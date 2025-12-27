<?php
// header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'conexao.php';

// Garante que o nome do usuário esteja carregado
if (empty($_SESSION['usuario_nome'])) {
    $stmt_nome = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt_nome->execute([$_SESSION['usuario_id']]);
    $u_nome = $stmt_nome->fetch();
    if ($u_nome) {
        $_SESSION['usuario_nome'] = $u_nome['nome'];
    }
}

$nome_usuario = $_SESSION['usuario_nome'] ?? 'Usuário';
$tipo_usuario = $_SESSION['usuario_tipo'] ?? '';

if (!isset($tituloPagina)) { $tituloPagina = "Painel"; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tituloPagina); ?> - Clínica</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<aside class="sidebar">
    <h2>Silvia Almeida<br><small style="font-size:0.6em">Psicologia</small></h2>
    <nav>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="cadastrar_agendamento.php"><i class="fas fa-calendar-plus"></i> Novo Agendamento</a></li>
            <li><a href="ver_agendamentos.php"><i class="fas fa-list"></i> Ver Agendamentos</a></li>
            <li><a href="cadastrar_paciente.php"><i class="fas fa-user-plus"></i> Cadastrar Paciente</a></li>
            <li><a href="ver_pacientes.php"><i class="fas fa-users"></i> Ver Pacientes</a></li>
            <li><a href="ver_planos.php"><i class="fas fa-notes-medical"></i> Gerenciar Planos</a></li>
            
            <li><a href="gerenciar_horarios.php" style="color: #ffeb3b;"><i class="fas fa-star"></i> Minha Disponibilidade</a></li>

            <?php if ($tipo_usuario === 'admin'): ?>
                <li><a href="cadastrar.php"><i class="fas fa-cog"></i> Gerenciar Usuários</a></li>
            <?php endif; ?>
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