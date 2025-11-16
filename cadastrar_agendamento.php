<?php
$tituloPagina = "Novo Agendamento";
require_once 'header.php';
require_once 'conexao.php'; 

$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

$id_profissional_selecionado = null;
$lista_profissionais = [];
$lista_pacientes = [];
$todos_planos_json = '[]'; // Prepara para o JavaScript

if ($tipo_usuario_logado == 'admin') {
    // --- LÓGICA DO ADMIN ---
    try {
        $sql_profissionais = "SELECT id, nome FROM usuarios WHERE tipo_acesso = 'profissional' AND status = 'ativo' ORDER BY nome ASC";
        $stmt_profissionais = $pdo->query($sql_profissionais);
        $lista_profissionais = $stmt_profissionais->fetchAll();
    } catch (PDOException $e) {
        echo "Erro ao buscar profissionais: " . $e->getMessage();
    }

    if (isset($_GET['id_profissional'])) {
        $id_profissional_selecionado = $_GET['id_profissional'];
    }
    
} else {
    // --- LÓGICA DO PROFISSIONAL ---
    $id_profissional_selecionado = $id_usuario_logado;
}

// SÓ busca os pacientes e planos SE um profissional já foi selecionado
if ($id_profissional_selecionado) {
    // Busca Pacientes
    try {
        $sql_pacientes = "SELECT id, nome_completo FROM pacientes 
                          WHERE id_profissional_responsavel = ? AND status = 'ativo' 
                          ORDER BY nome_completo ASC";
        $stmt_pacientes = $pdo->prepare($sql_pacientes);
        $stmt_pacientes->execute([$id_profissional_selecionado]);
        $lista_pacientes = $stmt_pacientes->fetchAll();
    } catch (PDOException $e) {
        echo "Erro ao buscar pacientes: " . $e->getMessage();
    }

    // *** NOVA LÓGICA: BUSCA TODOS OS PLANOS DESTE PROFISSIONAL ***
    try {
        $sql_planos = "SELECT id_paciente, tipo_plano, tipo_atendimento, sessoes_contratadas, sessoes_utilizadas 
                       FROM planos_paciente 
                       WHERE id_profissional = ? AND status = 'Ativo'";
        $stmt_planos = $pdo->prepare($sql_planos);
        $stmt_planos->execute([$id_profissional_selecionado]);
        $todos_planos = $stmt_planos->fetchAll(PDO::FETCH_ASSOC);
        
        // Converte o array PHP em JSON para o JavaScript ler
        $todos_planos_json = json_encode($todos_planos);

    } catch (PDOException $e) {
        echo "Erro ao buscar planos: " . $e->getMessage();
    }
}
?>

