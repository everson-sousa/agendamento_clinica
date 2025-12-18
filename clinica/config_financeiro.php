<?php
$tituloPagina = "Configuração Financeira";
require_once 'header.php';
require_once 'conexao.php';

$id_usuario = $_SESSION['usuario_id'];

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['mp_access_token'];
    $key = $_POST['mp_public_key'];
    
    $sql = "UPDATE usuarios SET mp_access_token = ?, mp_public_key = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token, $key, $id_usuario]);
    
    echo "<p style='color:green'>Dados atualizados com sucesso!</p>";
}

// Busca dados atuais
$sql = "SELECT mp_access_token, mp_public_key FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_usuario]);
$dados = $stmt->fetch();
?>

<h3>Configuração do Mercado Pago</h3>
<p>Para receber pagamentos diretamente na sua conta, insira suas credenciais abaixo.</p>
<p><small>Você encontra isso em: <a href="https://www.mercadopago.com.br/developers/panel" target="_blank">Seu Painel de Desenvolvedor</a> (Crie uma aplicação e pegue as "Credenciais de Produção").</small></p>

<form method="POST">
    <div>
        <label>Access Token (Produção):</label>
        <input type="text" name="mp_access_token" value="<?php echo htmlspecialchars($dados['mp_access_token'] ?? ''); ?>" required>
    </div>
    <div>
        <label>Public Key (Produção):</label>
        <input type="text" name="mp_public_key" value="<?php echo htmlspecialchars($dados['mp_public_key'] ?? ''); ?>" required>
    </div>
    <button type="submit">Salvar Configurações</button>
</form>

<?php require_once 'footer.php'; ?>