<?php
// 1. Inicia a sessão e verifica segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Inclui a conexão
require_once 'conexao.php'; 

// 3. Verifica se os dados vieram por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Pega os dados do formulário
    $id_paciente = $_POST['id_paciente'];
    $nome_completo = $_POST['nome_completo'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $data_nascimento = $_POST['data_nascimento'];
    $status = $_POST['status'];

    try {
        // 5. CHECAGEM DE PERMISSÃO (REFORÇO)
        $sql_check = "SELECT id_profissional_responsavel FROM pacientes WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_paciente]);
        $pac_original = $stmt_check->fetch();

        if ($_SESSION['usuario_tipo'] != 'admin' && $pac_original['id_profissional_responsavel'] != $_SESSION['usuario_id']) {
            die("Acesso negado.");
        }

        // 6. Prepara o SQL de ATUALIZAÇÃO (UPDATE)
        $sql_update = "UPDATE pacientes SET 
                            nome_completo = ?, 
                            email = ?, 
                            telefone = ?, 
                            data_nascimento = ?, 
                            status = ?
                       WHERE 
                            id = ?";

        // 7. Executa o comando
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            $nome_completo,
            $email,
            $telefone,
            $data_nascimento,
            $status,
            $id_paciente // ID para o "WHERE"
        ]);

        // 8. Sucesso!
        echo "Paciente atualizado com sucesso!";
        echo '<br><a href="ver_pacientes.php">Voltar para a Lista de Pacientes</a>';
        echo '<br><a href="dashboard.php">Voltar ao Painel</a>';

    } catch (PDOException $e) {
        echo "Erro ao atualizar o paciente: " . $e->getMessage();
    }

} else {
    echo "Acesso inválido.";
}
?>