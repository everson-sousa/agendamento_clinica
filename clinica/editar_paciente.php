<?php
// 1. Define o título
$tituloPagina = "Editar Paciente"; 

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

// 5. Busca os dados do paciente
try {
    $sql = "SELECT * FROM pacientes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_paciente]);
    $paciente = $stmt->fetch();

    if (!$paciente) {
        die("Erro: Paciente não encontrado.");
    }

} catch (PDOException $e) {
    die("Erro ao buscar paciente: " . $e->getMessage());
}

// 6. CHECAGEM DE PERMISSÃO
// Se não for admin, verifica se é o profissional "dono" do paciente
if ($tipo_usuario_logado != 'admin' && $paciente['id_profissional_responsavel'] != $id_usuario_logado) {
    die("Acesso negado. Você não tem permissão para editar este paciente.");
}
?>

<form action="processa_edicao_paciente.php" method="POST">
    
    <input type="hidden" name="id_paciente" value="<?php echo $paciente['id']; ?>">

    <div>
        <label for="nome_completo">Nome Completo:</label>
        <input type="text" id="nome_completo" name="nome_completo" 
               value="<?php echo htmlspecialchars($paciente['nome_completo']); ?>" required>
    </div>
    <div>
        <label for="email">E-mail (Opcional):</label>
        <input type="email" id="email" name="email"
               value="<?php echo htmlspecialchars($paciente['email']); ?>">
    </div>
    <div>
        <label for="telefone">Telefone (Opcional):</label>
        <input type="text" id="telefone" name="telefone"
               value="<?php echo htmlspecialchars($paciente['telefone']); ?>">
    </div>
    <div>
        <label for="data_nascimento">Data de Nascimento (Opcional):</label>
        <input type="date" id="data_nascimento" name="data_nascimento"
               value="<?php echo $paciente['data_nascimento']; ?>">
    </div>
    <div>
        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="ativo" <?php if($paciente['status'] == 'ativo') echo 'selected'; ?>>
                Ativo
            </option>
            <option value="inativo" <?php if($paciente['status'] == 'inativo') echo 'selected'; ?>>
                Inativo
            </option>
        </select>
    </div>
    
    <button type="submit">Salvar Alterações</button>
</form>

<?php
// 7. Inclui o rodapé
require_once 'footer.php'; 
?>