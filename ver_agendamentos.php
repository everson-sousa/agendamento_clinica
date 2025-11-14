<?php
// 1. Define o título desta página
$tituloPagina = "Ver Agendamentos"; 

// 2. Inclui o cabeçalho (que já tem a segurança e o menu)
require_once 'header.php'; 

// 3. Inclui a conexão (o header NÃO faz isso)
require_once 'conexao.php'; 

// 4. Pega o ID e Tipo do usuário da SESSÃO (o header só pegou o nome)
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo']; // Já está na var $tipo_usuario do header, mas ok repetir

// 5. Lógica SQL (exatamente como era antes)
$sql_base = "SELECT 
                ag.*, 
                usr.nome AS nome_profissional 
            FROM 
                agendamentos AS ag
            JOIN 
                usuarios AS usr ON ag.id_profissional = usr.id";

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
// 7. Verifica se há agendamentos para mostrar
if (count($agendamentos) == 0) {
    
    echo "<p>Nenhum agendamento encontrado.</p>";

} else {
    // 8. Se houver, cria a tabela (o style.css já cuida do visual)
?>
    <table>
        <thead>
            <tr>
                <th>Paciente</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Status</th>
                
                <?php
                // Admin vê a coluna "Profissional"
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
            // 9. Loop para exibir cada agendamento
            foreach ($agendamentos as $ag) {
                
                // Formata as datas para ficar mais amigável
                $inicio_formatado = date('d/m/Y H:i', strtotime($ag['data_hora_inicio']));
                $fim_formatado = date('d/m/Y H:i', strtotime($ag['data_hora_fim']));
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($ag['nome_paciente']) . "</td>";
                echo "<td>" . $inicio_formatado . "</td>";
                echo "<td>" . $fim_formatado . "</td>";
                
                // Adiciona a classe CSS para colorir o status
                echo "<td class='status-" . $ag['status'] . "'>" . ucfirst($ag['status']) . "</td>";

                if ($tipo_usuario_logado == 'admin') {
                    echo "<td>" . htmlspecialchars($ag['nome_profissional']) . "</td>";
                }
                
                echo "<td>" . htmlspecialchars($ag['observacoes']) . "</td>";
                
                echo "<td>";
                // Lógica de "Gerenciar" que já fizemos
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
} // Fim do "else" 
?>

<?php
// 10. Inclui o rodapé
require_once 'footer.php'; 
?>