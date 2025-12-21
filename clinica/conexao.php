<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
// conexao.php

$host = "localhost";
$db_name = "cadastro";
$db_user = "root";
$db_pass = "";
$charset = "utf8mb4";

// $host = "sql210.infinityfree.com";
// $db   = "if0_40715905_clinica";
// $user = "if0_40715905";
// $pass = "cymD88n2UYY";


$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Erro ao conectar ao banco: " . $e->getMessage());
}
?>