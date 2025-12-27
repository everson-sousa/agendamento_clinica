<?php
// processa_edicao_agendamento.php

// 1. Inicia a sessão e verifica segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 3. Verifica se os dados vieram por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 4. Pega os dados do formulário
    $id_agendamento = $_POST['id_agendamento']; 
    $id_paciente = $_POST['id_paciente'];         
    $tipo_atendimento = $_POST['tipo_atendimento']; 
    $status = $_POST['status']; // O NOVO status 
    $status_pagamento = $_POST['status_pagamento']; 
    $observacoes = $_POST['observacoes'];
    $data_hora_inicio = $_POST['data_inicio'] . ' ' . $_POST['hora_inicio'];
    $data_hora_fim = $_POST['data_inicio'] . ' ' . $_POST['hora_fim'];

    try {
        // --- 5. BUSCA DADOS ORIGINAIS ---
        // Adicionei status_pagamento na busca para conferir a regra
        $sql_check = "SELECT id_profissional, status, status_pagamento FROM agendamentos WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_agendamento]);
        $ag_original = $stmt_check->fetch();
        if (!$ag_original) { die("Agendamento não encontrado."); }
        
        $id_profissional = $ag_original['id_profissional'];
        $status_antigo = $ag_original['status']; // O status que estava no banco
        $status_pagamento_antigo = $ag_original['status_pagamento']; // O pagamento que estava no banco

        // --- 6. CHECAGEM DE PERMISSÃO ---
        if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] != 'admin' && $id_profissional != $_SESSION['usuario_id']) {
            die("Acesso negado.");
        }

        // ======================================================================
        // --- 6.5. REGRAS DE SEGURANÇA (MÁQUINA DE ESTADOS) ---
        // ======================================================================

        // REGRA 1: Se já estava PAGO, não pode voltar a ser PENDENTE
        if ($status_pagamento_antigo == 'Pago' && $status_pagamento != 'Pago') {
             die("<b>Ação Bloqueada:</b> O pagamento já foi confirmado. Não é possível reverter para Pendente.<br><br><a href='javascript:history.back()'>Voltar</a>");
        }

        // REGRA 2: Se já estava REALIZADO ou CANCELADO, não pode mudar o status
        if (in_array($status_antigo, ['realizado', 'cancelado']) && $status != $status_antigo) {
             die("<b>Ação Bloqueada:</b> A consulta já foi finalizada ($status_antigo). Não é possível alterar o status agora.<br><br><a href='javascript:history.back()'>Voltar</a>");
        }
        // ======================================================================


        // --- 7. VERIFICAÇÃO DE CONFLITO (DUPLICIDADE) ---
        $sql_conflito = "SELECT id FROM agendamentos 
                          WHERE id_profissional = ? AND id != ? AND status != 'cancelado'
                          AND data_hora_inicio < ? AND data_hora_fim > ?";
        $stmt_conflito = $pdo->prepare($sql_conflito);
        $stmt_conflito->execute([$id_profissional, $id_agendamento, $data_hora_fim, $data_hora_inicio]);
        $conflito = $stmt_conflito->fetch();

        if ($conflito) {
            die("<b>Erro: Conflito de Horário!</b> O profissional já possui outro agendamento neste mesmo horário. <br><a href='javascript:history.back()'>Tentar Novamente</a>");
        }
        
        // --- 8. ATUALIZA O AGENDAMENTO (SALVA TUDO) ---
        $sql_update = "UPDATE agendamentos SET 
                            id_paciente = ?, data_hora_inicio = ?, data_hora_fim = ?, 
                            status = ?, tipo_atendimento = ?, status_pagamento = ?, observacoes = ?
                       WHERE id = ?";
        
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            $id_paciente, $data_hora_inicio, $data_hora_fim, $status, 
            $tipo_atendimento, $status_pagamento, $observacoes, $id_agendamento 
        ]);

        
        // -----------------------------------------------------------
        // *** 9. LÓGICA: DAR BAIXA EM SESSÕES ***
        // -----------------------------------------------------------
        if ($status == 'realizado' && $status_antigo != 'realizado') {
            
            $sql_baixa = "UPDATE planos_paciente SET
                            sessoes_utilizadas = sessoes_utilizadas + 1
                          WHERE
                            id_paciente = ?
                            AND tipo_atendimento = ?
                            AND status = 'Ativo'
                            AND sessoes_utilizadas < sessoes_contratadas";
            
            $stmt_baixa = $pdo->prepare($sql_baixa);
            $stmt_baixa->execute([$id_paciente, $tipo_atendimento]);

            // -----------------------------------------------------------
            // *** 10. NOVA LÓGICA: AUTO-FINALIZAR PLANO/PACOTE ***
            // -----------------------------------------------------------
            // Após dar baixa, verifica se algum plano zerou o saldo
            $sql_finaliza = "UPDATE planos_paciente SET
                                status = 'Concluido'
                             WHERE
                                id_paciente = ?
                                AND tipo_atendimento = ?
                                AND status = 'Ativo'
                                AND sessoes_utilizadas >= sessoes_contratadas";
            
            $stmt_finaliza = $pdo->prepare($sql_finaliza);
            $stmt_finaliza->execute([$id_paciente, $tipo_atendimento]);
            // --- FIM DA LÓGICA DE AUTO-FINALIZAR ---
        }
        // --- FIM DA LÓGICA DE BAIXA ---

        
        // --- 11. SUCESSO ---
        echo "Agendamento atualizado com sucesso!";
        echo '<br><a href="ver_agendamentos.php">Voltar para a Lista</a>';
        echo '<br><a href="dashboard.php">Voltar ao Painel</a>';

    } catch (PDOException $e) {
        echo "Erro ao atualizar o agendamento: " . $e->getMessage();
    }

} else {
    echo "Acesso inválido.";
}
?>