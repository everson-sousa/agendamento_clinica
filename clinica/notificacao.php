<?php
// notificacao.php
// Recebe avisos do Mercado Pago sobre PIX e Cartão

require_once 'conexao.php';

// 1. Captura o aviso enviado pelo Mercado Pago
$json_event = file_get_contents('php://input');
$event = json_decode($json_event, true);

// 2. Verifica se é uma notificação de pagamento
if (isset($event['type']) && $event['type'] == 'payment') {
    $payment_id = $event['data']['id'];

    // 3. Busca o Token de Produção (Admin ID 1)
    $stmt = $pdo->prepare("SELECT mp_access_token FROM usuarios WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $config = $stmt->fetch();
    $access_token = $config['mp_access_token'] ?? '';

    if ($access_token) {
        // 4. Pergunta ao Mercado Pago: "Esse pagamento foi aprovado mesmo?"
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/" . $payment_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $access_token
        ]);
        $response = curl_exec($ch);
        $payment_info = json_decode($response, true);
        curl_close($ch);

        // 5. Se aprovado, atualiza o banco (CORREÇÃO APLICADA AQUI)
        if (isset($payment_info['status']) && $payment_info['status'] == 'approved') {
            $id_agendamento = $payment_info['external_reference'];
            
            if(!empty($id_agendamento)) {
                // Aqui usamos o nome correto da coluna que descobrimos: status_pagamento
                $update = $pdo->prepare("UPDATE agendamentos SET status_pagamento = 'Pago' WHERE id = ?");
                $update->execute([$id_agendamento]);
            }
        }
    }
}

// Responde "OK" para o Mercado Pago não ficar insistindo
http_response_code(200);
?>