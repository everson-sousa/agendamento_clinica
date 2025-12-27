<?php
// reservar_horario.php
require_once 'conexao.php';

$id_slot = $_GET['id_slot'] ?? 0;

// 1. Busca detalhes do horário clicado
$stmt = $pdo->prepare("SELECT h.*, u.nome as nome_profissional, u.id as id_profissional 
                       FROM horarios_disponiveis h 
                       JOIN usuarios u ON h.id_profissional = u.id 
                       WHERE h.id = ? AND h.status = 'livre'");
$stmt->execute([$id_slot]);
$slot = $stmt->fetch();

if (!$slot) {
    die("<h3>Ops! Este horário não está mais disponível.</h3><a href='javascript:history.back()'>Voltar e escolher outro</a>");
}

// 2. Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $whatsapp = $_POST['whatsapp']; 
    $email = $_POST['email']; 
    $cpf = $_POST['cpf']; // VOLTAMOS COM O CPF AQUI
    $tipo_atendimento = $_POST['tipo_atendimento'];
    
    // Limpa o CPF (deixa só números) para evitar erros no banco
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf);

    try {
        $pdo->beginTransaction();

        // A) Verifica se o paciente já existe (pelo CPF ou Email para garantir)
        $stmt_pac = $pdo->prepare("SELECT id FROM pacientes WHERE cpf = ? OR email = ?");
        $stmt_pac->execute([$cpf_limpo, $email]);
        $paciente_existente = $stmt_pac->fetch();

        if ($paciente_existente) {
            $id_paciente = $paciente_existente['id'];
        } else {
            // B) Cria novo paciente COM CPF
            $stmt_novo = $pdo->prepare("INSERT INTO pacientes (nome_completo, telefone, email, cpf, id_profissional_responsavel, status) VALUES (?, ?, ?, ?, ?, 'ativo')");
            $stmt_novo->execute([$nome, $whatsapp, $email, $cpf_limpo, $slot['id_profissional']]);
            $id_paciente = $pdo->lastInsertId();
        }

        // C) Cria o Agendamento "Pendente"
        $sql_agenda = "INSERT INTO agendamentos 
                      (id_profissional, id_paciente, data_hora_inicio, data_hora_fim, tipo_atendimento, status, status_pagamento, observacoes) 
                      VALUES (?, ?, ?, ?, ?, 'marcado', 'Pendente', 'Agendamento Online')";
        $stmt_agenda = $pdo->prepare($sql_agenda);
        $stmt_agenda->execute([
            $slot['id_profissional'],
            $id_paciente,
            $slot['data_hora_inicio'],
            $slot['data_hora_fim'],
            $tipo_atendimento
        ]);
        $id_agendamento_criado = $pdo->lastInsertId();

        // D) Marca o slot como "reservado"
        $pdo->prepare("UPDATE horarios_disponiveis SET status = 'reservado' WHERE id = ?")->execute([$id_slot]);

        $pdo->commit();

        // E) Redireciona para o Pagamento
        header("Location: checkout_cartao.php?id_agendamento=" . $id_agendamento_criado);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao processar: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Agendamento</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { margin-top: 0; color: #333; text-align: center; }
        .info-box { background: #e9f7ef; padding: 15px; border-radius: 5px; color: #155724; margin-bottom: 20px; font-size: 0.9em; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; font-size: 1.1em; cursor: pointer; font-weight: bold; }
        button:hover { background: #218838; }
        .lgpd-note { font-size: 0.8em; color: #777; margin-top: 10px; text-align: center; }
    </style>
</head>
<body>

<div class="card">
    <h2>Finalizar Reserva</h2>
    
    <div class="info-box">
        <strong>Profissional:</strong> <?php echo htmlspecialchars($slot['nome_profissional']); ?><br>
        <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($slot['data_hora_inicio'])); ?><br>
        <strong>Horário:</strong> <?php echo date('H:i', strtotime($slot['data_hora_inicio'])); ?>
    </div>

    <form method="POST">
        <label>Seu Nome Completo:</label>
        <input type="text" name="nome" required placeholder="Ex: Maria da Silva" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">

        <label>Seu E-mail:</label>
        <input type="email" name="email" required placeholder="maria@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

        <label>Seu CPF (para Nota Fiscal):</label>
        <input type="text" name="cpf" required placeholder="000.000.000-00" maxlength="14">

        <label>Seu WhatsApp:</label>
        <input type="text" name="whatsapp" required placeholder="(11) 99999-9999" value="<?php echo isset($_POST['whatsapp']) ? htmlspecialchars($_POST['whatsapp']) : ''; ?>">

        <label>Tipo de Atendimento:</label>
        <select name="tipo_atendimento" required>
            <option value="terapia">Sessão de Terapia</option>
            <option value="avaliacao">Avaliação Psicológica</option>
            <option value="plantao">Plantão Psicológico</option>
        </select>

        <button type="submit">Continuar para Pagamento ➤</button>
        
        <p class="lgpd-note">🔒 Seus dados são usados estritamente para identificação fiscal e confirmação do agendamento.</p>
    </form>
    
    <div style="text-align:center; margin-top:15px;">
        <a href="javascript:history.back()" style="color: #666; text-decoration: none;">Cancelar</a>
    </div>
</div>

</body>
</html>