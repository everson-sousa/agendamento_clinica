<?php
// 1. Define o título
$tituloPagina = "Novo Agendamento";

// 2. Inclui o cabeçalho
require_once 'header.php';

// 3. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 4. Pega os dados do usuário logado
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// 5. LÓGICA DE SELEÇÃO
$id_profissional_selecionado = null;
$lista_profissionais = [];
$lista_pacientes_inicial = [];

if ($tipo_usuario_logado == 'admin') {
    // --- LÓGICA DO ADMIN ---
    try {
        $sql_profissionais = "SELECT id, nome FROM usuarios WHERE tipo_acesso = 'profissional' AND status = 'ativo' ORDER BY nome ASC";
        $stmt_profissionais = $pdo->query($sql_profissionais);
        $lista_profissionais = $stmt_profissionais->fetchAll();
    } catch (PDOException $e) {
        echo "Erro ao buscar profissionais: " . $e->getMessage();
    }
    // A lista de pacientes ($lista_pacientes_inicial) começa vazia para o Admin
    
} else {
    // --- LÓGICA DO PROFISSIONAL ---
    $id_profissional_selecionado = $id_usuario_logado;
    
    // Se for profissional, já busca sua própria lista de pacientes
    try {
        $sql_pacientes = "SELECT id, nome_completo FROM pacientes 
                          WHERE id_profissional_responsavel = ? AND status = 'ativo' 
                          ORDER BY nome_completo ASC";
        $stmt_pacientes = $pdo->prepare($sql_pacientes);
        $stmt_pacientes->execute([$id_profissional_selecionado]);
        $lista_pacientes_inicial = $stmt_pacientes->fetchAll();
    } catch (PDOException $e) {
        echo "Erro ao buscar pacientes: " . $e->getMessage();
    }
}

// 6. BUSCA TODOS OS PLANOS
// (Para o script de Resumo de Planos)
$todos_planos_json = '[]';
$id_planos_buscar = ($tipo_usuario_logado == 'admin') ? null : $id_profissional_selecionado;

if ($id_planos_buscar) {
    try {
        $sql_planos = "SELECT id_paciente, tipo_plano, tipo_atendimento, sessoes_contratadas, sessoes_utilizadas 
                       FROM planos_paciente 
                       WHERE id_profissional = ? AND status = 'Ativo'";
        $stmt_planos = $pdo->prepare($sql_planos);
        $stmt_planos->execute([$id_planos_buscar]);
        $todos_planos = $stmt_planos->fetchAll(PDO::FETCH_ASSOC);
        $todos_planos_json = json_encode($todos_planos);
    } catch (PDOException $e) {
        echo "Erro ao buscar planos: " . $e->getMessage();
    }
}
?>

