<?php
// --- 1. CONFIGURAÇÃO DE ERROS E BUFFER ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start(); 
session_start();

// 2. DEFINIÇÃO DE CAMINHOS
$base_dir = __DIR__;
require_once $base_dir . '/conexao.php';
require_once $base_dir . '/vendor/autoload.php'; // Carrega o Composer/Mercado Pago

// Importa as classes
use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;

// 3. Verifica ID do plano
if (!isset($_GET['id_plano'])) { 
    die("Erro: ID do plano não informado."); 
}
$id_plano = $_GET['id_plano'];

// 4. Busca dados do plano e as CHAVES DO PROFISSIONAL (Linhas que estavam com erro antes)
$sql = "SELECT plan.*, pac.nome_completo, usr.mp_access_token 
        FROM planos_paciente AS plan
        JOIN pacientes AS pac ON plan.id_paciente = pac.id
        JOIN usuarios AS usr ON plan.id_profissional = usr.id
        WHERE plan.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_plano]);
$plano = $stmt->fetch();

// 5. Validações de Segurança e Valor
if (!$plano || empty($plano['mp_access_token'])) {
    die("Erro: Profissional não configurou o Access Token no sistema.");
}
$valor_cobranca = (float) $plano['valor'];
if ($valor_cobranca <= 0) {
    die("Erro: O valor deste plano é zero. Não é possível gerar pagamento.");
}

// 6. Gera o Pagamento
try {
        SDK::setAccessToken($plano['mp_access_token']);

        $preference = new Preference();
        $item = new Item();
        $item->title = "Pacote: " . ucfirst($plano['tipo_atendimento']) . " - " . $plano['nome_completo'];
        $item->quantity = 1;
        $item->unit_price = $valor_cobranca; 
        
        $preference->items = [$item];

        // *** BLOCO DE URLS DE RETORNO REMOVIDO PARA EVITAR VALIDAÇÃO ***
        // O código de redirecionamento final fará o trabalho.

        // Salva no Mercado Pago
        $preference->save();

        // 5. Redireciona o usuário para o Checkout
        ob_end_clean(); // Finaliza o buffer
        header("Location: " . $preference->init_point);
        exit;

    } catch (Exception $e) {
        die("Erro Fatal ao gerar pagamento. Verifique o Access Token. Detalhe: " . $e->getMessage());
}

?>