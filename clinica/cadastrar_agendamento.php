<?php
require_once 'auth.php';
$tituloPagina = "Cadastrar Agendamentos";
require_once 'header.php';

$tituloPagina = "Novo Agendamento";
require_once 'header.php';
require_once 'conexao.php'; 

$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// ... (Lógica de seleção de profissional e busca de pacientes via AJAX continua igual) ...
// Para economizar espaço, estou mantendo a lógica inicial idêntica
$id_profissional_selecionado = ($tipo_usuario_logado == 'admin' && isset($_POST['id_profissional'])) ? $_POST['id_profissional'] : (($tipo_usuario_logado == 'profissional') ? $id_usuario_logado : null);

// Carregamentos iniciais (Admin/Profissional)
$lista_profissionais = [];
$lista_pacientes_inicial = [];
if ($tipo_usuario_logado == 'admin') {
    $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE tipo_acesso = 'profissional' AND status = 'ativo' ORDER BY nome ASC");
    $lista_profissionais = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT id, nome_completo FROM pacientes WHERE id_profissional_responsavel = ? AND status = 'ativo' ORDER BY nome_completo ASC");
    $stmt->execute([$id_usuario_logado]);
    $lista_pacientes_inicial = $stmt->fetchAll();
}

// Busca Planos (para o resumo)
$todos_planos_json = '[]';
if ($tipo_usuario_logado == 'profissional') {
    $stmt = $pdo->prepare("SELECT id_paciente, tipo_plano, tipo_atendimento, sessoes_contratadas, sessoes_utilizadas FROM planos_paciente WHERE id_profissional = ? AND status = 'Ativo'");
    $stmt->execute([$id_usuario_logado]);
    $todos_planos_json = json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>

<form action="processa_agendamento.php" method="POST">

    <?php if ($tipo_usuario_logado == 'admin'): ?>
        <div>
            <label for="id_profissional_ajax">Profissional:</label>
            <select id="id_profissional_ajax" name="id_profissional" required>
                <option value="">Selecione um profissional...</option>
                <?php foreach ($lista_profissionais as $prof): ?>
                    <option value="<?php echo $prof['id']; ?>"><?php echo htmlspecialchars($prof['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else: ?>
        <input type="hidden" name="id_profissional" value="<?php echo $id_usuario_logado; ?>">
    <?php endif; ?>

    <div>
        <label for="id_paciente">Paciente:</label>
        <select id="id_paciente" name="id_paciente" required <?php if ($tipo_usuario_logado == 'admin') echo 'disabled'; ?>>
            <option value="">Selecione um paciente...</option>
            <?php foreach ($lista_pacientes_inicial as $paciente): ?>
                <option value="<?php echo $paciente['id']; ?>"><?php echo htmlspecialchars($paciente['nome_completo']); ?></option>
            <?php endforeach; ?>
        </select>
        <small style="display:block; margin-top:5px;">
            Paciente não está na lista? <a href="cadastrar_paciente.php">Cadastrar novo paciente</a>.
        </small>
    </div>

    <div id="info-planos-paciente" style="display: none; padding: 10px; background-color: #e8f4f8; border: 1px solid #bce8f1; border-radius: 4px; margin-bottom: 15px; color: #31708f;"></div>

    <div>
        <label for="tipo_atendimento">O que deseja agendar?</label>
        <select id="tipo_atendimento" name="tipo_atendimento" required>
            <option value="terapia_avulsa">Terapia (Avulsa - 1 Sessão)</option>
            <option value="terapia_pacote">Terapia Pacote (Fixo 4 Sessões)</option>
            <option value="plantao">Plantão (1 Sessão)</option>
            <option value="avaliacao">Avaliação (Fixo 10 Sessões)</option>
        </select>
    </div>

    <hr style="margin: 20px 0;">
    
    <h3 id="titulo-data">Data da Consulta</h3>
    <p id="texto-data" style="margin-bottom: 15px; color: #666;">Selecione a data e hora.</p>

    <div>
        <label for="data_inicio">Data:</label>
        <input type="date" id="data_inicio" name="data_inicio" required>
    </div>
    <div>
        <label for="hora_inicio">Hora de Início:</label>
        <input type="time" id="hora_inicio" name="hora_inicio" required>
    </div>
    <div>
        <label for="hora_fim">Hora de Fim:</label>
        <input type="time" id="hora_fim" name="hora_fim" required>
    </div>

    <div>
        <label for="observacoes">Observações (Opcional):</label>
        <textarea id="observacoes" name="observacoes" rows="3"></textarea>
    </div>
    
    <button type="submit" style="background-color: #2ecc71; margin-top: 10px;">Confirmar Agendamento</button>
</form>

<script>
// 1. AJAX Profissional/Paciente (Mantido igual)
const profissionalSelect = document.getElementById('id_profissional_ajax');
const pacienteSelect = document.getElementById('id_paciente');
let planosData = <?php echo $todos_planos_json; ?>;

if (profissionalSelect) {
    profissionalSelect.addEventListener('change', function() {
        const idProf = this.value;
        pacienteSelect.innerHTML = '<option>Carregando...</option>';
        pacienteSelect.disabled = true;
        if (!idProf) return;

        // Busca Pacientes
        fetch(`buscar_pacientes.php?id_profissional=${idProf}`).then(r=>r.json()).then(d=>{
            pacienteSelect.innerHTML = '<option value="">Selecione...</option>';
            d.forEach(p => {
                pacienteSelect.innerHTML += `<option value="${p.id}">${p.nome_completo}</option>`;
            });
            pacienteSelect.disabled = false;
        });
        
        // Busca Planos (para atualizar a variável global)
        fetch(`buscar_planos.php?id_profissional=${idProf}`).then(r=>r.json()).then(d=>{
            planosData = d;
        });
    });
}

// 2. Resumo de Planos e Mudança de Texto
const tipoSelect = document.getElementById('tipo_atendimento');
const infoDiv = document.getElementById('info-planos-paciente');
const tituloData = document.getElementById('titulo-data');
const textoData = document.getElementById('texto-data');

function atualizarUI() {
    const tipo = tipoSelect.value;
    const pacienteId = pacienteSelect.value;
    
    // Muda textos
    if (tipo === 'terapia_pacote' || tipo === 'avaliacao') {
        tituloData.innerText = "Início do Agendamento Automático";
        textoData.innerText = "ATENÇÃO: Ao salvar, o sistema criará um NOVO PLANO e agendará todas as sessões (4 ou 10) semanalmente a partir desta data.";
    } else {
        tituloData.innerText = "Data da Consulta";
        textoData.innerText = "Agendamento de uma única sessão.";
    }

    // Mostra resumo de saldo (se tiver paciente selecionado)
    if (pacienteId) {
        // Lógica simplificada: mostra qualquer plano ativo
        const planosAtivos = planosData.filter(p => p.id_paciente == pacienteId);
        if (planosAtivos.length > 0) {
            let html = '<strong>Planos Ativos deste Paciente:</strong><br>';
            planosAtivos.forEach(p => {
                let saldo = p.sessoes_contratadas - p.sessoes_utilizadas;
                html += `- ${p.tipo_atendimento.toUpperCase()}: ${saldo}/${p.sessoes_contratadas} sessões restantes.<br>`;
            });
            infoDiv.innerHTML = html;
            infoDiv.style.display = 'block';
        } else {
            infoDiv.style.display = 'none';
        }
    }
}

tipoSelect.addEventListener('change', atualizarUI);
pacienteSelect.addEventListener('change', atualizarUI);
</script>

<?php require_once 'footer.php'; ?>