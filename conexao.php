<?php

// 1. Configurações do Banco de Dados
$host = '127.0.0.1';     // Ou 'localhost'
$db_name = 'cadastro';   // O nome do seu banco de dados
$db_user = 'root';       // Usuário padrão do XAMPP
$db_pass = '';           // Senha padrão do XAMPP (vazia)
$charset = 'utf8mb4';    // O charset que definimos no banco

// 2. String de Conexão (DSN)
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

// 3. Opções do PDO (Opcional, mas recomendado)
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erros
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Retorna resultados como array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                // Usa "prepared statements" reais
];

// 4. Tenta a Conexão
try {
     $pdo = new PDO($dsn, $db_user, $db_pass, $options);
     // Se você quiser testar, pode descomentar a linha abaixo:
     // echo "Conexão com o banco '$db_name' realizada com sucesso!";

} catch (\PDOException $e) {
     // Se a conexão falhar, mostra o erro e para o script
     echo "Erro ao conectar ao banco de dados: " . $e->getMessage();
     exit;
}
?>