<?php
// Não precisa de session_start() aqui ainda
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Silvia Almeida Psicologia</title>
    
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="login-container">
        
        <form action="processa_esqueci_senha.php" method="POST">
            
            <h2>Recuperar Senha</h2>
            <p>Digite seu e-mail. Enviaremos um link para você redefinir sua senha.</p>
            
            <?php
            // Mostra mensagens de sucesso ou erro que podem vir da URL
            if (isset($_GET['erro'])) {
                echo '<p class="login-error">E-mail não encontrado.</p>';
            }
            if (isset($_GET['sucesso'])) {
                echo '<p style="color: green; text-align: center; margin-bottom: 15px;">Sucesso! Verifique seu e-mail (e a caixa de spam) para o link de redefinição.</p>';
            }
            ?>

            <div>
                <label for="email">Seu E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <button type="submit">Enviar Link de Recuperação</button>

            <div style="text-align: center; margin-top: 20px;">
                <a href="login.php">Voltar para o Login</a>
            </div>
        </form>
    </div> </body>
</html>