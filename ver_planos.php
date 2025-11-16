<?php
// 1. Define o título
$tituloPagina = "Gerenciar Planos e Pacotes"; 

// 2. Inclui o cabeçalho
require_once 'header.php'; 

// 3. Inclui a conexão
require_once 'conexao.php'; 

// 4. Pega o ID e Tipo do usuário da SESSÃO
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// 5. LÓGICA SQL
// Seleciona os planos, o nome do paciente e o nome do profissional
$sql_base = "SELECT 
                plan.*, 
                pac.nome_completo AS nome_paciente,
                usr.nome AS nome_profissional
            FROM 
                planos_paciente AS plan
            JOIN 
                pacientes AS pac ON plan.id_paciente = pac.id
            JOIN 
                usuarios AS usr ON plan.id_profissional = usr.id";

$params = []; 

// Se for profissional, filtra a busca
if ($tipo_usuario_logado == 'profissional') {
    $sql_base .= " WHERE plan.id_profissional = ?";
    $params[] = $id_usuario_logado;
}

// Ordena por paciente e data de criação
$sql_base .= " ORDER BY pac.nome_completo ASC, plan.data_criacao DESC";

// 6. Executa a consulta
try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $planos = $stmt->fetchAll(); 

} catch (PDOException $e) {
    die("Erro ao buscar planos: " . $e->getMessage());
}

?>

<div style="margin-bottom: 20px;">
    <a href="adicionar_plano.php" style="text-decoration: none; padding: 10px 15px; background-color: #2ecc71; color: white; border-radius: 4px; font-weight: bold;">
        + Adicionar Novo Plano/Pacote
    </a>
</div>

<?php
if (count($planos) == 0) {
    echo "<p>Nenhum plano ou pacote cadastrado.</p>";
} else {
?>
    <table>
        <thead>
            <tr>
                <th>Paciente</th>
                <th>Tipo</th>
                <th>Atendimento</th>
                <th>Progresso (Sessões)</th>
                <th>Status</th>
                
                <?php
                if ($tipo_usuario_logado == 'admin') {
                    echo "<th>Profissional</th>";
                }
                ?>
                
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($planos as $plano) {
                // Calcula o saldo
                $saldo = $plano['sessoes_contratadas'] - $plano['sessoes_utilizadas'];
                $progresso = $plano['sessoes_utilizadas'] . ' / ' . $plano['sessoes_contratadas'];
                
                // Muda a cor se o saldo for baixo
                $style = '';
                if ($plano['status'] == 'Ativo' && $saldo <= 1) {
                    $style = ' style="background-color: #fffbe6; color: #f39c12;"'; // Amarelo de atenção
                }
                if ($plano['status'] == 'Concluido') {
                    $style = ' style="color: #95a5a6;"'; // Cinza para concluído
                }

                echo "<tr{$style}>";
                echo "<td>" . htmlspecialchars($plano['nome_paciente']) . "</td>";
                echo "<td>" . htmlspecialchars($plano['tipo_plano']) . "</td>";
                echo "<td>" . htmlspecialchars($plano['tipo_atendimento']) . "</td>";
                
                // Mostra o progresso
                echo "<td><b>" . $progresso . "</b></td>";
                
                echo "<td>" . htmlspecialchars($plano['status']) . "</td>";

                // Admin vê o nome do profissional
                if ($tipo_usuario_logado == 'admin') {
                    echo "<td>" . htmlspecialchars($plano['nome_profissional']) . "</td>";
                }
                
                // Links para o "U" e "D" do CRUD
                echo "<td>";
                echo "<a href='editar_plano.php?id=" . $plano['id'] . "'>Editar</a>";
                echo "</td>";
                
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
<?php 
} // Fim do "else" 
?>

<?php
// 8. Inclui o rodapé
require_once 'footer.php'; 
?>