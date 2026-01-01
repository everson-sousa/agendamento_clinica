<?php
// processa_agendamento.php - Versão "Guest" (Sem Login)
session_start();
require_once 'conexao.php';

// 1. Recebendo dados da URL
$id_profissional = $_GET['p'] ?? null;
$id_servico      = $_GET['s'] ?? null;
$data_inicio     = $_GET['d'] ?? null;
$hora_inicio     = $_GET['h'] ?? null;

if (!$id_profissional || !$id_servico || !$data_inicio || !$hora_inicio) {
    die("Erro: Dados incompletos. Volte ao calendário.");
}

try {
    // 2. Buscar Serviço
    $stmt = $pdo->prepare("SELECT preco, quantidade_sessoes FROM servicos WHERE id = ?");
    $stmt->execute([$id_servico]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$servico) die("Serviço não encontrado.");

    $qtd_sessoes = $servico['quantidade_sessoes'];
    $grupo_id    = uniqid('pct_'); // Identificador do carrinho
    $hora_fim    = date('H:i:s', strtotime("$hora_inicio + 50 minutes"));

    $pdo->beginTransaction();

    // 3. Loop de Reserva (Sem ID de usuário)
    for ($i = 0; $i < $qtd_sessoes; $i++) {
        $data_calculada = date('Y-m-d', strtotime("+$i week", strtotime($data_inicio)));

        // Verifica disponibilidade
        $check = $pdo->prepare("SELECT count(*) FROM agendamentos 
                                WHERE id_profissional = ? 
                                AND data_agendamento = ? 
                                AND hora_agendamento = ? 
                                AND status_pagamento != 'cancelado'");
        $check->execute([$id_profissional, $data_calculada, $hora_inicio]);
        
        if ($check->fetchColumn() > 0) {
            $data_br = date('d/m/Y', strtotime($data_calculada));
            throw new Exception("O dia $data_br às $hora_inicio já está ocupado.");
        }

        // Insere com id_usuario NULL (Visitante)
        $sql = "INSERT INTO agendamentos (
                    id_usuario, id_profissional, id_servico, 
                    data_agendamento, hora_agendamento, hora_fim, 
                    valor, status_pagamento, grupo_id
                ) VALUES (NULL, ?, ?, ?, ?, ?, ?, 'pendente', ?)";
        
        $stmt_insert = $pdo->prepare($sql);
        $stmt_insert->execute([
            $id_profissional, $id_servico, 
            $data_calculada, $hora_inicio, $hora_fim, 
            $servico['preco'], $grupo_id
        ]);
    }

    $pdo->commit();

    // 4. Manda para o Checkout (lá pediremos o CPF)
		header("Location: checkout_cartão.php?ref=$grupo_id");    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h2 style='color:red; text-align:center'>" . $e->getMessage() . "</h2>";
    echo "<center><a href='agenda_publica.php?p=$id_profissional'>Voltar</a></center>";
}
?>