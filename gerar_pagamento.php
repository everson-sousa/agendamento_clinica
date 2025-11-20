<?php
ob_start();
session_start();

$base_dir = __DIR__;
require_once $base_dir . '/conexao.php';
require_once $base_dir . '/vendor/autoload.php';

use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;

// 1. Verifica ID do plano e prioridade de pagamento
if (!isset($_GET['id_plano'])) {
    die("Erro: ID do plano não informado.");
}

$id_plano = (int) $_GET['id_plano'];
$prioridade_pagamento = $_GET['prioridade'] ?? 'pix'; // pix ou card

// 2. Busca dados do plano e o token do profissional
$sql = "SELECT plan.*, pac.nome_completo, usr.mp_access_token, plan.valor
        FROM planos_paciente AS plan
        JOIN pacientes AS pac ON plan.id_paciente = pac.id
        JOIN usuarios AS usr ON plan.id_profissional = usr.id
        WHERE plan.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_plano]);
$plano = $stmt->fetch();

// 3. Validações
if (!$plano) {
    die("Erro: Plano não encontrado.");
}

if (empty($plano['mp_access_token'])) {
    die("Erro: O profissional não configurou o Access Token do Mercado Pago.");
}

$valor_cobranca = (float) $plano['valor'];
if ($valor_cobranca <= 0) {
    die("Erro: O valor deste plano é zero. Não é possível gerar pagamento.");
}

// ⚠️ 4. Inicializa o Mercado Pago (ESTE ERA O PONTO QUE FALTAVA!)
SDK::setAccessToken($plano['mp_access_token']);
// SDK::setIntegratorId("dev_123456"); // opcional

try {

    // 5. Criar a preferência
    $preference = new Preference();

    $item = new Item();
    $item->title = "Pacote: " . ucfirst($plano['tipo_atendimento']) . " - " . $plano['nome_completo'];
    $item->quantity = 1;
    $item->unit_price = $valor_cobranca;

    $preference->items = [$item];

    // 6. Política de prioridade (Pix / Cartão)
    $excluded_types = [];
    $installments = 1;

    if ($prioridade_pagamento === 'pix') {
        // Excluir cartão e boleto → só PIX
        $excluded_types = [
            ["id" => "credit_card"],
            ["id" => "debit_card"],
            ["id" => "ticket"]
        ];

    } elseif ($prioridade_pagamento === 'card') {
        // Excluir PIX → só cartão
        $excluded_types = [
            ["id" => "ticket"],
            ["id" => "bank_transfer"] // PIX
        ];
        $installments = 3; // pode ajustar
    }

    $preference->payment_methods = [
        "excluded_payment_types" => $excluded_types,
        "installments" => $installments
    ];

    // 7. URLs de retorno
    $base_url = "http://localhost/clinica";
    $preference->back_urls = [
        "success" => $base_url . "/ver_planos.php",
        "failure" => $base_url . "/ver_planos.php",
        "pending" => $base_url . "/ver_planos.php"
    ];

    // 8. Salva no Mercado Pago
    $preference->save();

    // 9. Redireciona para o checkout
    ob_end_clean();
    header("Location: " . $preference->init_point);
    exit;

} catch (Exception $e) {
    die("Erro ao gerar pagamento. Detalhe: " . $e->getMessage());
}
?>
