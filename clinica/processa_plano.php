<?php
session_start();
require_once 'conexao.php';

// 1. Verificação de Segurança e Sessão
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: adicionar_plano.php");
    exit;
}

// 2. Recebendo os dados do formulário
$id_profissional = $_POST['id_profissional'];
$id_paciente     = $_POST['id_paciente'];
$tipo_atend      = $_POST['tipo_atendimento'];
$valor_total     = str_replace(',', '.', $_POST['valor']); // Garante formato 150.00
$data_inicio     = $_POST['data_inicio'];
$hora_inicio     = $_POST['hora_inicio'];
$hora_fim        = $_POST['hora_fim'];
$tipo_plano_desc = $_POST['tipo_plano']; // 'Pacote' ou 'Tratamento'

// 3. Definindo a Quantidade de Sessões baseada na escolha
$qtd_sessoes = 1;
switch ($tipo_atend) {
    case 'terapia_pacote':
        $qtd_sessoes = 4;
        break;
    case 'avaliacao':
        $qtd_sessoes = 10;
        break;
    case 'plantao':
    case 'terapia_avulsa':
    default:
        $qtd_sessoes = 1;
        break;
}

// 4. Gerando um ID único para agrupar esse pacote
// Isso permite que no futuro você cancele o pacote inteiro de uma vez
$grupo_id = uniqid('plan_'); 

// INÍCIO DA TRANSAÇÃO (Segurança do Banco)
// Se der erro na 5ª sessão, ele desfaz a 1ª, 2ª, 3ª e 4ª automaticamente.
$pdo->beginTransaction();

try {
    $datas_conflito = [];

    // 5. Loop para criar os agendamentos
    for ($i = 0; $i < $qtd_sessoes; $i++) {
        
        // Calcula a data: Data Inicio + (7 dias * indice)
        // i=0 (hoje), i=1 (semana que vem), i=2 (daqui 2 semanas)...
        $data_calculada = date('Y-m-d', strtotime($data_inicio . " + $i week"));

        // A. VERIFICAÇÃO DE CONFLITO (Overbooking)
        // Verifica se o PROFISSIONAL já tem algo marcado nessa data/hora
        $sql_check = "SELECT id FROM agendamentos 
                      WHERE id_profissional = ? 
                      AND data_agendamento = ? 
                      AND (
                          (hora_inicio < ? AND hora_fim > ?) OR -- O novo começa durante um existente
                          (hora_inicio >= ? AND hora_inicio < ?) -- O novo termina durante um existente
                      )
                      AND status != 'cancelado'";
        
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([
            $id_profissional, 
            $data_calculada, 
            $hora_fim, $hora_inicio, // Lógica de sobreposição de horário
            $hora_inicio, $hora_fim
        ]);

        if ($stmt_check->rowCount() > 0) {
            // Se achou conflito, guarda a data para avisar o usuário
            $datas_conflito[] = date('d/m/Y', strtotime($data_calculada));
            continue; // Pula para a próxima (ou você pode usar 'break' para cancelar tudo)
        }

        // B. INSERIR NO BANCO
        // Nota: Salvamos o valor total apenas no primeiro registro ou dividido?
        // Estratégia: Salva o valor no banco para referência, mas o pagamento é pelo grupo_id
        $sql_insert = "INSERT INTO agendamentos (
            id_profissional, 
            id_paciente, 
            data_agendamento, 
            hora_inicio, 
            hora_fim, 
            tipo_servico, 
            valor, 
            grupo_id, 
            status_pagamento,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aguardando', 'agendado')";

        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([
            $id_profissional,
            $id_paciente,
            $data_calculada,
            $hora_inicio,
            $hora_fim,
            $tipo_atend, // ex: terapia_pacote
            $valor_total, // Valor salvo em cada registro para histórico
            $grupo_id
        ]);
    }

    // 6. Finalização
    if (count($datas_conflito) > 0) {
        // Se houve conflitos, decidimos se damos Rollback ou Commit parcial
        // Para segurança médica, melhor dar Rollback e avisar para escolher outro horário
        $pdo->rollBack();
        $_SESSION['msg_erro'] = "Erro: O profissional já tem agendamentos nas datas: " . implode(", ", $datas_conflito);
        header("Location: adicionar_plano.php");
        exit;
    } else {
        $pdo->commit();
        $_SESSION['msg_sucesso'] = "Sucesso! Foram agendadas $qtd_sessoes sessões.";
        // Redireciona para a lista ou para o checkout (link de pagamento)
        header("Location: meus_agendamentos.php"); 
        exit;
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Erro grave no sistema: " . $e->getMessage();
}
?>