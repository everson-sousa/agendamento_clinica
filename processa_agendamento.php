<?php
// 1. Inicia a sessão e faz a verificação de segurança
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
    $id_profissional = $_POST['id_profissional'];
    $nome_paciente = $_POST['nome_paciente'];
   // Combina data e hora para o formato DATETIME do MySQL
    $data_hora_inicio = $_POST['data_inicio'] . ' ' . $_POST['hora_inicio'];
    $data_hora_fim = $_POST['data_inicio'] . ' ' . $_POST['hora_fim'];
    $observacoes = $_POST['observacoes'];
    
    // O status padrão de um novo agendamento é "marcado"
    $status = 'marcado';

    // 5. Validação e Segurança
    // Se o usuário logado é um 'profissional', ele SÓ PODE marcar para ele mesmo.
    if ($_SESSION['usuario_tipo'] == 'profissional' && $id_profissional != $_SESSION['usuario_id']) {
        die("Erro: Você não tem permissão para marcar agendamentos para outros profissionais.");
    }
    
    // (Validação futura: verificar se o horário já está ocupado)

    // 6. Prepara o Comando SQL
    $sql = "INSERT INTO agendamentos (id_profissional, nome_paciente, data_hora_inicio, data_hora_fim, status, observacoes)
            VALUES (?, ?, ?, ?, ?, ?)";

    // 7. Tenta executar no banco
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_profissional,
            $nome_paciente,
            $data_hora_inicio,
            $data_hora_fim,
            $status,
            $observacoes
        ]);

        // 8. Sucesso!
        echo "Agendamento cadastrado com sucesso!";
        echo '<br><a href="dashboard.php">Voltar ao Painel</a>';
        echo '<br><a href="cadastrar_agendamento.php">Cadastrar Outro</a>';

    } catch (PDOException $e) {
        // 9. Erro!
        echo "Erro ao cadastrar o agendamento: " . $e->getMessage();
    }

} else {
    // Se alguém tentar acessar o arquivo direto pela URL
    echo "Acesso inválido.";
}
?>