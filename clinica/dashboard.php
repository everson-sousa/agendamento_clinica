<?php
require_once 'auth.php';
$tituloPagina = "Dashboard";
require_once 'header.php';

// ======================================================
// 1. PROTEÇÃO DE ACESSO (SEMPRE NO TOPO)
// ======================================================
//session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// ======================================================
// 2. CONFIGURAÇÕES DA PÁGINA
// ======================================================
$tituloPagina = "Dashboard";

// ======================================================
// 3. INCLUDES
// ======================================================
require_once 'conexao.php';
require_once 'header.php';

// ======================================================
// 4. DADOS DO USUÁRIO LOGADO
// ======================================================
$id_usuario_logado   = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// ======================================================
// 5. CONSULTA SQL
// ======================================================
$sql_base = "
    SELECT 
        ag.*, 
        usr.nome AS nome_profissional,
        pac.nome_completo AS nome_paciente 
    FROM agendamentos AS ag
    JOIN usuarios AS usr ON ag.id_profissional = usr.id
    JOIN pacientes AS pac ON ag.id_paciente = pac.id
    WHERE 
        ag.status = 'marcado'
        AND ag.data_hora_inicio >= CURDATE()
";

$params = [];

if ($tipo_usuario_logado === 'profissional') {
    $sql_base .= " AND ag.id_profissional = ?";
    $params[] = $id_usuario_logado;
}

$sql_base .= " ORDER BY ag.data_hora_inicio ASC";

// ======================================================
// 6. EXECUÇÃO DA QUERY
// ======================================================
try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $agendamentos_ativos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar agendamentos: " . $e->getMessage());
}
?>

<p>Bem-vindo(a) ao painel de gerenciamento da clínica.</p>

<hr style="margin: 30px 0;">

<h3>Próximos Agendamentos (Ativos)</h3>

<?php
if (count($agendamentos_ativos) === 0) {
    echo "<p>Nenhum agendamento ativo encontrado.</p>";
} else {

    $diaAtual = null;
    $diasDaSemana = [
        'Domingo', 'Segunda-feira', 'Terça-feira',
        'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'
    ];

    foreach ($agendamentos_ativos as $ag) {

        $diaDaSessao = date('Y-m-d', strtotime($ag['data_hora_inicio']));

        if ($diaDaSessao !== $diaAtual) {

            if ($diaAtual !== null) {
                echo '</tbody></table>';
            }

            $diaAtual = $diaDaSessao;
            $diaFormatado = date('d/m/Y', strtotime($diaAtual));
            $indiceDiaSemana = date('w', strtotime($diaAtual));
            $nomeDiaSemana = $diasDaSemana[$indiceDiaSemana];

            echo "<h3 class='dashboard-dia-titulo'>{$nomeDiaSemana}, {$diaFormatado}</h3>";

            echo '<table class="dashboard-tabela">';
            echo '<thead><tr>';
            echo '<th>Paciente</th>';
            echo '<th>Hora</th>';
            echo '<th>Tipo</th>';
            echo '<th>Pagamento</th>';

            if ($tipo_usuario_logado === 'admin') {
                echo '<th>Profissional</th>';
            }

            echo '<th>Ações</th>';
            echo '</tr></thead><tbody>';
        }

        echo '<tr>';
        echo '<td>' . htmlspecialchars($ag['nome_paciente']) . '</td>';
        echo '<td>' . date('H:i', strtotime($ag['data_hora_inicio'])) . '</td>';
        echo '<td>' . ucfirst($ag['tipo_atendimento']) . '</td>';

        $pagamento_class = ($ag['status_pagamento'] === 'Pendente')
            ? 'status-pendente'
            : 'status-pago';

        echo "<td class='{$pagamento_class}'>" . $ag['status_pagamento'] . "</td>";

        if ($tipo_usuario_logado === 'admin') {
            echo '<td>' . htmlspecialchars($ag['nome_profissional']) . '</td>';
        }

        echo "<td>
                <a href='editar_agendamento.php?id={$ag['id']}'>
                    Gerenciar
                </a>
              </td>";
        echo '</tr>';
    }

    echo '</tbody></table>';
}
?>

<?php
// ======================================================
// 7. RODAPÉ
// ======================================================
require_once 'footer.php';