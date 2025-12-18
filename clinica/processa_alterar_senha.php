<?php
// 1. Inicia a sessão e verifica segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 3. Pega o ID do usuário logado
$id_usuario_logado = $_SESSION['usuario_id'];

// 4. Verifica se os dados vieram por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 5. Pega os dados do formulário
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    // 6. VALIDAÇÃO 1: As senhas novas batem?
    if ($nova_senha != $confirma_senha) {
        die("Erro: A 'Nova Senha' e a 'Confirmação' não são idênticas. <a href='alterar_senha.php'>Tente novamente</a>.");
    }

    try {
        // 7. Busca a senha (hash) atual no banco
        $sql_busca = "SELECT senha FROM usuarios WHERE id = ?";
        $stmt_busca = $pdo->prepare($sql_busca);
        $stmt_busca->execute([$id_usuario_logado]);
        $usuario = $stmt_busca->fetch();
        
        $hash_senha_banco = $usuario['senha'];

        // 8. VALIDAÇÃO 2: A "Senha Atual" digitada bate com a do banco?
        if (password_verify($senha_atual, $hash_senha_banco)) {
            
            // 9. SUCESSO: A senha atual está correta!
            // Criptografa a NOVA senha
            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            
            // 10. Atualiza o banco com a NOVA senha
            $sql_update = "UPDATE usuarios SET senha = ? WHERE id = ?";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([$nova_senha_hash, $id_usuario_logado]);

            echo "Senha alterada com sucesso!";
            echo '<br><a href="dashboard.php">Voltar ao Painel</a>';

        } else {
            // 11. FALHA: Senha atual incorreta
            echo "Erro: A 'Senha Atual' está incorreta. <a href='alterar_senha.php'>Tente novamente</a>.";
        }

    } catch (PDOException $e) {
        echo "Erro ao processar a alteração: " . $e->getMessage();
    }

} else {
    // Se alguém tentar acessar o arquivo direto pela URL
    echo "Acesso inválido.";
}
?>