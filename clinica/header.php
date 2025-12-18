<?php
// Apenas usa dados que já existem
$nome_usuario = $_SESSION['usuario_nome'] ?? '';
$tipo_usuario = $_SESSION['usuario_tipo'] ?? '';

// Título padrão
if (!isset($tituloPagina)) {
    $tituloPagina = "Painel";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tituloPagina) ?> - Clínica</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<aside class="sidebar">
    <h2>Silvia Almeida Psicologia</h2>
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="cadastrar_agendamento.php">Novo Agendamento</a></li>
            <li><a href="ver_agendamentos.php">Ver Agendamentos</a></li>
            <li><a href="cadastrar_paciente.php">Cadastrar Paciente</a></li>
            <li><a href="ver_pacientes.php">Ver Pacientes</a></li>
            <li><a href="ver_planos.php">Gerenciar Planos</a></li>

            <?php if ($tipo_usuario === 'admin'): ?>
                <li><a href="cadastrar.php">Gerenciar Usuários</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

<main class="main-content">

<header class="top-bar">
    <h1><?= htmlspecialchars($tituloPagina) ?></h1>
    <div class="user-info">
        <span>Olá, <strong><?= htmlspecialchars($nome_usuario) ?></strong></span>
        <a href="alterar_senha.php" class="change-pass">Alterar Senha</a>
        <a href="logout.php">Sair</a>
    </div>
</header>
        