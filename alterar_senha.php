<?php
// 1. Inicia a sessão e faz a verificação de segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { max-width: 400px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="password"] { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background-color: #ffc107; color: black; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header a { text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Alterar Minha Senha</h2>
        <a href="dashboard.php">Voltar ao Painel</a>
    </div>

    <form action="processa_alterar_senha.php" method="POST">
        <div>
            <label for="senha_atual">Senha Atual:</label>
            <input type="password" id="senha_atual" name="senha_atual" required>
        </div>
        <div>
            <label for="nova_senha">Nova Senha:</label>
            <input type="password" id="nova_senha" name="nova_senha" required>
        </div>
        <div>
            <label for="confirma_senha">Confirme a Nova Senha:</label>
            <input type="password" id="confirma_senha" name="confirma_senha" required>
        </div>
        
        <button type="submit">Atualizar Senha</button>
    </form>
</body>
</html>