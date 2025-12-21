<?php
session_start();

// 🔹 Usa APENAS o arquivo que você já tem
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST['email'] ?? '';
    $senha_pura = $_POST['senha'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: login.php?erro=1");
        exit;
    }

    $sql = "SELECT id, nome, email, senha, tipo_acesso
            FROM usuarios
            WHERE email = ? AND status = 'ativo'
            LIMIT 1";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($senha_pura, $usuario['senha'])) {

            // 🔐 Proteção contra session fixation
            session_regenerate_id(true);

            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo_acesso'];

            header("Location: dashboard.php");
            exit;

        } else {
            sleep(1); // anti brute-force básico
            header("Location: login.php?erro=1");
            exit;
        }

    } catch (PDOException $e) {
        error_log("Erro no login: " . $e->getMessage());
        header("Location: login.php?erro=1");
        exit;
    }

} else {
    header("HTTP/1.1 403 Forbidden");
    exit("Acesso inválido.");
}
?>