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

<p>Use este formulário para criar um novo plano e agendar as sessões sequenciais automaticamente.</p>

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
        <div id="profissional-display" style="font-weight: bold; color: #007bff; margin-bottom: 15px;">
            Selecione o profissional para continuar.
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

    <input type="hidden" id="tipo_plano_hidden" name="tipo_plano" value="Pacote">
    
    <div>
        <label for="tipo_atendimento">Tipo de Atendimento:</label>
        <select id="tipo_atendimento" name="tipo_atendimento" required>
            <option value="terapia_avulsa">Terapia (Avulsa - 1 Sessão)</option>
            <option value="terapia_pacote" selected>Terapia Pacote (Fixo 4 Sessões)</option>
            <option value="plantao">Plantão (1 Sessão)</option>
            <option value="avaliacao">Avaliação (Fixo 10 Sessões)</option>
        </select>
    </div>

    <div>
        <label for="valor">Valor Total do Pacote/Sessão (R$):</label>
        <input type="number" id="valor" name="valor" step="0.01" min="0" placeholder="Ex: 150.00" required>
        <small>Digite o valor total a ser cobrado. Use ponto para centavos.</small>
    </div>
    
    <hr style="margin: 20px 0;">

    <h3 id="titulo-agendamento" style="margin-bottom: 10px;">Agendamento Automático</h3>
    <p id="texto-agendamento" style="margin-bottom: 15px;">Defina o horário da 1ª sessão. As seguintes serão agendadas semanalmente.</p>

    <div>
        <label for="data_inicio">Data da 1ª Consulta:</label>
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

    <button type="submit" style="background-color: #e67e22;">Salvar Plano e Agendar</button>
</form>

<script>
const profissionalSelect = document.getElementById('id_profissional_ajax');
const pacienteSelect = document.getElementById('id_paciente');
const profissionalDisplay = document.getElementById('profissional-display'); 
const tipoSelect = document.getElementById('tipo_atendimento');
const tituloAgendamento = document.getElementById('titulo-agendamento');
const textoAgendamento = document.getElementById('texto-agendamento');
const tipoPlanoHidden = document.getElementById('tipo_plano_hidden'); // Pega o campo escondido

if (profissionalSelect) {
    // Lógica AJAX (omitida para brevidade, mas está correta)
    profissionalSelect.addEventListener('change', function() {
        const idProfissional = this.value;
        const nomeProfissional = profissionalSelect.options[profissionalSelect.selectedIndex].text;
        
        profissionalDisplay.innerText = `Agendando para: ${nomeProfissional}`;
        pacienteSelect.innerHTML = '<option value="">Carregando...</option>';
        pacienteSelect.disabled = true;

        if (!idProfissional) {
            profissionalDisplay.innerText = 'Selecione o profissional para continuar.';
            pacienteSelect.innerHTML = '<option value="">Selecione um profissional acima</option>';
            return;
        }

        fetch(`buscar_pacientes.php?id_profissional=${idProfissional}`)
            .then(response => response.json())
            .then(pacientes => {
                pacienteSelect.innerHTML = '<option value="">Selecione um paciente</option>';
                pacientes.forEach(p => { pacienteSelect.innerHTML += `<option value="${p.id}">${p.nome_completo}</option>`; });
                pacienteSelect.disabled = false;
            })
            .catch(error => { console.error('Erro no AJAX:', error); });
    });
}

// LÓGICA VISUAL E DE VALOR ESCONDIDO
function atualizarTextoUI() {
    const tipo = tipoSelect.value;
    
    // 1. Lógica de Agendamento Sequencial (Muda Texto)
    if (tipo === 'plantao' || tipo === 'terapia_avulsa') {
        tituloAgendamento.innerText = "Agendamento da Consulta";
        textoAgendamento.innerText = "Defina a data e o horário desta sessão única.";
    } else {
        tituloAgendamento.innerText = "Agendamento Automático (Sequencial)";
        textoAgendamento.innerText = "Defina o horário da 1ª sessão. As seguintes serão agendadas semanalmente.";
    }

    // 2. LÓGICA CRUCIAL: SETAR O VALOR ESCONDIDO (Tipo de Registro)
    if (tipo === 'avaliacao') {
        // Avaliação (10 sessões) é o Plano de Tratamento (Global)
        tipoPlanoHidden.value = 'Tratamento';
    } else {
        // Terapia Pacote, Avulsa e Plantão são Pacotes (Financeiro)
        tipoPlanoHidden.value = 'Pacote';
    }
}
tipoSelect.addEventListener('change', atualizarTextoUI);
document.addEventListener('DOMContentLoaded', atualizarTextoUI);
</script>

<?php
require_once 'footer.php'; 
?>