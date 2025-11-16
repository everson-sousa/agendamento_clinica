<?php
// 1. Define o título
$tituloPagina = "Dashboard";

// 2. Inclui o cabeçalho
require_once 'header.php'; 

// 3. Inclui a conexão
require_once 'conexao.php'; 

// 4. Pega os dados do usuário da sessão
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// 5. LÓGICA SQL (A MESMA DE ANTES)
// Busca agendamentos "marcados" de hoje em diante
$sql_base = "SELECT 
                ag.*, 
                usr.nome AS nome_profissional,
                pac.nome_completo AS nome_paciente 
            FROM 
                agendamentos AS ag
            JOIN 
                usuarios AS usr ON ag.id_profissional = usr.id
            JOIN
                pacientes AS pac ON ag.id_paciente = pac.id
            WHERE 
                ag.status = 'marcado'
                AND ag.data_hora_inicio >= CURDATE() 
            ";

$params = []; 

if ($tipo_usuario_logado == 'profissional') {
    $sql_base .= " AND ag.id_profissional = ?";
    $params[] = $id_usuario_logado;
}

$sql_base .= " ORDER BY ag.data_hora_inicio ASC";

// 6. Executa a consulta
try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $agendamentos_ativos = $stmt->fetchAll(); 

} catch (PDOException $e) {
    die("Erro ao buscar agendamentos: " . $e->getMessage());
}
?>

<p>Bem-vindo(a) ao painel de gerenciamento da clínica.</p>

<hr style="margin: 30px 0;">

<h3>Próximos Agendamentos (Ativos)</h3>

<?php
if (count($agendamentos_ativos) == 0) {
    echo "<p>Nenhum agendamento ativo encontrado.</p>";
} else {
    
    $diaAtual = null; // Variável para rastrear o dia
    $diasDaSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];

    foreach ($agendamentos_ativos as $ag) {
        
        // Pega o dia (ex: 2025-11-17)
        $diaDaSessao = date('Y-m-d', strtotime($ag['data_hora_inicio']));

        // Se for um novo dia, imprime o cabeçalho do dia
        if ($diaDaSessao != $diaAtual) {
            
            // 1. Fecha a tabela anterior (se não for a primeira vez)
            if ($diaAtual !== null) {
                echo '</tbody></table>';
            }
            
            // 2. Atualiza o rastreador
            $diaAtual = $diaDaSessao;
            
            // 3. Formata e imprime o novo título do dia
            $diaFormatado = date('d/m/Y', strtotime($diaAtual));
            $indiceDiaSemana = date('w', strtotime($diaAtual));
            $nomeDiaSemana = $diasDaSemana[$indiceDiaSemana];

            echo "<h3 class='dashboard-dia-titulo'>{$nomeDiaSemana}, {$diaFormatado}</h3>";
            
            // 4. Inicia a nova tabela
            echo '<table class="dashboard-tabela"><thead><tr>';
            echo '<th>Paciente</th>';
            echo '<th>Hora</th>'; // Mudou de "Data/Hora Início" para "Hora"
            echo '<th>Tipo</th>';
            echo '<th>Pagamento</th>';
            if ($tipo_usuario_logado == 'admin') {
                echo '<th>Profissional</th>';
            }
            echo '<th>Ações</th>';
            echo '</tr></thead><tbody>';
        }
        
        // 5. Imprime a linha do agendamento
        echo '<tr>';
        echo '<td>' . htmlspecialchars($ag['nome_paciente']) . '</td>';
        echo '<td>' . date('H:i', strtotime($ag['data_hora_inicio'])) . '</td>'; // Mostra só a hora
        echo '<td>' . ucfirst($ag['tipo_atendimento']) . '</td>';
        
        // *** SOLUÇÃO 3 (CORES) APLICADA AQUI ***
        $pagamento_style = ($ag['status_pagamento'] == 'Pendente') ? 'status-pendente' : 'status-pago';
        echo "<td class='{$pagamento_style}'>" . $ag['status_pagamento'] . "</td>";
        
        if ($tipo_usuario_logado == 'admin') {
            echo '<td>' . htmlspecialchars($ag['nome_profissional']) . '</td>';
        }
        echo "<td><a href='editar_agendamento.php?id=" . $ag['id'] . "'>Gerenciar</a></td>";
        echo '</tr>';
    }
    
    // 6. Fecha a ÚLTIMA tabela
    if ($diaAtual !== null) {
        echo '</tbody></table>';
    }
} 
?>

<?php
// 8. Inclui o rodapé
require_once 'footer.php'; 
?>