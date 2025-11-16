<?php
// Inicia a sessão para podermos ler as mensagens de erro (se houver)
session_start();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Silvia Almeida Psicologia</title>
    
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="login-container">
        
        <form action="processa_login.php" method="POST">
            
            <h2>Silvia Almeida Psicologia</h2>
            <p>Por favor, acesse sua conta</p>
            
            <?php
            // Mostra mensagem de erro, se houver
            if (isset($_GET['erro'])) {
                echo '<p class="login-error">E-mail ou senha inválidos.</p>';
            }
            // Mostra mensagem de sucesso após redefinir a senha
            if (isset($_GET['redefinida'])) {
                echo '<p style="color: green; text-align: center; margin-bottom: 15px;">Senha redefinida com sucesso! Você já pode entrar.</p>';
            }
            ?>

            <div>
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit">Entrar</button>

            <div style="text-align: center; margin-top: 20px;">
                <a href="esqueci_senha.php">Esqueci minha senha</a>
            </div>
        </form>
    </div> </body>
</html>