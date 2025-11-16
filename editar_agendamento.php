<?php
$tituloPagina = "Editar Agendamento";
require_once 'header.php';
require_once 'conexao.php'; 

$id_agendamento = $_GET['id'];
$id_usuario_logado = $_SESSION['usuario_id'];

// --- Busca 1: O Agendamento ---
try {
    $sql = "SELECT * FROM agendamentos WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_agendamento]);
    $agendamento = $stmt->fetch();
    if (!$agendamento) { die("Erro: Agendamento não encontrado."); }
} catch (PDOException $e) {
    die("Erro ao buscar agendamento: " . $e->getMessage());
}

// --- Checagem de Permissão ---
if ($tipo_usuario != 'admin' && $agendamento['id_profissional'] != $id_usuario_logado) {
    die("Acesso negado.");
}

// Pega o ID do profissional "dono" do agendamento
$id_profissional_agendamento = $agendamento['id_profissional'];

// --- Busca 2: Lista de Pacientes (deste profissional) ---
$lista_pacientes = [];
try {
    $sql_pacientes = "SELECT id, nome_completo FROM pacientes 
                      WHERE id_profissional_responsavel = ? AND status = 'ativo' 
                      ORDER BY nome_completo ASC";
    $stmt_pacientes = $pdo->prepare($sql_pacientes);
    $stmt_pacientes->execute([$id_profissional_agendamento]); // Carrega pacientes do "dono"
    $lista_pacientes = $stmt_pacientes->fetchAll();
} catch (PDOException $e) {
    echo "Erro ao buscar pacientes: " . $e->getMessage();
}

// --- Busca 3: Planos (deste profissional) ---
$todos_planos_json = '[]';
try {
    $sql_planos = "SELECT id_paciente, tipo_plano, tipo_atendimento, sessoes_contratadas, sessoes_utilizadas 
                   FROM planos_paciente 
                   WHERE id_profissional = ? AND status = 'Ativo'";
    $stmt_planos = $pdo->prepare($sql_planos);
    $stmt_planos->execute([$id_profissional_agendamento]);
    $todos_planos = $stmt_planos->fetchAll(PDO::FETCH_ASSOC);
    $todos_planos_json = json_encode($todos_planos);
} catch (PDOException $e) {
    echo "Erro ao buscar planos: " . $e->getMessage();
}

// --- Prepara dados para o formulário ---
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
                    <?php if ($paciente['id'] == $agendamento['id_paciente']) echo 'selected'; ?>
                >
                    <?php echo htmlspecialchars($paciente['nome_completo']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="info-planos-paciente" style="display: none; padding: 10px; background-color: #f4f7fa; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
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
        <input type="date" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio_val; ?>" required>
    </div>
    <div>
        <label for="hora_inicio">Hora de Início:</label>
        <input type="time" id="hora_inicio" name="hora_inicio" value="<?php echo $hora_inicio_val; ?>" required>
    </div>
    <div>
        <label for="hora_fim">Hora de Fim:</label>
        <input type="time" id="hora_fim" name="hora_fim" value="<?php echo $hora_fim_val; ?>" required>
    </div>
    <div>
        <label for="status">Status da Consulta:</label>
        <select id="status" name="status" required>
            <option value="marcado" <?php if($agendamento['status'] == 'marcado') echo 'selected'; ?>>Marcado</option>
            <option value="realizado" <?php if($agendamento['status'] == 'realizado') echo 'selected'; ?>>Realizado (Concluído)</option>
            <option value="cancelado" <?php if($agendamento['status'] == 'cancelado') echo 'selected'; ?>>Cancelado</option>
        </select>
    </div>
    <div>
        <label for="status_pagamento">Status do Pagamento:</label>
        <select id="status_pagamento" name="status_pagamento" required>
            <option value="Pendente" <?php if($agendamento['status_pagamento'] == 'Pendente') echo 'selected'; ?>>Pendente</option>
            <option value="Pago" <?php if($agendamento['status_pagamento'] == 'Pago') echo 'selected'; ?>>Pago</option>
        </select>
    </div>
    <div>
        <label for="observacoes">Observações (Opcional):</label>
        <textarea id="observacoes" name="observacoes" rows="4"><?php echo htmlspecialchars($agendamento['observacoes']); ?></textarea>
    </div>
    
    <button type="submit">Salvar Alterações</button>
</form>

<script>
const planosData = <?php echo $todos_planos_json; ?>;
const pacienteSelect = document.getElementById('id_paciente');
const tipoAtendimentoSelect = document.getElementById('tipo_atendimento');
const infoDiv = document.getElementById('info-planos-paciente');

function atualizarInfoPlanos() {
    const pacienteId = pacienteSelect.value;
    const tipoAtendimento = tipoAtendimentoSelect.value;
    infoDiv.innerHTML = '';
    
    if (!pacienteId) {
        infoDiv.style.display = 'none';
        return;
    }

    const planosTratamento = planosData.filter(p => 
        p.id_paciente == pacienteId && 
        p.tipo_plano == 'Tratamento' && 
        p.tipo_atendimento.toLowerCase() == tipoAtendimento
    );
    
    const planosPacote = planosData.filter(p => 
        p.id_paciente == pacienteId && 
        p.tipo_plano == 'Pacote' &&
        p.tipo_atendimento.toLowerCase() == tipoAtendimento
    );

    let html = '<strong>Resumo de Planos:</strong><br>';
    let temPlano = false;

    if (planosTratamento.length > 0) {
        temPlano = true;
        planosTratamento.forEach(plano => {
            let restantes = plano.sessoes_contratadas - plano.sessoes_utilizadas;
            html += `Plano de Tratamento: ${restantes}/${plano.sessoes_contratadas} sessões restantes.<br>`;
        });
    }

    if (planosPacote.length > 0) {
        temPlano = true;
        planosPacote.forEach(plano => {
            let restantes = plano.sessoes_contratadas - plano.sessoes_utilizadas;
            html += `Pacote Pago: ${restantes}/${plano.sessoes_contratadas} sessões restantes.<br>`;
        });
    }

    if (!temPlano) {
        html += 'Nenhum plano ou pacote ativo encontrado para este tipo de atendimento.';
    }

    infoDiv.innerHTML = html;
    infoDiv.style.display = 'block';
}

// "Escuta" mudanças
pacienteSelect.addEventListener('change', atualizarInfoPlanos);
tipoAtendimentoSelect.addEventListener('change', atualizarInfoPlanos);

// *** CHAMA A FUNÇÃO UMA VEZ NO INÍCIO ***
// (Para mostrar o resumo do paciente que já está selecionado)
document.addEventListener('DOMContentLoaded', atualizarInfoPlanos);
</script>

<?php
require_once 'footer.php'; 
?>