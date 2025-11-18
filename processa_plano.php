<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: login.php"); exit; }
require_once 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

function checarConflito($pdo, $id_profissional, $data_inicio, $data_fim) {
    try {
        $sql_conflito = "SELECT id FROM agendamentos WHERE id_profissional = ? AND status != 'cancelado' AND data_hora_inicio < ? AND data_hora_fim > ?";
        $stmt_conflito = $pdo->prepare($sql_conflito);
        $stmt_conflito->execute([$id_profissional, $data_fim, $data_inicio]);
        return $stmt_conflito->fetch() ? true : false;
    } catch (PDOException $e) { return true; }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_paciente = $_POST['id_paciente'];
    $id_profissional = $_POST['id_profissional'];
    $tipo_plano = $_POST['tipo_plano']; 
    $tipo_escolhido = $_POST['tipo_atendimento']; 
    $valor = $_POST['valor']; // <-- NOVO: Captura o valor

    $data_inicio_primeira = $_POST['data_inicio'];
    $hora_inicio_primeira = $_POST['hora_inicio'];
    $hora_fim_primeira = $_POST['hora_fim'];

    // Validação de Data Passada
    if (($data_inicio_primeira . ' ' . $hora_inicio_primeira) < date('Y-m-d H:i')) {
        die("Erro: Não é possível agendar no passado.");
    }

    // Tradutor de Opções
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
            die("Erro: Tipo inválido.");
    }

    if ($_SESSION['usuario_tipo'] == 'profissional' && $id_profissional != $_SESSION['usuario_id']) {
        die("Acesso negado.");
    }

    try {
        // --- SALVA O PLANO/PACOTE (Com Valor) ---
        $sql_plano = "INSERT INTO planos_paciente 
                        (id_paciente, id_profissional, tipo_plano, tipo_atendimento, valor, sessoes_contratadas)
                      VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt_plano = $pdo->prepare($sql_plano);
        $stmt_plano->execute([
            $id_paciente, 
            $id_profissional, 
            $tipo_plano, 
            $tipo_atendimento_banco, 
            $valor, // <-- Salva no banco
            $sessoes_contratadas
        ]);
        
        // --- ROBÔ AGENDADOR ---
        $sessoes_agendadas = 0;
        $datas_com_conflito = [];

        $sql_agendamento = "INSERT INTO agendamentos (id_profissional, id_paciente, data_hora_inicio, data_hora_fim, status, tipo_atendimento, status_pagamento, observacoes) VALUES (?, ?, ?, ?, 'marcado', ?, 'Pendente', ?)";
        $stmt_agendamento = $pdo->prepare($sql_agendamento);

        for ($i = 0; $i < $sessoes_contratadas; $i++) {
            $data_sessao_atual = date('Y-m-d', strtotime($data_inicio_primeira . " +$i weeks"));
            $data_hora_inicio_sessao = $data_sessao_atual . ' ' . $hora_inicio_primeira;
            $data_hora_fim_sessao = $data_sessao_atual . ' ' . $hora_fim_primeira;
            
            if (checarConflito($pdo, $id_profissional, $data_hora_inicio_sessao, $data_hora_fim_sessao)) {
                $datas_com_conflito[] = date('d/m/Y', strtotime($data_sessao_atual));
            } else {
                $observacao = "Sessão " . ($i + 1) . "/" . $sessoes_contratadas . " (" . ucfirst($tipo_plano) . ")";
                $stmt_agendamento->execute([$id_profissional, $id_paciente, $data_hora_inicio_sessao, $data_hora_fim_sessao, $tipo_atendimento_banco, $observacao]);
                $sessoes_agendadas++;
            }
        }
        
        // --- RESUMO ---
        echo "<div style='font-family: Arial, padding: 20px;'><h2 style='color: #27ae60;'>Plano criado com sucesso!</h2><p><b>Agendamento Automático:</b> {$sessoes_agendadas} de {$sessoes_contratadas} sessões agendadas.</p>";
        if (count($datas_com_conflito) > 0) {
            echo "<div style='color: red;'>Conflito nas datas: " . implode(", ", $datas_com_conflito) . ". Agende manualmente.</div>";
        }
        echo '<br><a href="ver_planos.php">Ver Planos</a> | <a href="ver_agendamentos.php">Ver Calendário</a></div>';

    } catch (PDOException $e) {
        echo "Erro ao salvar: " . $e->getMessage();
    }
}
?>