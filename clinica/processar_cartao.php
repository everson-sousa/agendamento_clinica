<?php
// processar_cartao.php
// 1. Configurações para forçar resposta JSON mesmo com erro
ini_set('display_errors', 0); // Evita que erros do PHP quebrem o JSON
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    // 2. Verifica se o arquivo de conexão existe antes de incluir
    if (!file_exists('conexao.php')) {
        throw new Exception("Arquivo 'conexao.php' não encontrado na pasta.");
    }
    require_once 'conexao.php';

    // 3. Recebe os dados
    $input = file_get_contents("php://input");
    $request = json_decode($input, true);

    if (!$request) {
        throw new Exception("O servidor não recebeu dados. Verifique o Javascript.");
    }

    // 4. Busca token de acesso
    session_start();
    $id_usuario = $_SESSION['usuario_id'] ?? 1; // Padrão ID 1 se não tiver sessão

    // Verifica se a tabela usuarios e coluna mp_access_token existem
    $stmt = $pdo->prepare("SELECT mp_access_token FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$id_usuario]);
    $user_config = $stmt->fetch();

    if (!$user_config || empty($user_config['mp_access_token'])) {
        throw new Exception("Access Token não encontrado no banco para o usuário ID $id_usuario.");
    }

    $access_token = $user_config['mp_access_token'];

    // 5. Prepara dados para o Mercado Pago
    $dados_pagamento = [
        "transaction_amount" => (float)$request['transaction_amount'],
        "description" => "Sessão de Terapia",
        "payment_method_id" => $request['payment_method_id'],
        "payer" => [
            "email" => $request['payer']['email'] ?? 'email@teste.com',
            "identification" => [
                "type" => $request['payer']['identification']['type'] ?? 'CPF',
                "number" => $request['payer']['identification']['number'] ?? ''
            ]
        ],
        "external_reference" => $request['id_agendamento'] ?? null
    ];

    // Se for cartão, adiciona token
    if ($request['payment_method_id'] != 'pix') {
        if (empty($request['token'])) throw new Exception("Token do cartão não gerado.");
        $dados_pagamento["token"] = $request['token'];
        $dados_pagamento["installments"] = (int)$request['installments'];
        $dados_pagamento["issuer_id"] = (int)$request['issuer_id'];
    }

    // 6. Envia cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados_pagamento));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $access_token,
        "Content-Type: application/json",
        "X-Idempotency-Key: " . uniqid('pay_', true)
    ]);

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro de conexão cURL: ' . curl_error($ch));
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 7. Responde ao Javascript
    $mp_response = json_decode($response, true);

    if ($http_code == 201 && isset($mp_response['status']) && $mp_response['status'] == 'approved') {
        
        // Atualiza banco
        if(!empty($request['id_agendamento'])){
             $update = $pdo->prepare("UPDATE agendamentos SET status_pagamento = 'Pago' WHERE id = ?");
             $update->execute([$request['id_agendamento']]);
        }
        echo json_encode(['status' => 'approved', 'id' => $mp_response['id']]);

    } elseif ($http_code == 201 && $request['payment_method_id'] == 'pix') {
        
        echo json_encode([
            'status' => 'pix_created', 
            'qr_code' => $mp_response['point_of_interaction']['transaction_data']['qr_code'],
            'qr_code_base64' => $mp_response['point_of_interaction']['transaction_data']['qr_code_base64']
        ]);

    } else {
        // Pega mensagem detalhada do erro
        $msg = $mp_response['message'] ?? 'Erro desconhecido do Mercado Pago';
        if(isset($mp_response['cause'][0]['description'])){
            $msg .= " - " . $mp_response['cause'][0]['description'];
        }
        echo json_encode(['status' => 'error', 'message' => $msg]);
    }

} catch (Exception $e) {
    // AQUI ESTÁ A MÁGICA: Captura o erro fatal e devolve como JSON
    echo json_encode(['status' => 'error', 'message' => 'Erro no Servidor: ' . $e->getMessage()]);
}
?>