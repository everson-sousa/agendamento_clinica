<?php
// 1. Define o título desta página
$tituloPagina = "Editar Agendamento";

// 2. Inclui o cabeçalho (que já tem a segurança)
require_once 'header.php';

// 3. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 4. Pega o ID do agendamento da URL
if (!isset($_GET['id'])) {
    die("Erro: ID do agendamento não fornecido.");
}
$id_agendamento = $_GET['id'];

// 5. Busca os dados do agendamento no banco
try {
    $sql = "SELECT * FROM agendamentos WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_agendamento]);
    $agendamento = $stmt->fetch();

    if (!$agendamento) {
        die("Erro: Agendamento não encontrado.");
    }

} catch (PDOException $e) {
    die("Erro ao buscar agendamento: " . $e->getMessage());
}

// 6. CHECAGEM DE PERMISSÃO
// (O $tipo_usuario e $id_usuario_logado vêm do header.php)
$id_usuario_logado = $_SESSION['usuario_id'];
if ($tipo_usuario != 'admin' && $agendamento['id_profissional'] != $id_usuario_logado) {
    die("Acesso negado. Você não tem permissão para editar este agendamento.");
}

// --- Se chegou aqui, está tudo OK ---

// 7. "Quebra" a data e hora para preencher os campos do formulário
$data_inicio_val = date('Y-m-d', strtotime($agendamento['data_hora_inicio']));
$hora_inicio_val = date('H-i', strtotime($agendamento['data_hora_inicio']));
$hora_fim_val    = date('H-i', strtotime($agendamento['data_hora_fim']));

?>

<form action="processa_edicao_agendamento.php" method="POST">
    
    <input type="hidden" name="id_agendamento" value="<?php echo $agendamento['id']; ?>">

    <div>
        <label for="nome_paciente">Nome do Paciente:</label>
        <input type="text" id="nome_paciente" name="nome_paciente" 
               value="<?php echo htmlspecialchars($agendamento['nome_paciente']); ?>" required>
    </div>
    
    <div>
        <label for="data_inicio">Data da Consulta:</label>
        <input type="date" id="data_inicio" name="data_inicio" 
               value="<?php echo $data_inicio_val; ?>" required>
    </div>
    <div>
        <label for="hora_inicio">Hora de Início:</label>
        <input type="time" id="hora_inicio" name="hora_inicio" 
               value="<?php echo $hora_inicio_val; ?>" required>
    </div>
    <div>
        <label for="hora_fim">Hora de Fim:</label>
        <input type="time" id="hora_fim" name="hora_fim" 
               value="<?php echo $hora_fim_val; ?>" required>
    </div>
    
    <div>
        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="marcado" <?php if($agendamento['status'] == 'marcado') echo 'selected'; ?>>
                Marcado
            </option>
            <option value="realizado" <?php if($agendamento['status'] == 'realizado') echo 'selected'; ?>>
                Realizado (Concluído)
            </option>
            <option value="cancelado" <?php if($agendamento['status'] == 'cancelado') echo 'selected'; ?>>
                Cancelado
            </option>
        </select>
    </div>

    <div>
        <label for="observacoes">Observações (Opcional):</label>
        <textarea id="observacoes" name="observacoes" rows="4"><?php echo htmlspecialchars($agendamento['observacoes']); ?></textarea>
    </div>
    
    <button type="submit">Salvar Alterações</button>
</form>

<script>
    const form = document.querySelector('form');
    const statusSelect = document.getElementById('status');
    const statusOriginal = '<?php echo $agendamento['status']; ?>';

    form.addEventListener('submit', function(event) {
        const statusNovo = statusSelect.value;
        
        // SÓ mostra o aviso se o status MUDOU e é para um estado final
        if (statusNovo !== statusOriginal && (statusNovo === 'realizado' || statusNovo === 'cancelado')) {
            const confirmacao = confirm(
                "ATENÇÃO!\n\n" +
                "Você está prestes a finalizar este agendamento (marcar como 'Realizado' ou 'Cancelado').\n\n" +
                "Esta ação não poderá ser revertida.\n\n" +
                "Deseja continuar?"
            );

            if (!confirmacao) {
                event.preventDefault(); // Impede o envio do formulário
            }
        }
    });
</script>

<?php
// 8. Inclui o rodapé
require_once 'footer.php'; 
?>