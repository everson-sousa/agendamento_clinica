<?php
$tituloPagina = "Adicionar Novo Plano/Pacote"; 
require_once 'header.php'; 
require_once 'conexao.php'; 

$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// LÓGICA: Se for profissional, já busca os pacientes dele
$lista_pacientes_inicial = [];
if ($tipo_usuario_logado == 'profissional') {
    try {
        $sql_pacientes = "SELECT id, nome_completo FROM pacientes 
                          WHERE id_profissional_responsavel = ? AND status = 'ativo' 
                          ORDER BY nome_completo ASC";
        
        $stmt_pacientes = $pdo->prepare($sql_pacientes);
        $stmt_pacientes->execute([$id_usuario_logado]);
        $lista_pacientes_inicial = $stmt_pacientes->fetchAll();

    } catch (PDOException $e) {
        echo "Erro ao buscar pacientes: " . $e->getMessage();
    }
}

// SE FOR ADMIN, busca os profissionais
$lista_profissionais = [];
if ($tipo_usuario_logado == 'admin') {
    try {
        $sql_profissionais = "SELECT id, nome FROM usuarios 
                              WHERE tipo_acesso = 'profissional' AND status = 'ativo' ORDER BY nome ASC";
        $stmt_profissionais = $pdo->query($sql_profissionais);
        $lista_profissionais = $stmt_profissionais->fetchAll();
    } catch (PDOException $e) {
        echo "Erro ao buscar profissionais: " . $e->getMessage();
    }
}
?>

<p>Use este formulário para criar um novo plano e agendar sessões.</p>

<form action="processa_plano.php" method="POST">

    <?php if ($tipo_usuario_logado == 'admin'): ?>
        <div>
            <label for="id_profissional_ajax">Profissional:</label>
            <select id="id_profissional_ajax" name="id_profissional" required>
                <option value="">Selecione o profissional...</option>
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
        <select id="id_paciente" name="id_paciente" required 
            <?php if ($tipo_usuario_logado == 'admin') echo 'disabled'; ?>
        >
            <option value="">
                <?php echo ($tipo_usuario_logado == 'admin') ? 'Selecione um profissional acima' : 'Selecione seu paciente...'; ?>
            </option>
            
            <?php foreach ($lista_pacientes_inicial as $paciente): ?>
                <option value="<?php echo $paciente['id']; ?>"><?php echo htmlspecialchars($paciente['nome_completo']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <hr style="margin: 20px 0;">

    <div>
        <label for="tipo_plano">Tipo de Registro (Financeiro):</label>
        <select id="tipo_plano" name="tipo_plano" required>            
            <option value="Pacote">Pacote de Pagamento (Financeiro)</option>
        </select>
    </div>
    
    <div>
        <label for="tipo_atendimento">Tipo de Atendimento:</label>
        <select id="tipo_atendimento" name="tipo_atendimento" required>
            <option value="terapia_avulsa">Terapia (Avulsa - 1 Sessão)</option>
            <option value="terapia_pacote" selected>Terapia Pacote (Fixo 4 Sessões)</option>
            <option value="plantao">Plantão (1 Sessão)</option>
            <option value="avaliacao">Avaliação (Fixo 10 Sessões)</option>
        </select>
    </div>
    
    <hr style="margin: 20px 0;">

    <h3 id="titulo-agendamento" style="margin-bottom: 10px;">Agendamento Automático</h3>
    <p id="texto-agendamento" style="margin-bottom: 15px;">Defina o horário da 1ª sessão. As seguintes serão agendadas semanalmente.</p>

    <div>
        <label for="data_inicio">Data da Consulta:</label>
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

    <button type="submit" style="background-color: #e67e22;">Salvar e Agendar</button>
</form>

<script>
// --- 1. AJAX PARA CARREGAR PACIENTES (ADMIN) ---
const profissionalSelect = document.getElementById('id_profissional_ajax');
const pacienteSelect = document.getElementById('id_paciente');

if (profissionalSelect) {
    profissionalSelect.addEventListener('change', function() {
        const idProfissional = this.value;
        pacienteSelect.innerHTML = '<option value="">Carregando...</option>';
        pacienteSelect.disabled = true;

        if (!idProfissional) {
            pacienteSelect.innerHTML = '<option value="">Selecione um profissional acima</option>';
            return;
        }

        fetch(`buscar_pacientes.php?id_profissional=${idProfissional}`)
            .then(response => response.json())
            .then(pacientes => {
                pacienteSelect.innerHTML = '<option value="">Selecione um paciente</option>';
                pacientes.forEach(paciente => {
                    const option = document.createElement('option');
                    option.value = paciente.id;
                    option.textContent = paciente.nome_completo;
                    pacienteSelect.appendChild(option);
                });
                pacienteSelect.disabled = false;
            })
            .catch(error => {
                console.error('Erro:', error);
                pacienteSelect.innerHTML = '<option value="">Erro ao carregar</option>';
            });
    });
}

// --- 2. LÓGICA VISUAL: MUDA O TEXTO CONFORME O TIPO ---
const tipoSelect = document.getElementById('tipo_atendimento');
const tituloAgendamento = document.getElementById('titulo-agendamento');
const textoAgendamento = document.getElementById('texto-agendamento');

function atualizarTextoUI() {
    const tipo = tipoSelect.value;
    
    if (tipo === 'plantao' || tipo === 'terapia_avulsa') {
        // Se for sessão única
        tituloAgendamento.innerText = "Agendamento da Consulta";
        textoAgendamento.innerText = "Defina a data e o horário desta sessão única.";
    } else {
        // Se for pacote/sequencial
        tituloAgendamento.innerText = "Agendamento Automático (Sequencial)";
        textoAgendamento.innerText = "Defina o horário da 1ª sessão. As seguintes serão agendadas semanalmente.";
    }
}

// Escuta a mudança no dropdown
tipoSelect.addEventListener('change', atualizarTextoUI);

// Roda uma vez ao carregar a página (para ajustar o texto inicial)
document.addEventListener('DOMContentLoaded', atualizarTextoUI);
</script>

<?php
require_once 'footer.php'; 
?>