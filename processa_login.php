<?php
// 1. INICIA A SESSÃO
// Isso DEVE ser a primeira coisa no arquivo, antes de qualquer HTML ou echo.
session_start();

// 2. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 3. Verifica se os dados vieram por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Pega os dados do formulário
    $email = $_POST['email'];
    $senha_pura = $_POST['senha']; // A senha que o usuário digitou

    // 5. Prepara a consulta para buscar o usuário pelo E-MAIL
    $sql = "SELECT id, nome, email, senha, tipo_acesso 
            FROM usuarios 
            WHERE email = ? AND status = 'ativo'";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        
        // 6. Pega o usuário do banco (se ele existir)
        // fetch() pega apenas UMA linha, que é o que queremos
        $usuario = $stmt->fetch();

        // 7. A MÁGICA: VERIFICA A SENHA
        // $usuario existe? E a senha bate com a do banco?
        // password_verify() compara a senha pura com o hash do banco.
        if ($usuario && password_verify($senha_pura, $usuario['senha'])) {
            
            // 8. LOGIN COM SUCESSO!
            // Senha correta! Agora, salvamos os dados do usuário na SESSÃO
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo_acesso'];

            // 9. Redireciona para a página principal do sistema (o painel)
            // (Vamos criar essa página 'dashboard.php' em seguida)
            header("Location: dashboard.php");
            exit; // Para o script aqui após o redirecionamento

        } else {
            // 10. LOGIN FALHOU
            // Usuário ou senha incorretos
            header("Location: login.php?erro=1");
            exit;
            // (Em um sistema real, redirecionaríamos de volta ao login)
            // header("Location: login.php?erro=1");
        }

    } catch (PDOException $e) {
        // Erro no banco de dados
        echo "Erro ao processar o login: " . $e->getMessage();
    }

} else {
    // Se tentarem acessar o arquivo direto
    echo "Acesso inválido.";
}
?>