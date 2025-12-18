<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
// 1. Inicia a sessão e faz a verificação de segurança
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
    $id_profissional = $_POST['id_profissional'];
    $id_paciente = $_POST['id_paciente'];         
    $tipo_atendimento = $_POST['tipo_atendimento']; 
    $observacoes = $_POST['observacoes'];
    
    // Combina data e hora
    $data_hora_inicio = $_POST['data_inicio'] . ' ' . $_POST['hora_inicio'];
    $data_hora_fim = $_POST['data_inicio'] . ' ' . $_POST['hora_fim'];
    
    $status = 'marcado'; // Padrão

    // 5. Validação e Segurança
    if ($_SESSION['usuario_tipo'] == 'profissional' && $id_profissional != $_SESSION['usuario_id']) {
        die("Erro: Você não tem permissão para marcar agendamentos para outros profissionais.");
    }
    
    // ------------------------------------------------------------------
    // *** 6. NOVA VERIFICAÇÃO DE CONFLITO (DUPLICIDADE) ***
    // ------------------------------------------------------------------
    try {
        $sql_conflito = "SELECT id FROM agendamentos 
                         WHERE id_profissional = ? 
                         AND status != 'cancelado'
                         AND data_hora_inicio < ?  -- Início Existente < Fim Novo
                         AND data_hora_fim > ?    -- Fim Existente > Início Novo";

        $stmt_conflito = $pdo->prepare($sql_conflito);
        
        // Executa a query com os parâmetros corretos
        $stmt_conflito->execute([
            $id_profissional,
            $data_hora_fim,     // Parâmetro 2 ($data_hora_fim)
            $data_hora_inicio   // Parâmetro 3 ($data_hora_inicio)
        ]);

        $conflito = $stmt_conflito->fetch();

        // Se $conflito NÃO for falso, significa que ENCONTROU um agendamento
        if ($conflito) {
            die("<b>Erro: Conflito de Horário!</b> O profissional selecionado já possui um agendamento que se sobrepõe a este horário. <br><a href='javascript:history.back()'>Tentar Novamente</a>");
        }

    } catch (PDOException $e) {
        die("Erro ao verificar conflitos: " ."<br>". $e->getMessage());
    }
    // --- FIM DA VERIFICAÇÃO DE CONFLITO ---


    // 7. Prepara o Comando SQL (só executa se passou do passo 6)
    $sql = "INSERT INTO agendamentos 
                (id_profissional, id_paciente, data_hora_inicio, data_hora_fim, status, tipo_atendimento, observacoes)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?)";

    // 8. Tenta executar no banco
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_profissional,
            $id_paciente,
            $data_hora_inicio,
            $data_hora_fim,
            $status,
            $tipo_atendimento,
            $observacoes
        ]);

        // 9. Sucesso!
        echo "Agendamento cadastrado com sucesso!";
        echo '<br><a href="ver_agendamentos.php">Ver Agendamentos</a>';
        echo '<br><a href="cadastrar_agendamento.php">Cadastrar Outro</a>';

    } catch (PDOException $e) {
        // 10. Erro!
        echo "Erro ao cadastrar o agendamento: " . $e->getMessage();
    }

} else {
    // Se alguém tentar acessar o arquivo direto pela URL
    echo "Acesso inválido.";
}
?>