<?php
require 'conexao.php'; // PDO já configurado

// Captura o payload
$payload = json_decode(file_get_contents("php://input"), true);

// Verificação básica
if (!isset($payload['data']['id'])) {
    http_response_code(400);
    exit('ID do pagamento não encontrado');
}

$payment_id = $payload['data']['id'];

// Token de produção
$access_token = 'SEU_ACCESS_TOKEN_PRODUCAO';

// Consulta o pagamento direto na API do Mercado Pago
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.mercadopago.com/v1/payments/$payment_id",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $access_token"
    ]
]);

$response = curl_exec($curl);
curl_close($curl);

$payment = json_decode($response, true);

// Validação do pagamento
if (
    $payment['status'] === 'approved'
) {

    $agendamento_id = $payment['external_reference']; 
    // external_reference deve conter o ID do agendamento

    $pdo->beginTransaction();

    // Atualiza pagamento
    $stmt = $pdo->prepare("
        UPDATE pagamentos 
        SET status = 'pago', mp_payment_id = ?
        WHERE agendamento_id = ?
    ");
    $stmt->execute([$payment_id, $agendamento_id]);

    // Atualiza agendamento
    $stmt = $pdo->prepare("
        UPDATE agendamentos 
        SET status_pagamento = 'Pago'
        WHERE id = ?
    ");
    $stmt->execute([$agendamento_id]);

    $pdo->commit();

    http_response_code(200);
    echo "Pagamento confirmado";
    exit;
}

http_response_code(200);
echo "Pagamento ainda não aprovado";
