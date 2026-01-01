<?php
// processar_cartao.php
// CORRIGIDO: Nomes de colunas compatíveis com seu banco (telefone, tipo_acesso)

header('Content-Type: application/json');
require_once 'conexao.php';

// Lê o JSON recebido do JavaScript
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos ou vazios']);
    exit;
}

try {
    // 1. Busca Token de Acesso SEMPRE DO ADMIN (ID 1)
    // Isso garante que o dinheiro vá para a SUA conta
    $stmt = $pdo->query("SELECT mp_access_token FROM usuarios WHERE id = 1 LIMIT 1");
    $token = $stmt->fetchColumn();

    if (!$token) {
        throw new Exception("Configuração de pagamento incompleta (Token do Admin ID 1 não encontrado).");
    }

    // 2. Prepara dados para a API do Mercado Pago
    $payment_data = [
        "transaction_amount" => (float)$data['transaction_amount'],
        "token"              => $data['token'] ?? null,
        "description"        => "Agendamento Psicologia",
        "installments"       => (int)$data['installments'],
        "payment_method_id"  => $data['payment_method_id'],
        "issuer_id"          => $data['issuer_id'] ?? null,
        "payer" => [
            "email" => $data['payer']['email'],
            "identification" => [
                "type" => $data['payer']['identification']['type'] ?? 'CPF',
                "number" => $data['payer']['identification']['number'] ?? $data['agendamento']['cpf']
            ]
        ]
    ];

    // Ajuste para PIX (não envia token de cartão)
    if ($data['payment_method_id'] === 'pix') {
        unset($payment_data['token'], $payment_data['issuer_id'], $payment_data['installments']);
    }

    // 3. Envia para Mercado Pago via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json",
        "X-Idempotency-Key: " . uniqid()
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $mp_res = json_decode($response, true);

    // 4. Verifica se o MP aceitou
    if ($http_code != 200 && $http_code != 201) {
        $msg = $mp_res['message'] ?? 'Erro desconhecido no Mercado Pago';
        throw new Exception($msg);
    }

    $status = $mp_res['status']; // approved, pending, rejected
    $id_pagamento_mp = $mp_res['id'];

    // 5. Se Aprovado ou Pendente, salva no banco
    if ($status === 'approved' || $status === 'pending') {
        
        $ag = $data['agendamento'];

        // A) Verifica/Cria Paciente (Usuario)
        // Busca usuário pelo e-mail
        $stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $stmtUser->execute([$ag['email']]);
        $id_paciente = $stmtUser->fetchColumn();

        if (!$id_paciente) {
            // Cria novo usuário (Senha provisória = CPF)
            $senha_temp = password_hash($ag['cpf'], PASSWORD_DEFAULT);
            
            // --- CORREÇÃO AQUI: Usando os nomes da sua tabela ---
            // 'telefone' em vez de whatsapp
            // 'tipo_acesso' em vez de nivel
            $stmtNew = $pdo->prepare("INSERT INTO usuarios (nome, email, cpf, telefone, senha, tipo_acesso, status) VALUES (?, ?, ?, ?, ?, 'cliente', 'ativo')");
            
            $stmtNew->execute([
                $ag['nome'], 
                $ag['email'], 
                $ag['cpf'], 
                $ag['whatsapp'], // O valor vem do JS como 'whatsapp', mas salvamos em 'telefone'
                $senha_temp
            ]);
            
            $id_paciente = $pdo->lastInsertId();
        }

        // B) Cria o Agendamento
        $stmtAg = $pdo->prepare("INSERT INTO agendamentos (id_slot, id_usuario, id_profissional, id_servico, status, mp_id, valor_pago) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $status_interno = ($status === 'approved') ? 'confirmado' : 'aguardando_pix';
        
        $stmtAg->execute([
            $ag['id_slot'],
            $id_paciente,
            $ag['id_profissional'], // ID da Silvia (para agenda)
            $ag['id_servico'],
            $status_interno,
            $id_pagamento_mp,
            $data['transaction_amount']
        ]);

        // C) Atualiza Slot para 'reservado' (apenas se aprovado na hora)
        if ($status === 'approved') {
            $pdo->prepare("UPDATE horarios_disponiveis SET status = 'reservado' WHERE id = ?")->execute([$ag['id_slot']]);
        }
    }

    // Retorna JSON para o Frontend
    $retorno = [
        'status' => $status,
        'id_pagamento' => $id_pagamento_mp,
        'payment_method_id' => $data['payment_method_id']
    ];

    // Se for Pix, manda o QR Code
    if ($data['payment_method_id'] === 'pix' && isset($mp_res['point_of_interaction'])) {
        $qr_data = $mp_res['point_of_interaction']['transaction_data'];
        $retorno['qr_code'] = $qr_data['qr_code'];
        $retorno['qr_code_base64'] = $qr_data['qr_code_base64'];
    }

    echo json_encode($retorno);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>