<?php
// login.php - Versão Final (Corrigida)

// 1. PRIMEIRO carregamos a conexão (para ela configurar o que precisar)
require_once 'conexao.php';

// 2. DEPOIS iniciamos a sessão (agora seguro)
session_start();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    try {
        // O resto do código continua igualzinho...
        $stmt = $pdo->prepare("SELECT id, nome, senha, tipo_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo_usuario'];
            
            header("Location: dashboard.php");
            exit;
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    } catch (Exception $e) {
        $erro = "Erro técnico: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Clínica</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; margin: 0; height: 100vh; display: flex; justify-content: center; align-items: center; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin-top: 0; color: #2c3e50; margin-bottom: 5px; }
        p.subtitle { color: #7f8c8d; margin-bottom: 25px; font-size: 0.9em; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 6px; background-color: #f0f4f8; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background-color: #2ecc71; color: white; border: none; border-radius: 6px; font-size: 1.1rem; font-weight: bold; cursor: pointer; }
        button:hover { background-color: #27ae60; }
        .erro-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center; border: 1px solid #f5c6cb; }
        .links { margin-top: 15px; text-align: center; } .links a { color: #007bff; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Silvia Almeida Psicologia</h2>
        <p class="subtitle">Por favor, acesse sua conta</p>
        <?php if($erro): ?> <div class="erro-msg"><?php echo $erro; ?></div> <?php endif; ?>
        <form method="POST">
            <label>E-mail:</label>
            <input type="email" name="email" required placeholder="seu@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <label>Senha:</label>
            <input type="password" name="senha" required placeholder="••••••••">
            <button type="submit">Entrar</button>
            <div class="links"><a href="#">Esqueci minha senha</a></div>
        </form>
    </div>
</body>
</html>