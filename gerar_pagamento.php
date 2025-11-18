<?php
// Inicia o buffer para evitar erros de redirecionamento
ob_start();
session_start();

// Define caminhos absolutos
$base_dir = __DIR__;
require_once $base_dir . '/conexao.php';
$autoload_path = $base_dir . '/vendor/autoload.php';

// Verifica se o autoload existe
if (!file_exists($autoload_path)) {
    die("Erro: Autoload não encontrado.");
}
require_once $autoload_path;

use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;

// 1. Verifica ID do plano
if (!isset($_GET['id_plano'])) { die("ID do plano não informado."); }
$id_plano = $_GET['id_plano'];

// 2. Busca dados no banco
$sql = "SELECT plan.*, pac.nome_completo, usr.mp_access_token 
        FROM planos_paciente AS plan
        JOIN pacientes AS pac ON plan.id_paciente = pac.id
        JOIN usuarios AS usr ON plan.id_profissional = usr.id
        WHERE plan.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_plano]);
$plano = $stmt->fetch();

// 3. Validações
if (!$plano || empty($plano['mp_access_token'])) {
    die("Erro: Configuração financeira incompleta ou plano não encontrado.");
}

$valor_cobranca = (float) $plano['valor'];
if ($valor_cobranca <= 0) {
    die("Erro: O valor deste plano é zero.");
}

// 4. Gera o Pagamento
try {
    // Configura o Token
    SDK::setAccessToken($plano['mp_access_token']);

    // Cria a Preferência
    $preference = new Preference();
    $item = new Item();
    $item->title = "Pacote: " . ucfirst($plano['tipo_atendimento']) . " - " . $plano['nome_completo'];
    $item->quantity = 1;
    $item->unit_price = $valor_cobranca;
    
    $preference->items = [$item];

    // Configura retorno
    $base_url = "http://localhost/clinica"; 
    $preference->back_urls = array(
        "success" => $base_url . "/ver_planos.php?status=aprovado",
        "failure" => $base_url . "/ver_planos.php?status=falha",
        "pending" => $base_url . "/ver_planos.php?status=pendente"
    );
    $preference->auto_return = "approved";

    // Salva no Mercado Pago
    $preference->save();

    // Redireciona o usuário
    ob_clean();
    header("Location: " . $preference->init_point);
    exit;

} catch (Exception $e) {
    // Se cair aqui, mostra o erro na tela
    die("Erro ao conectar com Mercado Pago: " . $e->getMessage());
}
?>