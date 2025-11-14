<?php
// 1. Inicia a sessão e verifica segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 3. Verifica se os dados vieram por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Pega os dados do formulário
    $id_agendamento = $_POST['id_agendamento'];
    $nome_paciente = $_POST['nome_paciente'];
    // Combina data e hora para o formato DATETIME do MySQL
    $data_hora_inicio = $_POST['data_inicio'] . ' ' . $_POST['hora_inicio'];
    $data_hora_fim = $_POST['data_inicio'] . ' ' . $_POST['hora_fim'];
    $status = $_POST['status'];
    $observacoes = $_POST['observacoes'];

    try {
        // 5. CHECAGEM DE PERMISSÃO (REFORÇO DE SEGURANÇA)
        // Antes de atualizar, verifica se o usuário tem permissão
        // Busca o "dono" original do agendamento
        $sql_check = "SELECT id_profissional FROM agendamentos WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_agendamento]);
        $ag_original = $stmt_check->fetch();

        if ($_SESSION['usuario_tipo'] != 'admin' && $ag_original['id_profissional'] != $_SESSION['usuario_id']) {
            die("Acesso negado. Você não pode salvar alterações neste agendamento.");
        }

        // 6. Prepara o Comando SQL de ATUALIZAÇÃO (UPDATE)
        $sql_update = "UPDATE agendamentos SET 
                            nome_paciente = ?, 
                            data_hora_inicio = ?, 
                            data_hora_fim = ?, 
                            status = ?, 
                            observacoes = ?
                       WHERE 
                            id = ?";

        // 7. Executa o comando
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            $nome_paciente,
            $data_hora_inicio,
            $data_hora_fim,
            $status,
            $observacoes,
            $id_agendamento // O ID entra por último, para o "WHERE id = ?"
        ]);

        // 8. Sucesso!
        echo "Agendamento atualizado com sucesso!";
        echo '<br><a href="ver_agendamentos.php">Voltar para a Lista</a>';
        echo '<br><a href="dashboard.php">Voltar ao Painel</a>';

    } catch (PDOException $e) {
        // 9. Erro!
        echo "Erro ao atualizar o agendamento: " . $e->getMessage();
    }

} else {
    // Se alguém tentar acessar o arquivo direto pela URL
    echo "Acesso inválido.";
}
?>