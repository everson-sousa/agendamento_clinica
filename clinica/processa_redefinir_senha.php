<?php
require 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    // 1. Validação básica
    if ($nova_senha != $confirma_senha) {
        die("As senhas não conferem. <a href='javascript:history.back()'>Voltar</a>");
    }

    try {
        // 2. Busca o e-mail associado ao token (verificando validade de novo por segurança)
        $sql_token = "SELECT email FROM password_resets WHERE token = ? AND data_expiracao > NOW()";
        $stmt_token = $pdo->prepare($sql_token);
        $stmt_token->execute([$token]);
        $resetRequest = $stmt_token->fetch();

        if (!$resetRequest) {
            die("Link inválido ou expirado.");
        }

        $email = $resetRequest['email'];

        // 3. Criptografa a nova senha
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        // 4. Atualiza a senha na tabela USUARIOS
        $sql_update = "UPDATE usuarios SET senha = ? WHERE email = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$senha_hash, $email]);

        // 5. Apaga o token (para ele não ser usado de novo)
        $sql_delete = "DELETE FROM password_resets WHERE email = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([$email]);

        // 6. Sucesso! Redireciona para o login
        header("Location: login.php?redefinida=1");
        exit;

    } catch (PDOException $e) {
        echo "Erro ao atualizar senha: " . $e->getMessage();
    }
}
?>