<?php
// ======================================================
// 1. AUTENTICAÇÃO E CONFIGURAÇÃO DA PÁGINA
// ======================================================
require_once 'auth.php';

$tituloPagina = 'Dashboard';

require_once 'conexao.php';
require_once 'header.php';

// ======================================================

// 2. DADOS DO USUÁRIO LOGADO
// ======================================================
$idUsuario   = $_SESSION['usuario_id'];
$tipoUsuario = $_SESSION['usuario_tipo'];

// ======================================================
// 3. CONSULTA DOS AGENDAMENTOS ATIVOS
// ======================================================
$sql = "
    SELECT 
        ag.*,
        usr.nome AS nome_profissional,
        pac.nome_completo AS nome_paciente
    FROM agendamentos ag
    INNER JOIN usuarios usr ON usr.id = ag.id_profissional
    INNER JOIN pacientes pac ON pac.id = ag.id_paciente
    WHERE 
        ag.status = 'marcado'
        AND ag.data_hora_inicio >= CURDATE()
";

$params = [];

if ($tipoUsuario === 'profissional') {
    $sql .= " AND ag.id_profissional = ?";
    $params[] = $idUsuario;
}

$sql .= " ORDER BY ag.data_hora_inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<p>Bem-vindo(a) ao painel de gerenciamento da clínica.</p>

<hr style="margin: 30px 0;">

<h3>Próximos Agendamentos</h3>

<?php if (empty($agendamentos)): ?>

    <p>Nenhum agendamento ativo encontrado.</p>

<?php else: ?>

<?php
$diaAtual = null;
$diasSemana = [
    'Domingo', 'Segunda-feira', 'Terça-feira',
    'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'
];

foreach ($agendamentos as $ag):

    $dataSessao = date('Y-m-d', strtotime($ag['data_hora_inicio']));

    if ($dataSessao !== $diaAtual):

        if ($diaAtual !== null) {
            echo '</tbody></table>';
        }

        $diaAtual = $dataSessao;
        $dataFormatada = date('d/m/Y', strtotime($diaAtual));
        $diaSemana = $diasSemana[date('w', strtotime($diaAtual))];
?>

<h3 class="dashboard-dia-titulo">
    <?= $diaSemana ?>, <?= $dataFormatada ?>
</h3>

<table class="dashboard-tabela">
<thead>
<tr>
    <th>Paciente</th>
    <th>Hora</th>
    <th class="col-desktop">Tipo</th>
    <th>Pagamento</th>

    <?php if ($tipoUsuario === 'admin'): ?>
        <th class="col-desktop">Profissional</th>
    <?php endif; ?>

    <th class="col-desktop">Ações</th>
</tr>
</thead>
<tbody>

<?php
    endif;

    $classePagamento = ($ag['status_pagamento'] === 'Pendente')
        ? 'status-pendente'
        : 'status-pago';
?>

<tr>
    <td><?= htmlspecialchars($ag['nome_paciente']) ?></td>

    <td>
        <?= date('H:i', strtotime($ag['data_hora_inicio'])) ?>
    </td>

    <td class="col-desktop">
        <?= ucfirst($ag['tipo_atendimento']) ?>
    </td>

    <td class="<?= $classePagamento ?>">
        <?= $ag['status_pagamento'] ?>
    </td>

    <?php if ($tipoUsuario === 'admin'): ?>
        <td class="col-desktop">
            <?= htmlspecialchars($ag['nome_profissional']) ?>
        </td>
    <?php endif; ?>

    <td class="col-desktop">
        <a href="editar_agendamento.php?id=<?= $ag['id'] ?>">
            Gerenciar
        </a>
    </td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<?php endif; ?>

<?php require_once 'footer.php'; ?>