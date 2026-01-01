<?php
// reservar_horario.php
// CÓDIGO COMPLETO - CORRIGIDO PARA A TABELA 'horarios_disponiveis'

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';

// --- CONFIGURAÇÕES ---
$CHECKOUT_ACTION = 'checkout_cartao.php'; 

// --- 1. RECEBE OS DADOS DA URL ---
$id_slot = $_GET['id_slot'] ?? null;
// Aceita id_servico via GET ou POST
$id_servico = $_GET['id_servico'] ?? ($_POST['id_servico'] ?? 0);

if (!$id_slot) {
    die("Erro: Falta o ID do horário (id_slot) na URL.");
}

// --- 2. BUSCA O HORÁRIO NO BANCO (Consulta Direta) ---
try {
    // Busca na tabela que vimos no seu print: 'horarios_disponiveis'
    $stmt = $pdo->prepare("SELECT * FROM horarios_disponiveis WHERE id = ? LIMIT 1");
    $stmt->execute([$id_slot]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        die("Erro: O horário (ID: $id_slot) não foi encontrado na tabela 'horarios_disponiveis'.");
    }
    
    // Verifica se o status é 'livre'
    if (isset($row['status']) && strtolower($row['status']) !== 'livre') {
         // Se quiser bloquear totalmente, descomente a linha abaixo:
         // die("Atenção: Este horário não está disponível. Status: " . htmlspecialchars($row['status']));
    }

} catch (PDOException $e) {
    die("Erro ao consultar banco de dados: " . $e->getMessage());
}

// --- 3. DADOS DO PROFISSIONAL ---
// Pega o ID do profissional da tabela de horários
$id_profissional = $row['id_profissional'];
$prof_nome = "Profissional ID " . $id_profissional;

// Tenta buscar o nome real na tabela de usuarios
try {
    $stmtProf = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ? LIMIT 1");
    $stmtProf->execute([$id_profissional]);
    $prof = $stmtProf->fetch(PDO::FETCH_ASSOC);
    if ($prof && !empty($prof['nome'])) {
        $prof_nome = $prof['nome'];
    }
} catch (Exception $e) {
    // Se der erro ao buscar nome, mantém o ID mesmo
}

// --- 4. FORMATAÇÃO DE DATA E HORA ---
// Usa a coluna 'data_hora_inicio' que vimos no phpMyAdmin (Ex: 2026-01-11 18:00:00)
$datetime_raw = $row['data_hora_inicio']; 
$timestamp = strtotime($datetime_raw);

// Cria as variáveis separadas que o sistema precisa
$data_iso = date('Y-m-d', $timestamp); // Para inputs hidden (2026-01-11)
$hora_iso = date('H:i:s', $timestamp); // Para inputs hidden (18:00:00)
$data_br  = date('d/m/Y', $timestamp); // Para exibir na tela (11/01/2026)
$hora_br  = date('H:i', $timestamp);   // Para exibir na tela (18:00)

// Cria o array $slot para facilitar o uso no HTML
$slot = [
    'id'                => $row['id'],
    'id_profissional'   => $id_profissional,
    'profissional_nome' => $prof_nome,
    'data_iso'          => $data_iso,
    'hora_iso'          => $hora_iso,
    'data_br'           => $data_br,
    'hora_br'           => $hora_br
];

// --- 5. DADOS DO SERVIÇO ---
$servicoSelecionado = null;
if ($id_servico > 0) {
    $stmtServ = $pdo->prepare("SELECT * FROM servicos WHERE id = ? LIMIT 1");
    $stmtServ->execute([$id_servico]);
    $servicoSelecionado = $stmtServ->fetch(PDO::FETCH_ASSOC);
}

// Carrega lista de serviços para o <select> caso o cliente não tenha escolhido ainda
$stmtLista = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY preco ASC");
$servicos = $stmtLista->fetchAll(PDO::FETCH_ASSOC);

// Função auxiliar para exibir texto do pacote
function pacote_texto($nome, $qtd) {
    $qtd = (int)$qtd;
    if ($qtd <= 1) return '';
    $isAvaliacao = (stripos($nome, 'avalia') !== false);
    return $isAvaliacao ? "Pacote com até {$qtd} Sessões" : "Pacote com {$qtd} Sessões";
}

