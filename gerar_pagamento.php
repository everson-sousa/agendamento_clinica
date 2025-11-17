<?php
session_start();

// Define o caminho base do sistema (Evita erros de pasta no Windows/XAMPP)
$base_dir = __DIR__;

// 1. Carrega dependências
require_once $base_dir . '/conexao.php';
$autoload_path = $base_dir . '/vendor/autoload.php';

// Verifica se o autoloader existe
if (!file_exists($autoload_path)) {
    die("ERRO CRÍTICO: O arquivo 'vendor/autoload.php' não foi encontrado.<br>Execute 'composer require mercadopago/dx-php:2.5.3' no terminal.");
}
require_once $autoload_path;

// Importa as classes (Essencial para funcionar)
use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;

// 2. Pega o ID do plano
if (!isset($_GET['id_plano'])) { 
    die("Erro: ID do plano não informado."); 
}
$id_plano = $_GET['id_plano'];

// 3. Busca dados do plano e do profissional
$sql = "SELECT plan.*, pac.nome_completo, usr.mp_access_token 
        FROM planos_paciente AS plan
        JOIN pacientes AS pac ON plan.id_paciente = pac.id
        JOIN usuarios AS usr ON plan.id_profissional = usr.id
        WHERE plan.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_plano]);
$plano = $stmt->fetch();

if (!$plano) {
    die("Plano não encontrado.");
}

if (empty($plano['mp_access_token'])) {
    die("<h3 style='color:red'>Erro: Configuração Financeira Incompleta</h3>
         <p>O profissional responsável ainda não configurou o Token do Mercado Pago.</p>
         <p>Acesse 'Config. Financeira' no menu e salve suas credenciais de produção.</p>");
}

// 4. Gera o Pagamento
try {
    // Configura o Token
    SDK::setAccessToken($plano['mp_access_token']);

    // Cria o Carrinho
    $preference = new Preference();
    $item = new Item();
    $item->title = "Pacote: " . ucfirst($plano['tipo_atendimento']) . " - " . $plano['nome_completo'];
    $item->quantity = 1;
    $item->unit_price = 150.00;
    $preference->items = [$item];
    
    // Tenta salvar
    $preference->save();

    // --- ÁREA DE DEBUG ---
    echo "<h3>Debug do Mercado Pago:</h3>";
    
    if ($preference->init_point) {
        echo "<p style='color:green'>SUCESSO! Link gerado:</p>";
        echo "<a href='" . $preference->init_point . "'>" . $preference->init_point . "</a>";
        // Se quiser testar o redirecionamento, descomente a linha abaixo:
        // header("Location: " . $preference->init_point);
    } else {
        echo "<p style='color:red'>ERRO: O link não foi gerado.</p>";
        echo "<pre>";
        // Mostra qualquer erro que o Mercado Pago tenha devolvido (ex: erro SSL)
        print_r($preference); 
        echo "</pre>";
    }
    // --------------------

} catch (Exception $e) {
    die("Erro Fatal (Exception): " . $e->getMessage());
}
?>