<form action="processa_agendamento.php" method="POST">

    <?php if ($tipo_usuario_logado == 'admin'): ?>
        <div>
            <label for="id_profissional_ajax">Profissional:</label>
            <select id="id_profissional_ajax" name="id_profissional" required>
                <option value="">Selecione um profissional...</option>
                <?php foreach ($lista_profissionais as $profissional): ?>
                    <option value="<?php echo $profissional['id']; ?>">
                        <?php echo htmlspecialchars($profissional['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else: ?>
        <input type="hidden" name="id_profissional" value="<?php echo $id_usuario_logado; ?>">
    <?php endif; ?>


    <div>
        <label for="id_paciente">Paciente:</label>
        <select id="id_paciente" name="id_paciente" required 
            <?php if ($tipo_usuario_logado == 'admin') echo 'disabled'; // Começa desabilitado para o Admin ?>
        >
            <option value="">
                <?php echo ($tipo_usuario_logado == 'admin') ? 'Selecione um profissional acima' : 'Selecione seu paciente...'; ?>
            </option>
            
            <?php foreach ($lista_pacientes_inicial as $paciente): ?>
                <option value="<?php echo $paciente['id']; ?>">
                    <?php echo htmlspecialchars($paciente['nome_completo']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small style="display:block; margin-top:5px;">
            Paciente não está na lista? 
            <a href="cadastrar_paciente.php">Cadastrar novo paciente</a>.
        </small>
    </div>

    <div id="info-planos-paciente" style="display: none; padding: 10px; background-color: #f4f7fa; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 15px;">
    </div>

    <div>
        <label for="tipo_atendimento">Tipo de Atendimento:</label>
        <select id="tipo_atendimento" name="tipo_atendimento" required>
            <option value="terapia">Terapia (Sessão)</option>
            <option value="plantao">Plantão</option>
            <option value="avaliacao">Avaliação</option>
        </select>
    </div>
    <div>
        <label for="data_inicio">Data da Consulta:</label>
        <input type="date" id="data_inicio" name="data_inicio" required>
    </div>
    <div>
        <label for="hora_inicio">Hora de Início:</label>
        <input type="time" id="hora_inicio" name="hora_inicio" required>
    </div>
    <div>
        <label for="hora_fim">Hora de Fim:</label>
        <input type="time" id="hora_fim" name="hora_fim" required>
    </div>
    <div>
        <label for="observacoes">Observações (Opcional):</label>
        <textarea id="observacoes" name="observacoes" rows="4"></textarea>
    </div>
    
    <button type="submit">Salvar Agendamento</button>
</form>

<script>
// Pega os elementos do formulário
const profissionalSelect = document.getElementById('id_profissional_ajax'); // O dropdown de profissional
const pacienteSelect = document.getElementById('id_paciente'); // O dropdown de paciente
const tipoAtendimentoSelect = document.getElementById('tipo_atendimento');
const infoDiv = document.getElementById('info-planos-paciente');

let planosData = <?php echo $todos_planos_json; ?>;

// --- A MÁGICA DO AJAX ---
if (profissionalSelect) { // Se o seletor de profissional existir (ou seja, se for Admin)
    
    // "Escuta" a mudança no dropdown de profissional
    profissionalSelect.addEventListener('change', function() {
        const idProfissional = this.value;
        
        pacienteSelect.innerHTML = '<option value="">Carregando...</option>';
        pacienteSelect.disabled = true;
        infoDiv.style.display = 'none'; // Esconde o resumo

        if (!idProfissional) {
            pacienteSelect.innerHTML = '<option value="">Selecione um profissional acima</option>';
            return;
        }

        // Esta é a chamada AJAX!
        fetch(`buscar_pacientes.php?id_profissional=${idProfissional}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao buscar pacientes');
                }
                return response.json();
            })
            .then(pacientes => {
                // Sucesso! O PHP respondeu com a lista de pacientes
                pacienteSelect.innerHTML = '<option value="">Selecione um paciente</option>';
                pacientes.forEach(paciente => {
                    const option = document.createElement('option');
                    option.value = paciente.id;
                    option.textContent = paciente.nome_completo;
                    pacienteSelect.appendChild(option);
                });
                pacienteSelect.disabled = false; // Habilita o dropdown de paciente
                
                // (Nota: A busca de planos para o Admin ainda não foi feita,
                // então o resumo de planos não aparecerá para o Admin)
            })
            .catch(error => {
                console.error('Erro no AJAX:', error);
                pacienteSelect.innerHTML = '<option value="">Erro ao carregar pacientes</option>';
            });
    });
}

// --- LÓGICA DO RESUMO DE PLANOS (AGORA CORRIGIDA, SEM '...') ---
function atualizarInfoPlanos() {
    const pacienteId = pacienteSelect.value;
    const tipoAtendimento = tipoAtendimentoSelect.value;
    infoDiv.innerHTML = '';
    
    if (!pacienteId) {
        infoDiv.style.display = 'none';
        return;
    }
    
    // (Lembrete: 'planosData' só é preenchido para o Profissional)
    const planosTratamento = planosData.filter(p => 
        p.id_paciente == pacienteId && p.tipo_plano == 'Tratamento' && p.tipo_atendimento == tipoAtendimento
    );
    const planosPacote = planosData.filter(p => 
        p.id_paciente == pacienteId && p.tipo_plano == 'Pacote' && p.tipo_atendimento == tipoAtendimento
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

pacienteSelect.addEventListener('change', atualizarInfoPlanos);
tipoAtendimentoSelect.addEventListener('change', atualizarInfoPlanos);
</script>

<?php
require_once 'footer.php';
?>