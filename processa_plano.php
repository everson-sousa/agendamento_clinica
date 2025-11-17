<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'conexao.php';

// Define o fuso horário para garantir que a comparação seja justa
date_default_timezone_set('America/Sao_Paulo');

// ------------------------------------------------------------------
// FUNÇÃO: Checar conflito de horário
// ------------------------------------------------------------------
function checarConflito($pdo, $id_profissional, $data_inicio, $data_fim) {
    try {
        $sql_conflito = "SELECT id FROM agendamentos 
                         WHERE id_profissional = ? 
                         AND status != 'cancelado'
                         AND data_hora_inicio < ?  
                         AND data_hora_fim > ?";

        $stmt_conflito = $pdo->prepare($sql_conflito);
        $stmt_conflito->execute([$id_profissional, $data_fim, $data_inicio]);
        
        return $stmt_conflito->fetch() ? true : false;

    } catch (PDOException $e) {
        return true; 
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. PEGA DADOS
    $id_paciente = $_POST['id_paciente'];
    $id_profissional = $_POST['id_profissional'];
    $tipo_plano = $_POST['tipo_plano']; 
    $tipo_escolhido = $_POST['tipo_atendimento']; 

    $data_inicio_primeira = $_POST['data_inicio'];
    $hora_inicio_primeira = $_POST['hora_inicio'];
    $hora_fim_primeira = $_POST['hora_fim'];

    // -----------------------------------------------------------
    // *** NOVA VALIDAÇÃO: BLOQUEAR DATA NO PASSADO ***
    // -----------------------------------------------------------
    // Monta a data completa que o usuário tentou agendar
    $data_hora_tentativa = $data_inicio_primeira . ' ' . $hora_inicio_primeira;
    
    // Pega a data/hora de AGORA
    $agora = date('Y-m-d H:i');

    if ($data_hora_tentativa < $agora) {
        // Se tentar agendar para trás, mata o processo e avisa
        die("<div style='font-family:Arial; padding:20px; color:red; border:1px solid red; background:#fff0f0;'>
                <h3>Erro de Data!</h3>
                <p>Você tentou agendar para <b>" . date('d/m/Y H:i', strtotime($data_hora_tentativa)) . "</b>.</p>
                <p>Não é possível criar agendamentos ou planos para o passado.</p>
                <a href='javascript:history.back()'>Voltar e Corrigir</a>
             </div>");
    }
    // -----------------------------------------------------------


    // 2. TRADUTOR DE OPÇÕES
    $tipo_atendimento_banco = ''; 
    $sessoes_contratadas = 0;

    switch ($tipo_escolhido) {
        case 'terapia_avulsa':
            $tipo_atendimento_banco = 'terapia';
            $sessoes_contratadas = 1;
            break;
        case 'terapia_pacote':
            $tipo_atendimento_banco = 'terapia';
            $sessoes_contratadas = 4;
            break;
        case 'plantao':
            $tipo_atendimento_banco = 'plantao';
            $sessoes_contratadas = 1;
            break;
        case 'avaliacao':
            $tipo_atendimento_banco = 'avaliacao';
            $sessoes_contratadas = 10;
            break;
        default:
            die("Erro: Tipo de atendimento inválido.");
    }

    // 3. CHECAGEM DE SEGURANÇA
    if ($_SESSION['usuario_tipo'] == 'profissional' && $id_profissional != $_SESSION['usuario_id']) {
        die("Acesso negado.");
    }

    try {
        // 4. SALVA O PLANO/PACOTE
        $sql_plano = "INSERT INTO planos_paciente 
                        (id_paciente, id_profissional, tipo_plano, tipo_atendimento, sessoes_contratadas)
                      VALUES (?, ?, ?, ?, ?)";
        
        $stmt_plano = $pdo->prepare($sql_plano);
        $stmt_plano->execute([
            $id_paciente, 
            $id_profissional, 
            $tipo_plano, 
            $tipo_atendimento_banco, 
            $sessoes_contratadas
        ]);
        
        // 5. ROBÔ AGENDADOR
        $sessoes_agendadas = 0;
        $datas_com_conflito = [];

        $sql_agendamento = "INSERT INTO agendamentos 
                                (id_profissional, id_paciente, data_hora_inicio, data_hora_fim, 
                                 status, tipo_atendimento, status_pagamento, observacoes)
                            VALUES 
                                (?, ?, ?, ?, 'marcado', ?, 'Pendente', ?)";
        $stmt_agendamento = $pdo->prepare($sql_agendamento);

        for ($i = 0; $i < $sessoes_contratadas; $i++) {
            
            $data_sessao_atual = date('Y-m-d', strtotime($data_inicio_primeira . " +$i weeks"));
            $data_hora_inicio_sessao = $data_sessao_atual . ' ' . $hora_inicio_primeira;
            $data_hora_fim_sessao = $data_sessao_atual . ' ' . $hora_fim_primeira;
            
            if (checarConflito($pdo, $id_profissional, $data_hora_inicio_sessao, $data_hora_fim_sessao)) {
                $datas_com_conflito[] = date('d/m/Y', strtotime($data_sessao_atual));
            } else {
                $observacao = "Sessão " . ($i + 1) . "/" . $sessoes_contratadas . " (" . ucfirst($tipo_plano) . ")";
                
                $stmt_agendamento->execute([
                    $id_profissional,
                    $id_paciente,
                    $data_hora_inicio_sessao,
                    $data_hora_fim_sessao,
                    $tipo_atendimento_banco,
                    $observacao
                ]);
                $sessoes_agendadas++;
            }
        }
        
        // 6. RESULTADO
        echo "<div style='font-family: Arial, padding: 20px;'>";
        echo "<h2 style='color: #27ae60;'>Plano criado com sucesso!</h2>";
        echo "<p><b>Resumo do Agendamento Automático:</b></p>";
        echo "<p>{$sessoes_agendadas} de {$sessoes_contratadas} sessões foram agendadas automaticamente.</p>";

        if (count($datas_com_conflito) > 0) {
            echo "<div style='background-color: #ffebee; color: #c0392b; padding: 15px; border-radius: 5px; border: 1px solid #ef9a9a;'>";
            echo "<strong>Atenção:</strong> As seguintes datas não puderam ser agendadas devido a conflito de horário:";
            echo "<ul>";
            foreach ($datas_com_conflito as $data_falha) {
                echo "<li>{$data_falha}</li>";
            }
            echo "</ul>";
            echo "<p>Por favor, vá em 'Novo Agendamento' e marque essas sessões manualmente em horários alternativos.</p>";
            echo "</div>";
        }

        echo '<br><br>';
        echo '<a href="ver_planos.php">Ver Lista de Planos</a> | ';
        echo '<a href="ver_agendamentos.php">Ver Calendário</a> | ';
        echo '<a href="dashboard.php">Voltar ao Painel</a>';
        echo "</div>";

    } catch (PDOException $e) {
        echo "Erro ao salvar: " . $e->getMessage();
    }
}
?>