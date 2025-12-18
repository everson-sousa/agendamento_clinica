<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403);
    exit;
}
require_once 'conexao.php';
header('Content-Type: application/json');

$id_profissional = $_GET['id_profissional'];

if (empty($id_profissional)) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT id_paciente, tipo_plano, tipo_atendimento, sessoes_contratadas, sessoes_utilizadas 
            FROM planos_paciente 
            WHERE id_profissional = ? AND status = 'Ativo'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_profissional]);
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($planos);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>