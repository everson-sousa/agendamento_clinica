<?php
// 1. Inclui o arquivo de conexão
// Se este arquivo não for encontrado, o script para
require_once 'conexao.php'; // Traz a variável $pdo para cá

// 2. Verifica se os dados foram enviados via POST
// (Evita que o script seja acessado diretamente pela URL)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 3. Pega os dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha_pura = $_POST['senha']; // Senha como o usuário digitou
    $telefone = $_POST['telefone'];
    $tipo_acesso = $_POST['tipo_acesso'];

    // 4. SEGURANÇA: Criptografa a senha
    // NUNCA salve senhas em texto puro!
    // password_hash() cria um hash seguro.
    $senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);

    // 5. Prepara o Comando SQL (Usando "Prepared Statements")
    // Os "?" são marcadores de posição. Isso previne SQL Injection.
    $sql = "INSERT INTO usuarios (nome, email, senha, telefone, tipo_acesso) 
            VALUES (?, ?, ?, ?, ?)";

    // 6. Tenta executar o comando no banco
    try {
        // Prepara a consulta
        $stmt = $pdo->prepare($sql);
        
        // Executa a consulta, passando os dados em um array
        // A ordem DEVE ser a mesma dos "?"
        $stmt->execute([
            $nome,
            $email,
            $senha_hash,   // Salva a senha criptografada
            $telefone,
            $tipo_acesso
        ]);

        // 7. Se deu certo, avisa o usuário
        echo "Usuário cadastrado com sucesso!";
        // Você pode redirecionar para uma página de login aqui
        // header("Location: login.php");

    } catch (PDOException $e) {
        // 8. Se deu erro...
        // Verifica se o erro é de "email duplicado"
        if ($e->errorInfo[1] == 1062) {
            echo "Erro: Este e-mail ('$email') já está cadastrado.";
        } else {
            // Outro erro qualquer
            echo "Erro ao cadastrar o usuário: " . $e->getMessage();
        }
    }

} else {
    // Se alguém tentar acessar o arquivo direto pela URL
    echo "Acesso inválido.";
}
?>