// Define para onde o formulário vai (checkout)
$checkoutExists = file_exists(__DIR__ . '/' . $CHECKOUT_ACTION);
$formAction = $checkoutExists ? $CHECKOUT_ACTION : basename(__FILE__);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Finalizar Reserva</title>
  <style>
    body{font-family:Segoe UI,Arial,sans-serif;background:#f4f6f9;margin:0;padding:18px}
    .wrap{max-width:520px;margin:0 auto}
    .card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);padding:22px}
    h1{margin:0 0 14px;text-align:center;color:#2c3e50;font-size:22px}
    .resume{background:#e9f7ef;border-radius:10px;padding:12px 14px;margin:10px 0 18px;color:#145a32}
    .resume b{display:block;margin-bottom:2px}
    .field{margin:12px 0}
    label{display:block;font-weight:600;color:#333;margin-bottom:6px}
    input,select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:8px;font-size:14px;box-sizing:border-box}
    .serviceBox{border:1px solid #ddd;border-radius:10px;padding:12px;background:#f8f9fa;margin:8px 0 14px}
    .serviceTop{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
    .serviceName{font-weight:800;color:#2c3e50}
    .servicePrice{font-weight:900;color:#27ae60;white-space:nowrap}
    .serviceDesc{margin-top:6px;font-size:13px;color:#7f8c8d;line-height:1.35}
    .servicePack{margin-top:6px;font-size:13px;font-weight:800;color:#d35400}
    .hint{font-size:12px;color:#666;margin-top:6px;text-align:center}
    .btn{width:100%;padding:12px;border:0;border-radius:10px;background:#2f80ed;color:#fff;font-weight:800;font-size:15px;cursor:pointer;margin-top:10px}
    .btn:hover{background:#1d66c9}
  </style>
</head>
<body>

<div class="wrap">
  <div class="card">
    <h1>Finalizar Reserva</h1>

    <div class="resume">
      <b>Profissional:</b> <?php echo htmlspecialchars($slot['profissional_nome']); ?><br>
      <b>Data:</b> <?php echo htmlspecialchars($slot['data_br']); ?><br>
      <b>Horário:</b> <?php echo htmlspecialchars($slot['hora_br']); ?>
    </div>

    <form method="POST" action="<?php echo htmlspecialchars($formAction); ?>">
      
      <input type="hidden" name="id_slot" value="<?php echo htmlspecialchars((string)$slot['id']); ?>">
      <input type="hidden" name="id_profissional" value="<?php echo (int)$slot['id_profissional']; ?>">
      <input type="hidden" name="data" value="<?php echo htmlspecialchars($slot['data_iso']); ?>">
      <input type="hidden" name="hora" value="<?php echo htmlspecialchars($slot['hora_iso']); ?>">

      <?php if ($servicoSelecionado): ?>
        <label>Tipo de Atendimento:</label>
        <div class="serviceBox">
          <div class="serviceTop">
            <div class="serviceName"><?php echo htmlspecialchars($servicoSelecionado['nome_servico']); ?></div>
            <div class="servicePrice">R$ <?php echo number_format((float)$servicoSelecionado['preco'], 2, ',', '.'); ?></div>
          </div>

          <?php if (!empty($servicoSelecionado['descricao_comercial'])): ?>
            <div class="serviceDesc"><?php echo htmlspecialchars($servicoSelecionado['descricao_comercial']); ?></div>
          <?php endif; ?>

          <?php $pack = pacote_texto($servicoSelecionado['nome_servico'], $servicoSelecionado['quantidade_sessoes']); ?>
          <?php if ($pack): ?>
            <div class="servicePack"><?php echo htmlspecialchars($pack); ?></div>
          <?php endif; ?>
        </div>

        <input type="hidden" name="id_servico" value="<?php echo (int)$servicoSelecionado['id']; ?>">
        <input type="hidden" name="tipo_atendimento" value="<?php echo htmlspecialchars($servicoSelecionado['nome_servico']); ?>">
        <input type="hidden" name="preco" value="<?php echo htmlspecialchars((string)$servicoSelecionado['preco']); ?>">

      <?php else: ?>
        <div class="field">
          <label>Tipo de Atendimento:</label>
          <select name="id_servico" required>
            <option value="" disabled selected>Selecione um serviço…</option>
            <?php foreach ($servicos as $s): ?>
              <option value="<?php echo (int)$s['id']; ?>">
                <?php echo htmlspecialchars($s['nome_servico']); ?> — R$ <?php echo number_format((float)$s['preco'], 2, ',', '.'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>

      <div class="field">
        <label>Seu Nome Completo:</label>
        <input type="text" name="nome" placeholder="Ex: Maria da Silva" required>
      </div>

      <div class="field">
        <label>Seu E-mail:</label>
        <input type="email" name="email" placeholder="maria@email.com" required>
      </div>

      <div class="field">
        <label>Seu CPF (para Nota Fiscal):</label>
        <input type="text" name="cpf" placeholder="000.000.000-00" required>
      </div>

      <div class="field">
        <label>Seu WhatsApp:</label>
        <input type="text" name="whatsapp" placeholder="(11) 99999-9999" required>
      </div>

      <div class="hint">
        Ao clicar em confirmar, você será redirecionado para o pagamento.
      </div>

      <button type="submit" class="btn">Confirmar Agendamento</button>
      
      <a href="javascript:history.back()" class="cancel" style="display:block;text-align:center;margin-top:15px;color:#666;text-decoration:none">Cancelar</a>

    </form>
  </div>
</div>

</body>
</html>