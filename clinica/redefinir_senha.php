<?php
require 'conexao.php';

// 1. Verifica se tem token na URL
if (!isset($_GET['token'])) {
    die("Token não fornecido.");
}

$token = $_GET['token'];

// 2. Verifica se o token existe e se NÃO expirou
try {
    // A data de expiração deve ser MAIOR que agora (NOW())
    $sql = "SELECT email FROM password_resets WHERE token = ? AND data_expiracao > NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token]);
    $resetRequest = $stmt->fetch();

    if (!$resetRequest) {
        die("Link inválido ou expirado. Por favor, solicite uma nova redefinição.");
    }
    
    // Se chegou aqui, o token é válido!

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <form action="processa_redefinir_senha.php" method="POST">
            <h2>Nova Senha</h2>
            <p>Crie uma nova senha para sua conta.</p>

            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div>
                <label for="nova_senha">Nova Senha:</label>
                <input type="password" id="nova_senha" name="nova_senha" required>
            </div>
            <div>
                <label for="confirma_senha">Confirme a Senha:</label>
                <input type="password" id="confirma_senha" name="confirma_senha" required>
            </div>

            <button type="submit">Alterar Senha</button>
        </form>
    </div>
</body>
</html>