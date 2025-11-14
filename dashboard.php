<?php
// 1. Inicia a sessão
// DEVE ser a primeira linha do arquivo
session_start();

// 2. VERIFICAÇÃO DE SEGURANÇA
// Se a variável de sessão 'usuario_id' NÃO EXISTIR
// significa que o usuário NÃO ESTÁ LOGADO
if (!isset($_SESSION['usuario_id'])) {
    
    // Redireciona o usuário para a página de login
    header("Location: login.php");
    
    // Para o script para garantir que nada mais seja executado
    exit;
}

// 3. Se o script chegou até aqui, o usuário ESTÁ LOGADO!
// Podemos buscar os dados da sessão para personalizar a página
$nome_usuario = $_SESSION['usuario_nome'];
$tipo_usuario = $_SESSION['usuario_tipo'];

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Clínica</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header a { color: red; text-decoration: none; }
    </style>
</head>
<body>

    <div class="header">
    <h2>Painel Administrativo</h2>
    <div>
        <a href="alterar_senha.php" style="margin-right: 20px; color: #333;">Alterar Senha</a>
        <a href="logout.php">Sair (Logout)</a>
    </div>
</div>

    <p>Bem-vindo(a), <strong><?php echo htmlspecialchars($nome_usuario); ?></strong>!</p>
    <p>Seu nível de acesso é: <strong><?php echo htmlspecialchars($tipo_usuario); ?></strong></p>

    <hr>

    <h3>O que você pode fazer:</h3>
    
    <ul>
        <li><a href="cadastrar_agendamento.php">Cadastrar Novo Agendamento</a></li>
        <li><a href="ver_agendamentos.php">Ver Meus Agendamentos</a></li>
        
        <?php
        // 4. MOSTRA OPÇÕES DE ADMIN
        // Verifica se o usuário logado é do tipo 'admin'
        if ($tipo_usuario == 'admin') {
            echo "<li><strong><a href='cadastrar.php'>Gerenciar Usuários (Admin)</a></strong></li>";
        }
        ?>
    </ul>

</body>
</html>