<?php
// 1. Inicia a sessão e faz a verificação de segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 3. Pega o ID do agendamento da URL
if (!isset($_GET['id'])) {
    die("Erro: ID do agendamento não fornecido.");
}
$id_agendamento = $_GET['id'];

// 4. Busca os dados do agendamento no banco
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

// 5. CHECAGEM DE PERMISSÃO (MUITO IMPORTANTE)
// O usuário é Admin? Se não, ele é o "dono" desse agendamento?
if ($_SESSION['usuario_tipo'] != 'admin' && $agendamento['id_profissional'] != $_SESSION['usuario_id']) {
    die("Acesso negado. Você não tem permissão para editar este agendamento.");
}
// ADICIONE ESTAS 4 LINHAS:
// Pega os dados do banco e "quebra" em data e hora
$data_inicio_val = date('Y-m-d', strtotime($agendamento['data_hora_inicio']));
$hora_inicio_val = date('H:i', strtotime($agendamento['data_hora_inicio']));
$hora_fim_val    = date('H:i', strtotime($agendamento['data_hora_fim']));
// --- Se chegou aqui, está tudo OK ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Agendamento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { max-width: 500px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="datetime-local"], select, textarea { 
            width: 100%; padding: 8px; box-sizing: border-box; 
        }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header a { text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Editar Agendamento</h2>
        <a href="ver_agendamentos.php">Voltar para a Lista</a>
    </div>

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
    </form>

    <script>
        // Pega o formulário pelo nome da tag
        const form = document.querySelector('form');
        
        // Pega o dropdown de status pelo ID
        const statusSelect = document.getElementById('status');
        
        // Pega o status original que veio do banco
        const statusOriginal = '<?php echo $agendamento['status']; ?>';

        // Adiciona um "escutador" ao evento de submit do formulário
        form.addEventListener('submit', function(event) {
            
            // Pega o NOVO valor que o usuário selecionou
            const statusNovo = statusSelect.value;

            // O aviso SÓ aparece se o status MUDOU e se o NOVO status for final
            if (statusNovo !== statusOriginal && (statusNovo === 'realizado' || statusNovo === 'cancelado')) {
                
                // Mostra a caixa de confirmação nativa do navegador
                const confirmacao = confirm(
                    "ATENÇÃO!\n\nVocê está prestes a finalizar este agendamento (marcar como 'Realizado' ou 'Cancelado').\n\nEsta ação não poderá ser revertida.\n\nDeseja continuar?"
                );

                // Se o usuário clicar em "Cancelar" no pop-up
                if (!confirmacao) {
                    event.preventDefault(); // Impede o envio do formulário
                }
            }
        });
    </script>

</body>
</html>

</body>
</html>