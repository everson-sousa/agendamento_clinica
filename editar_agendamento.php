<?php
// 1. Define o título
$tituloPagina = "Editar Agendamento";

// 2. Inclui o cabeçalho
require_once 'header.php';

// 3. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 4. Pega o ID do agendamento da URL
if (!isset($_GET['id'])) {
    die("Erro: ID do agendamento não fornecido.");
}
$id_agendamento = $_GET['id'];
$id_usuario_logado = $_SESSION['usuario_id'];

// 5. Busca os dados do agendamento
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
if ($tipo_usuario != 'admin' && $agendamento['id_profissional'] != $id_usuario_logado) {
    die("Acesso negado.");
}

// 7. LÓGICA: Buscar os PACIENTES deste profissional (para o dropdown)
$lista_pacientes = [];
try {
    $sql_pacientes = "SELECT id, nome_completo 
                      FROM pacientes 
                      WHERE id_profissional_responsavel = ? AND status = 'ativo' 
                      ORDER BY nome_completo ASC";
    
    $stmt_pacientes = $pdo->prepare($sql_pacientes);
    // Usa o ID do "dono" do agendamento para carregar a lista correta
    $stmt_pacientes->execute([ $agendamento['id_profissional'] ]); 
    $lista_pacientes = $stmt_pacientes->fetchAll();

} catch (PDOException $e) {
    echo "Erro ao buscar pacientes: " . $e->getMessage();
}

// 8. "Quebra" data e hora
$data_inicio_val = date('Y-m-d', strtotime($agendamento['data_hora_inicio']));
$hora_inicio_val = date('H:i', strtotime($agendamento['data_hora_inicio'])); 
$hora_fim_val    = date('H:i', strtotime($agendamento['data_hora_fim']));    
?>

<form action="processa_edicao_agendamento.php" method="POST">
    
    <input type="hidden" name="id_agendamento" value="<?php echo $agendamento['id']; ?>">

    <div>
        <label for="id_paciente">Paciente:</label>
        <select id="id_paciente" name="id_paciente" required>
            <option value="">Selecione um paciente</option>
            <?php foreach ($lista_pacientes as $paciente): ?>
                <option value="<?php echo $paciente['id']; ?>"
                    <?php 
                    if ($paciente['id'] == $agendamento['id_paciente']) {
                        echo 'selected';
                    } 
                    ?>
                >
                    <?php echo htmlspecialchars($paciente['nome_completo']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small style="display:block; margin-top:5px;">
            Paciente não está na lista? 
            <a href="cadastrar_paciente.php">Cadastrar novo paciente</a>.
        </small>
    </div>

    <div>
        <label for="tipo_atendimento">Tipo de Atendimento:</label>
        <select id="tipo_atendimento" name="tipo_atendimento" required>
            <option value="terapia" <?php if($agendamento['tipo_atendimento'] == 'terapia') echo 'selected'; ?>>
                Terapia (Sessão)
            </option>
            <option value="plantao" <?php if($agendamento['tipo_atendimento'] == 'plantao') echo 'selected'; ?>>
                Plantão
            </option>
            <option value="avaliacao" <?php if($agendamento['tipo_atendimento'] == 'avaliacao') echo 'selected'; ?>>
                Avaliação
            </option>
        </select>
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
        <label for="status">Status da Consulta:</label>
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
        <label for="status_pagamento">Status do Pagamento:</label>
        <select id="status_pagamento" name="status_pagamento" required>
            <option value="Pendente" <?php if($agendamento['status_pagamento'] == 'Pendente') echo 'selected'; ?>>
                Pendente
            </option>
            <option value="Pago" <?php if($agendamento['status_pagamento'] == 'Pago') echo 'selected'; ?>>
                Pago
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
        if (statusNovo !== statusOriginal && (statusNovo === 'realizado' || statusNovo === 'cancelado')) {
            const confirmacao = confirm("ATENÇÃO!\n\nEsta ação não poderá ser revertida.\n\nDeseja continuar?");
            if (!confirmacao) {
                event.preventDefault();
            }
        }
    });
</script>

<?php
// 9. Inclui o rodapé
require_once 'footer.php'; 
?>