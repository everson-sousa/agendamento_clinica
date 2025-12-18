<?php
require_once 'auth.php';
$tituloPagina = "Ver Agendamentos";
require_once 'header.php';

//session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
// 1. Define o título desta página
$tituloPagina = "Ver Agendamentos"; 

// 2. Inclui o cabeçalho
require_once 'header.php'; 

// 3. Inclui a conexão
require_once 'conexao.php'; 

// 4. Pega o ID e Tipo do usuário da SESSÃO
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// 5. LÓGICA SQL
$sql_base = "SELECT 
                ag.*, 
                usr.nome AS nome_profissional,
                pac.nome_completo AS nome_paciente 
            FROM 
                agendamentos AS ag
            JOIN 
                usuarios AS usr ON ag.id_profissional = usr.id
            JOIN
                pacientes AS pac ON ag.id_paciente = pac.id";

$params = []; 

if ($tipo_usuario_logado == 'profissional') {
    $sql_base .= " WHERE ag.id_profissional = ?";
    $params[] = $id_usuario_logado;
}

$sql_base .= " ORDER BY ag.data_hora_inicio ASC";

// 6. Executa a consulta
try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $agendamentos = $stmt->fetchAll(); 

} catch (PDOException $e) {
    die("Erro ao buscar agendamentos: " . $e->getMessage());
}
?>

<?php
if (count($agendamentos) == 0) {
    echo "<p>Nenhum agendamento encontrado.</p>";
} else {
?>
    <table>
        <thead>
            <tr>
                <th>Paciente</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Tipo</th> 
                <th>Status</th>
                <th>Pagamento</th> <?php
                if ($tipo_usuario_logado == 'admin') {
                    echo "<th>Profissional</th>";
                }
                ?>
                
                <th>Observações</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($agendamentos as $ag) {
                
                $inicio_formatado = date('d/m/Y H:i', strtotime($ag['data_hora_inicio']));
                $fim_formatado = date('d/m/Y H:i', strtotime($ag['data_hora_fim']));
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($ag['nome_paciente']) . "</td>";
                echo "<td>" . $inicio_formatado . "</td>";
                echo "<td>" . $fim_formatado . "</td>";
                echo "<td>" . ucfirst($ag['tipo_atendimento']) . "</td>";
                echo "<td class='status-" . $ag['status'] . "'>" . ucfirst($ag['status']) . "</td>";
                
                // Adiciona uma classe CSS se estiver pendente
                $pagamento_style = ($ag['status_pagamento'] == 'Pendente') ? 'status-pendente' : 'status-pago';
                echo "<td class='{$pagamento_style}'>" . $ag['status_pagamento'] . "</td>";

                if ($tipo_usuario_logado == 'admin') {
                    echo "<td>" . htmlspecialchars($ag['nome_profissional']) . "</td>";
                }
                
                echo "<td>" . htmlspecialchars($ag['observacoes']) . "</td>";
                
                echo "<td>";
                if ($ag['status'] == 'marcado') {
                    echo "<a href='editar_agendamento.php?id=" . $ag['id'] . "'>Gerenciar</a>";
                } else {
                    echo "—";
                }
                echo "</td>";
                
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
<?php 
} 
?>

<?php
// 10. Inclui o rodapé
require_once 'footer.php'; 
?>