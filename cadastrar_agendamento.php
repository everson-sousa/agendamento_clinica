<?php
// 1. Define o título
$tituloPagina = "Novo Agendamento";

// 2. Inclui o cabeçalho
require_once 'header.php';

// 3. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 4. Pega os dados do usuário logado
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// 5. LÓGICA DE SELEÇÃO
$id_profissional_selecionado = null;
$lista_profissionais = [];
$lista_pacientes = [];

if ($tipo_usuario_logado == 'admin') {
    // --- LÓGICA DO ADMIN ---
    
    // Busca TODOS os profissionais para o Admin escolher
    try {
        $sql_profissionais = "SELECT id, nome FROM usuarios WHERE tipo_acesso = 'profissional' AND status = 'ativo' ORDER BY nome ASC";
        $stmt_profissionais = $pdo->query($sql_profissionais);
        $lista_profissionais = $stmt_profissionais->fetchAll();
    } catch (PDOException $e) {
        echo "Erro ao buscar profissionais: " . $e->getMessage();
    }

    // VERIFICA SE O ADMIN JÁ ESCOLHEU UM PROFISSIONAL (Etapa 2)
    if (isset($_GET['id_profissional'])) {
        $id_profissional_selecionado = $_GET['id_profissional'];
    }
    
} else {
    // --- LÓGICA DO PROFISSIONAL ---
    // Se é um profissional, ele só pode agendar para ele mesmo
    $id_profissional_selecionado = $id_usuario_logado;
}

// 6. BUSCA A LISTA DE PACIENTES
// SÓ busca os pacientes SE um profissional já foi selecionado
if ($id_profissional_selecionado) {
    try {
        $sql_pacientes = "SELECT id, nome_completo 
                          FROM pacientes 
                          WHERE id_profissional_responsavel = ? AND status = 'ativo' 
                          ORDER BY nome_completo ASC";
        
        $stmt_pacientes = $pdo->prepare($sql_pacientes);
        $stmt_pacientes->execute([$id_profissional_selecionado]);
        $lista_pacientes = $stmt_pacientes->fetchAll();

    } catch (PDOException $e) {
        echo "Erro ao buscar pacientes: " . $e->getMessage();
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
// (Só mostra se um profissional foi selecionado)
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
                Paciente não está na lista? 
                <a href="cadastrar_paciente.php">Cadastrar novo paciente</a>.
            </small>
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
// Fim da lógica condicional
?>

<?php
// Inclui o rodapé
require_once 'footer.php';
?>