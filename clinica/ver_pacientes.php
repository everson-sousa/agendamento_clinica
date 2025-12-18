<?php
require_once 'auth.php';
$tituloPagina = "Ver Pacientes";
require_once 'header.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
// 1. Define o título
$tituloPagina = "Ver Pacientes"; 

// 2. Inclui o cabeçalho
require_once 'header.php'; 

// 3. Inclui a conexão
require_once 'conexao.php'; 

// 4. Pega o ID e Tipo do usuário da SESSÃO
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// 5. LÓGICA SQL (A MÁGICA DA PRIVACIDADE)
// Seleciona os pacientes E o nome do profissional responsável
$sql_base = "SELECT 
                pac.*, 
                usr.nome AS nome_profissional 
            FROM 
                pacientes AS pac
            JOIN 
                usuarios AS usr ON pac.id_profissional_responsavel = usr.id";

$params = []; 

// Se for profissional, filtra a busca
if ($tipo_usuario_logado == 'profissional') {
    $sql_base .= " WHERE pac.id_profissional_responsavel = ?";
    $params[] = $id_usuario_logado;
}

$sql_base .= " ORDER BY pac.nome_completo ASC";

// 6. Executa a consulta
try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $pacientes = $stmt->fetchAll(); 

} catch (PDOException $e) {
    die("Erro ao buscar pacientes: " . $e->getMessage());
}

?>

<?php
if (count($pacientes) == 0) {
    
    echo "<p>Nenhum paciente cadastrado.</p>";
    echo "<p><a href='cadastrar_paciente.php'>Cadastrar seu primeiro paciente</a>.</p>";

} else {
?>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Status</th>
                
                <?php
                // O Admin vê para quem é o paciente
                if ($tipo_usuario_logado == 'admin') {
                    echo "<th>Profissional Responsável</th>";
                }
                ?>
                
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($pacientes as $paciente) {
                echo "<td>";
                echo "<a href='detalhe_paciente.php?id=" . $paciente['id'] . "'>";
                echo htmlspecialchars($paciente['nome_completo']);
                echo "</a>";
                echo "</td>";

                // O Admin vê o nome do profissional
                if ($tipo_usuario_logado == 'admin') {
                    echo "<td>" . htmlspecialchars($paciente['nome_profissional']) . "</td>";
                }
                
                // Links para o "U" e "D" do CRUD
                echo "<td>";
                echo "<a href='editar_paciente.php?id=" . $paciente['id'] . "'>Editar</a>";
                // (Vamos adicionar o "Desativar" depois)
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
// 7. Inclui o rodapé
require_once 'footer.php'; 
?>