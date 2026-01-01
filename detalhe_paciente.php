<?php
// 1. Define o título
$tituloPagina = "Prontuário do Paciente"; 

// 2. Inclui o cabeçalho
require_once 'header.php'; 

// 3. Inclui a conexão
require_once 'conexao.php'; 

// 4. Pega o ID do paciente da URL
if (!isset($_GET['id'])) {
    die("Erro: ID do paciente não fornecido.");
}
$id_paciente = $_GET['id'];
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// --- BUSCA 1: DADOS DO PACIENTE ---
try {
    $sql_paciente = "SELECT * FROM pacientes WHERE id = ?";
    $stmt_paciente = $pdo->prepare($sql_paciente);
    $stmt_paciente->execute([$id_paciente]);
    $paciente = $stmt_paciente->fetch();

    if (!$paciente) {
        die("Erro: Paciente não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro ao buscar paciente: " . $e->getMessage());
}

// 6. CHECAGEM DE PERMISSÃO
if ($tipo_usuario_logado != 'admin' && $paciente['id_profissional_responsavel'] != $id_usuario_logado) {
    die("Acesso negado.");
}

// --- BUSCA 2: HISTÓRICO DE AGENDAMENTOS ---
try {
    $sql_historico = "SELECT * FROM agendamentos 
                      WHERE id_paciente = ? 
                      ORDER BY data_hora_inicio DESC"; // Mais recentes primeiro
    
    $stmt_historico = $pdo->prepare($sql_historico);
    $stmt_historico->execute([$id_paciente]);
    $historico = $stmt_historico->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar histórico: " . $e->getMessage());
}

?>

<form>
    <h3>Dados do Paciente</h3>
    <div>
        <label>Nome Completo:</label>
        <input type="text" value="<?php echo htmlspecialchars($paciente['nome_completo']); ?>" readonly>
    </div>
    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <label>E-mail:</label>
            <input type="text" value="<?php echo htmlspecialchars($paciente['email']); ?>" readonly>
        </div>
        <div style="flex: 1;">
            <label>Telefone:</label>
            <input type="text" value="<?php echo htmlspecialchars($paciente['telefone']); ?>" readonly>
        </div>
    </div>
    <a href="editar_paciente.php?id=<?php echo $paciente['id']; ?>">Editar dados do paciente</a>
</form>

<hr style="margin: 30px 0;">

<h3>Histórico de Atendimentos</h3>

<?php if (count($historico) == 0): ?>
    <p>Nenhum atendimento registrado para este paciente.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Data/Hora Início</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Observações (Resumo)</th> </tr>
        </thead>
        <tbody>
            <?php foreach ($historico as $atendimento): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($atendimento['data_hora_inicio'])); ?></td>
                    <td><?php echo ucfirst($atendimento['tipo_atendimento']); ?></td>
                    <td class="status-<?php echo $atendimento['status']; ?>">
                        <?php echo ucfirst($atendimento['status']); ?>
                    </td>
                    
                    <td><?php echo nl2br(htmlspecialchars($atendimento['observacoes'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>


<?php
// 7. Inclui o rodapé
require_once 'footer.php'; 
?>