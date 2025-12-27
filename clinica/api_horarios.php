<?php
// api_horarios.php
require_once 'conexao.php';
header('Content-Type: application/json');

$id_profissional = $_GET['id_profissional'] ?? 0;

// Busca horários futuros e livres
$sql = "SELECT id, data_hora_inicio, data_hora_fim 
        FROM horarios_disponiveis 
        WHERE id_profissional = ? 
        AND status = 'livre' 
        AND data_hora_inicio >= NOW()";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_profissional]);
$horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$eventos = [];
foreach($horarios as $h) {
    $eventos[] = [
        'id' => $h['id'],
        'title' => 'Disponível',
        'start' => $h['data_hora_inicio'],
        'end' => $h['data_hora_fim'],
        'className' => 'slot-livre',
        'extendedProps' => [ 'status' => 'livre' ]
    ];
}
echo json_encode($eventos);
?>