<?php
// --- ETAPA 1 (PARA ADMINS): MOSTRA SELEÇÃO DE PROFISSIONAL ---
if ($tipo_usuario_logado == 'admin' && !$id_profissional_selecionado):
?>
    
    <h3>Etapa 1: Selecionar Profissional</h3>
    <p>Para qual profissional você deseja agendar?</p>
    
    <form action="cadastrar_agendamento.php" method="GET">
        <div>
            <label for="id_profissional">Profissional:</label>
            <select id="id_profissional" name="id_profissional" required>
                <option value="">Selecione...</option>
                <?php foreach ($lista_profissionais as $profissional): ?>
                    <option value="<?php echo $profissional['id']; ?>">
                        <?php echo htmlspecialchars($profissional['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Avançar (Carregar Pacientes)</button>
    </form>

<?php
// --- ETAPA 2 (PARA TODOS): MOSTRA FORMULÁRIO DE AGENDAMENTO ---
elseif ($id_profissional_selecionado):
?>
    
    <form action="processa_agendamento.php" method="POST">
        <input type="hidden" name="id_profissional" value="<?php echo $id_profissional_selecionado; ?>">

        <div>
            <label for="id_paciente">Paciente:</label>
            <select id="id_paciente" name="id_paciente" required>
                <option value="">Selecione um paciente</option>
                <?php foreach ($lista_pacientes as $paciente): ?>
                    <option value="<?php echo $paciente['id']; ?>">
                        <?php echo htmlspecialchars($paciente['nome_completo']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="display:block; margin-top:5px;">
                Paciente não está na lista? <a href="cadastrar_paciente.php">Cadastrar novo paciente</a>.
            </small>
        </div>

        <div id="info-planos-paciente" style="display: none; padding: 10px; background-color: #f4f7fa; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
            </div>

        <div>
            <label for="tipo_atendimento">Tipo de Atendimento:</label>
            <select id="tipo_atendimento" name="tipo_atendimento" required>
                <option value="terapia">Terapia (Sessão)</option>
                <option value="plantao">Plantão</option>
                <option value="avaliacao">Avaliação</option>
            </select>
        </div>
        
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
        <div>
            <label for="observacoes">Observações (Opcional):</label>
            <textarea id="observacoes" name="observacoes" rows="4"></textarea>
        </div>
        
        <button type="submit">Salvar Agendamento</button>
    </form>

<?php
endif; 
?>
<script>
// Pega os dados de TODOS os planos que o PHP buscou
const planosData = <?php echo $todos_planos_json; ?>;

// Pega os elementos do formulário
const pacienteSelect = document.getElementById('id_paciente');
const tipoAtendimentoSelect = document.getElementById('tipo_atendimento');
const infoDiv = document.getElementById('info-planos-paciente');

// Função para ATUALIZAR o resumo
function atualizarInfoPlanos() {
    const pacienteId = pacienteSelect.value;
    const tipoAtendimento = tipoAtendimentoSelect.value; // 'terapia' ou 'avaliacao'
    
    infoDiv.innerHTML = ''; // Limpa o resumo
    
    if (!pacienteId) {
        infoDiv.style.display = 'none'; // Esconde se nenhum paciente for selecionado
        return;
    }

    // Filtra os planos para este paciente e este tipo de atendimento
    const planosTratamento = planosData.filter(p => 
        p.id_paciente == pacienteId && 
        p.tipo_plano == 'Tratamento' && 
        p.tipo_atendimento.toLowerCase() == tipoAtendimento
    );
    
    const planosPacote = planosData.filter(p => 
        p.id_paciente == pacienteId && 
        p.tipo_plano == 'Pacote' &&
        p.tipo_atendimento.toLowerCase() == tipoAtendimento
    );

    let html = '<strong>Resumo de Planos:</strong><br>';
    let temPlano = false;

    // Mostra o Plano de Tratamento (Ex: 10 sessões)
    if (planosTratamento.length > 0) {
        temPlano = true;
        planosTratamento.forEach(plano => {
            let restantes = plano.sessoes_contratadas - plano.sessoes_utilizadas;
            html += `Plano de Tratamento: ${restantes}/${plano.sessoes_contratadas} sessões restantes.<br>`;
        });
    }

    // Mostra o Pacote de Pagamento (Ex: 4 sessões)
    if (planosPacote.length > 0) {
        temPlano = true;
        planosPacote.forEach(plano => {
            let restantes = plano.sessoes_contratadas - plano.sessoes_utilizadas;
            html += `Pacote Pago: ${restantes}/${plano.sessoes_contratadas} sessões restantes.<br>`;
        });
    }

    if (!temPlano) {
        html += 'Nenhum plano ou pacote ativo encontrado para este tipo de atendimento.';
    }

    infoDiv.innerHTML = html;
    infoDiv.style.display = 'block'; // Mostra o resumo
}

// "Escuta" mudanças nos dois dropdowns
pacienteSelect.addEventListener('change', atualizarInfoPlanos);
tipoAtendimentoSelect.addEventListener('change', atualizarInfoPlanos);
</script>

<?php
require_once 'footer.php';
?>