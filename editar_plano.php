<?php
$tituloPagina = "Editar Plano/Pacote"; 
require_once 'header.php'; 
require_once 'conexao.php'; 

// Pega o ID do plano
if (!isset($_GET['id'])) {
    die("ID do plano não fornecido.");
}
$id_plano = $_GET['id'];

// Busca dados do plano
try {
    $sql = "SELECT plan.*, pac.nome_completo 
            FROM planos_paciente AS plan 
            JOIN pacientes AS pac ON plan.id_paciente = pac.id
            WHERE plan.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_plano]);
    $plano = $stmt->fetch();

    if (!$plano) {
        die("Plano não encontrado.");
    }

} catch (PDOException $e) {
    die("Erro ao buscar plano: " . $e->getMessage());
}

// Checagem de Permissão
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];
if ($tipo_usuario_logado != 'admin' && $plano['id_profissional'] != $id_usuario_logado) {
    die("Acesso negado.");
}
?>

<form action="processa_edicao_plano.php" method="POST">
    <input type="hidden" name="id_plano" value="<?php echo $plano['id']; ?>">

    <h3>Editando Plano/Pacote</h3>
    
    <div>
        <label>Paciente:</label>
        <input type="text" value="<?php echo htmlspecialchars($plano['nome_completo']); ?>" readonly>
    </div>
    
    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <label>Tipo de Registro:</label>
            <input type="text" value="<?php echo htmlspecialchars($plano['tipo_plano']); ?>" readonly>
        </div>
        <div style="flex: 1;">
            <label>Tipo de Atendimento:</label>
            <input type="text" value="<?php echo htmlspecialchars(ucfirst($plano['tipo_atendimento'])); ?>" readonly>
        </div>
    </div>
    
    <hr style="margin: 20px 0;">

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <label for="sessoes_contratadas">Sessões Contratadas (Total):</label>
            <input type="number" name="sessoes_contratadas" 
                   value="<?php echo $plano['sessoes_contratadas']; ?>" min="1" required>
        </div>
        <div style="flex: 1;">
            <label>Sessões Já Utilizadas:</label>
            <input type="text" value="<?php echo $plano['sessoes_utilizadas']; ?>" readonly>
        </div>
    </div>
    
    <div>
        <label for="status">Status do Plano/Pacote:</label>
        <select id="status" name="status" required>
            <option value="Ativo" <?php if($plano['status'] == 'Ativo') echo 'selected'; ?>>
                Ativo
            </option>
            <option value="Concluido" <?php if($plano['status'] == 'Concluido') echo 'selected'; ?>>
                Concluído
            </option>
        </select>
        <small>Selecione "Concluído" para finalizar manualmente este plano (Ex: Avaliação).</small>
    </div>

    <button type="submit">Salvar Alterações no Plano</button>
</form>

<?php
require_once 'footer.php'; 
?>