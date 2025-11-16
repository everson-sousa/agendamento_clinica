<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'conexao.php';

// ------------------------------------------------------------------
// *** FUNÇÃO DE CHECAGEM DE CONFLITO ***
// (Trazemos a lógica que já existe para este script)
// ------------------------------------------------------------------
function checarConflito($pdo, $id_profissional, $data_inicio, $data_fim) {
    try {
        $sql_conflito = "SELECT id FROM agendamentos 
                         WHERE id_profissional = ? 
                         AND status != 'cancelado'
                         AND data_hora_inicio < ?  -- Início Existente < Fim Novo
                         AND data_hora_fim > ?    -- Fim Existente > Início Novo";

        $stmt_conflito = $pdo->prepare($sql_conflito);
        $stmt_conflito->execute([$id_profissional, $data_fim, $data_inicio]);
        $conflito = $stmt_conflito->fetch();

        // Se $conflito for encontrado, retorna true (HÁ CONFLITO)
        if ($conflito) {
            return true; 
        }
        // Se não, retorna false (LIVRE)
        return false;

    } catch (PDOException $e) {
        // Em caso de erro, assume que há conflito por segurança
        error_log("Erro ao checar conflito: " . $e->getMessage());
        return true; 
    }
}
// --- FIM DA FUNÇÃO ---


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. PEGA OS DADOS DO FORMULÁRIO ---
    $id_paciente = $_POST['id_paciente'];
    $id_profissional = $_POST['id_profissional'];
    $tipo_plano = $_POST['tipo_plano'];
    $tipo_atendimento = $_POST['tipo_atendimento']; // 'terapia' ou 'avaliacao'

    // Dados da primeira consulta
    $data_inicio_primeira = $_POST['data_inicio'];
    $hora_inicio_primeira = $_POST['hora_inicio'];
    $hora_fim_primeira = $_POST['hora_fim'];

    // --- 2. DEFINE O NÚMERO DE SESSÕES ---
    $total_sessoes = 0;
    if ($tipo_atendimento == 'terapia') {
        $total_sessoes = 4;
    } elseif ($tipo_atendimento == 'avaliacao') {
        $total_sessoes = 10;
    }
    if ($total_sessoes == 0) {
        die("Erro: Tipo de atendimento inválido.");
    }
    
    // --- 3. CHECAGEM DE SEGURANÇA ---
    if ($_SESSION['usuario_tipo'] == 'profissional' && $id_profissional != $_SESSION['usuario_id']) {
        die("Acesso negado.");
    }

    try {
        // --- 4. SALVA O PLANO/PACOTE (O "CRÉDITO") ---
        $sql_plano = "INSERT INTO planos_paciente 
                        (id_paciente, id_profissional, tipo_plano, tipo_atendimento, sessoes_contratadas)
                      VALUES (?, ?, ?, ?, ?)";
        $stmt_plano = $pdo->prepare($sql_plano);
        $stmt_plano->execute([
            $id_paciente, $id_profissional, $tipo_plano, 
            $tipo_atendimento, $total_sessoes
        ]);
        
        
        // --- 5. O "ROBÔ AGENDADOR" (LOOP) ---
        $sessoes_agendadas = 0;
        $datas_com_conflito = [];

        // Prepara o SQL para inserir o agendamento (dentro do loop)
        $sql_agendamento = "INSERT INTO agendamentos 
                                (id_profissional, id_paciente, data_hora_inicio, data_hora_fim, 
                                 status, tipo_atendimento, status_pagamento, observacoes)
                            VALUES 
                                (?, ?, ?, ?, 'marcado', ?, 'Pendente', ?)";
        $stmt_agendamento = $pdo->prepare($sql_agendamento);

        for ($i = 0; $i < $total_sessoes; $i++) {
            
            // Calcula a data da sessão atual (semana 0, semana 1, etc.)
            // Adiciona $i semanas à data da primeira consulta
            $data_sessao_atual = date('Y-m-d', strtotime($data_inicio_primeira . " +$i weeks"));
            
            // Monta o datetime de início e fim
            $data_hora_inicio_sessao = $data_sessao_atual . ' ' . $hora_inicio_primeira;
            $data_hora_fim_sessao = $data_sessao_atual . ' ' . $hora_fim_primeira;
            
            // --- 6. CHECA O CONFLITO ---
            $temConflito = checarConflito($pdo, $id_profissional, $data_hora_inicio_sessao, $data_hora_fim_sessao);

            if ($temConflito) {
                // Se tem conflito, anota a data e PULA esta semana
                $datas_com_conflito[] = date('d/m/Y', strtotime($data_sessao_atual));
            
            } else {
                // Se está livre, AGENDA!
                $observacao = "Sessão " . ($i + 1) . "/" . $total_sessoes . " do " . $tipo_plano . ".";
                
                $stmt_agendamento->execute([
                    $id_profissional,
                    $id_paciente,
                    $data_hora_inicio_sessao,
                    $data_hora_fim_sessao,
                    $tipo_atendimento,
                    $observacao
                ]);
                
                $sessoes_agendadas++;
            }
        }
        
        // --- 7. APRESENTA O RESUMO ---
        echo "<h1>Plano/Pacote criado com sucesso!</h1>";
        echo "<p><b>Resumo do Agendamento Automático:</b></p>";
        echo "<p>{$sessoes_agendadas} de {$total_sessoes} sessões foram agendadas automaticamente.</p>";

        if (count($datas_com_conflito) > 0) {
            echo "<p style='color: red; font-weight: bold;'>As seguintes sessões não puderam ser agendadas por conflito de horário:</p>";
            echo "<ul>";
            foreach ($datas_com_conflito as $data_falha) {
                echo "<li>{$data_falha}</li>";
            }
            echo "</ul>";
            echo "<p>Por favor, negocie com o paciente e agende essas datas manualmente pela tela 'Novo Agendamento'.</p>";
        }

        echo '<br><a href="ver_planos.php">Ver Lista de Planos</a>';
        echo '<br><a href="ver_agendamentos.php">Ver Calendário de Agendamentos</a>';
        echo '<br><a href="dashboard.php">Voltar ao Painel</a>';

    } catch (PDOException $e) {
        echo "Erro ao salvar: " . $e->getMessage();
    }
}
?>