<?php
// sucesso.php
// Exibe a confirmação após o pagamento

require_once 'conexao.php';

$id_pagamento = $_GET['id'] ?? null;

if (!$id_pagamento) {
    die("Erro: ID do pagamento não informado.");
}

try {
    // Busca os detalhes do agendamento, serviço e horário
    // Fazendo JOIN para pegar tudo de uma vez
    $sql = "SELECT a.*, s.nome_servico, s.quantidade_sessoes, h.data_hora_inicio, u.nome as paciente_nome
            FROM agendamentos a
            JOIN servicos s ON a.id_servico = s.id
            JOIN horarios_disponiveis h ON a.id_slot = h.id
            JOIN usuarios u ON a.id_usuario = u.id
            WHERE a.mp_id = ? 
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_pagamento]);
    $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agendamento) {
        die("Agendamento não encontrado para este pagamento.");
    }

    // Formata data e hora para exibir
    $data_timestamp = strtotime($agendamento['data_hora_inicio']);
    $data_formatada = date('d/m/Y', $data_timestamp);
    $hora_formatada = date('H:i', $data_timestamp);

} catch (PDOException $e) {
    die("Erro no banco: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento Confirmado!</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f6f9; display: flex; justify-content: center; padding: 30px 20px; }
        .card { background: white; max-width: 500px; width: 100%; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); padding: 40px 30px; text-align: center; }
        
        .icon-success { color: #27ae60; font-size: 60px; margin-bottom: 10px; }
        h1 { color: #2c3e50; margin: 10px 0 5px; font-size: 24px; }
        p.subtitle { color: #7f8c8d; font-size: 16px; margin-bottom: 30px; }

        .details { background: #f8f9fa; border-radius: 10px; padding: 20px; text-align: left; margin-bottom: 30px; border: 1px solid #e9ecef; }
        .row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; }
        .row:last-child { margin-bottom: 0; }
        .label { color: #666; font-weight: 600; }
        .value { color: #333; font-weight: bold; }

        .btn { display: inline-block; background: #2f80ed; color: white; text-decoration: none; padding: 12px 30px; border-radius: 8px; font-weight: bold; transition: background 0.2s; }
        .btn:hover { background: #1d66c9; }
    </style>
</head>
<body>

<div class="card">
    <div class="icon-success">✓</div>
    <h1>Agendamento Confirmado!</h1>
    <p class="subtitle">Obrigado, <?php echo htmlspecialchars($agendamento['paciente_nome']); ?>. Seu horário está reservado.</p>

    <div class="details">
        <div class="row">
            <span class="label">Serviço:</span>
            <span class="value"><?php echo htmlspecialchars($agendamento['nome_servico']); ?></span>
        </div>
        <div class="row">
            <span class="label">Data:</span>
            <span class="value"><?php echo $data_formatada; ?></span>
        </div>
        <div class="row">
            <span class="label">Horário:</span>
            <span class="value"><?php echo $hora_formatada; ?></span>
        </div>
        <div class="row">
            <span class="label">Status:</span>
            <span class="value" style="color:#27ae60; text-transform: uppercase;">
                <?php echo htmlspecialchars($agendamento['status']); ?>
            </span>
        </div>
        <div class="row">
            <span class="label">Código do Pagamento:</span>
            <span class="value">#<?php echo $id_pagamento; ?></span>
        </div>
    </div>

    <a href="index.php" class="btn">Voltar ao Início</a>
</div>

</body>
</html>