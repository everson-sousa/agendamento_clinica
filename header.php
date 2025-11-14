<?php
// 1. Inicia a sessão
session_start();

// 2. VERIFICAÇÃO DE SEGURANÇA
// Se a variável de sessão 'usuario_id' NÃO EXISTIR
// significa que o usuário NÃO ESTÁ LOGADO
if (!isset($_SESSION['usuario_id'])) {
    
    // Redireciona o usuário para a página de login
    header("Location: login.php");
    
    // Para o script
    exit;
}

// 3. Pega os dados da sessão
$nome_usuario = $_SESSION['usuario_nome'];
$tipo_usuario = $_SESSION['usuario_tipo'];

// Esta variável $tituloPagina será definida em cada página
if (!isset($tituloPagina)) {
    $tituloPagina = "Painel";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina); ?> - Clínica</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <aside class="sidebar">
        <h2>Silvia Almeida Psicologia</h2>
        <nav>
            <ul>
                <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="cadastrar_agendamento.php">Novo Agendamento</a></li>
                <li><a href="ver_agendamentos.php">Ver Agendamentos</a></li>
                
                <li><a href="cadastrar_paciente.php">Cadastrar Paciente</a></li>
                
                <?php
                // Mostra link de "Gerenciar Usuários" SÓ para o admin
                if ($tipo_usuario == 'admin') {
                    echo "<li><a href='cadastrar.php'>Gerenciar Usuários</a></li>";
                }
                ?>
            </ul>
        </nav>
                
                
                <?php
                // Mostra link de "Gerenciar Usuários" SÓ para o admin
                if ($tipo_usuario == 'admin') {
                    echo "<li><a href='cadastrar.php'>Gerenciar Usuários</a></li>";
                }
                ?>
            </ul>
        </nav>
    </aside>

    <main class="main-content">

        <header class="top-bar">
            <h1><?php echo htmlspecialchars($tituloPagina); ?></h1>
            <div class="user-info">
                <span>Olá, <strong><?php echo htmlspecialchars($nome_usuario); ?></strong></span>
                <a href="alterar_senha.php" class="change-pass">Alterar Senha</a>
                <a href="logout.php">Sair</a>
            </div>
        </header>