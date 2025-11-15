<?php
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
    $id_agendamento = $_POST['id_agendamento']; // ID do agendamento que estamos EDITANDO
    $id_paciente = $_POST['id_paciente'];         
    $tipo_atendimento = $_POST['tipo_atendimento']; 
    $status = $_POST['status'];
    $status_pagamento = $_POST['status_pagamento']; 
    $observacoes = $_POST['observacoes'];

    // Combina data e hora
    $data_hora_inicio = $_POST['data_inicio'] . ' ' . $_POST['hora_inicio'];
    $data_hora_fim = $_POST['data_inicio'] . ' ' . $_POST['hora_fim'];

    try {
        // 5. CHECAGEM DE PERMISSÃO (REFORÇO)
        $sql_check = "SELECT id_profissional FROM agendamentos WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_agendamento]);
        $ag_original = $stmt_check->fetch();
        $id_profissional = $ag_original['id_profissional']; // Pega o ID do profissional

        if ($_SESSION['usuario_tipo'] != 'admin' && $ag_original['id_profissional'] != $_SESSION['usuario_id']) {
            die("Acesso negado.");
        }

        // ------------------------------------------------------------------
        // *** 6. VERIFICAÇÃO DE CONFLITO (DUPLICIDADE) NA EDIÇÃO ***
        // ------------------------------------------------------------------
        $sql_conflito = "SELECT id FROM agendamentos 
                         WHERE id_profissional = ? 
                         AND id != ?                  -- IGNORA este agendamento
                         AND status != 'cancelado'
                         AND data_hora_inicio < ?     -- Início Existente < Fim Novo
                         AND data_hora_fim > ?       -- Fim Existente > Início Novo";

        $stmt_conflito = $pdo->prepare($sql_conflito);
        
        $stmt_conflito->execute([
            $id_profissional,
            $id_agendamento,    // O ID que vamos ignorar
            $data_hora_fim,     // Parâmetro 3 ($data_hora_fim)
            $data_hora_inicio   // Parâmetro 4 ($data_hora_inicio)
        ]);

        $conflito = $stmt_conflito->fetch();

        // Se encontrou um conflito
        if ($conflito) {
            die("<b>Erro: Conflito de Horário!</b> O profissional já possui outro agendamento neste mesmo horário. <br><a href='javascript:history.back()'>Tentar Novamente</a>");
        }
        // --- FIM DA VERIFICAÇÃO DE CONFLITO ---


        // 7. Prepara o Comando SQL de ATUALIZAÇÃO (só executa se passou do passo 6)
        $sql_update = "UPDATE agendamentos SET 
                            id_paciente = ?, 
                            data_hora_inicio = ?, 
                            data_hora_fim = ?, 
                            status = ?, 
                            tipo_atendimento = ?,
                            status_pagamento = ?,
                            observacoes = ?
                       WHERE 
                            id = ?";

        // 8. Executa o comando
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([
            $id_paciente,
            $data_hora_inicio,
            $data_hora_fim,
            $status,
            $tipo_atendimento,
            $status_pagamento,
            $observacoes,
            $id_agendamento 
        ]);

        // 9. Sucesso!
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