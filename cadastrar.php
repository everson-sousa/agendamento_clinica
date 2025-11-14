<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Novo Usuário</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { max-width: 400px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

    <h2>Cadastrar Novo Usuário</h2>
    <p>Use este formulário para criar o seu primeiro usuário "Admin".</p>

    <form action="processa_cadastro.php" method="POST">
        <div>
            <label for="nome">Nome:</label>
            <input type="text" id="nome" name="nome" required>
        </div>
        <div>
            <label for="email">E-mail (Login):</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        <div>
            <label for="telefone">Telefone (Opcional):</label>
            <input type="text" id="telefone" name="telefone">
        </div>
        <div>
            <label for="tipo_acesso">Tipo de Acesso:</label>
            <select id="tipo_acesso" name="tipo_acesso" required>
                <option value="admin">Admin (Root)</option>
                <option value="profissional">Profissional</option>
            </select>
        </div>
        
        <button type="submit">Cadastrar Usuário</button>
    </form>

</body>
</html>