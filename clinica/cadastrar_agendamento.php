<?php
require_once 'auth.php';
require_once 'conexao.php';

$tituloPagina = "Novo Agendamento";
require_once 'header.php';

// --- Dados do usuário logado ---
$id_usuario_logado   = (int)($_SESSION['usuario_id'] ?? 0);
$tipo_usuario_logado = $_SESSION['usuario_tipo'] ?? '';

if ($id_usuario_logado <= 0 || $tipo_usuario_logado === '') {
    // auth.php normalmente já bloqueia, mas deixo um fallback
    die("Erro: Sessão inválida. Faça login novamente.");
}

// --- Carregamentos iniciais ---
$lista_profissionais      = [];
$lista_pacientes_inicial  = [];
$todos_planos_json        = '[]';
$nome_profissional_logado = null;

// ADMIN: lista profissionais (para escolher)
if ($tipo_usuario_logado === 'admin') {
    $stmt = $pdo->query("
        SELECT id, nome
        FROM usuarios
        WHERE tipo_acesso = 'profissional'
          AND status = 'ativo'
        ORDER BY nome ASC
    ");
    $lista_profissionais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // PROFISSIONAL: busca seu próprio nome (para exibir no select travado)
    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$id_usuario_logado]);
    $nome_profissional_logado = $stmt->fetchColumn() ?: 'Profissional';

    // PROFISSIONAL: pacientes iniciais
    $stmt = $pdo->prepare("
        SELECT id, nome_completo
        FROM pacientes
        WHERE id_profissional_responsavel = ?
          AND status = 'ativo'
        ORDER BY nome_completo ASC
    ");
    $stmt->execute([$id_usuario_logado]);
    $lista_pacientes_inicial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // PROFISSIONAL: planos ativos (para resumo)
    $stmt = $pdo->prepare("
        SELECT id_paciente, tipo_plano, tipo_atendimento, sessoes_contratadas, sessoes_utilizadas
        FROM planos_paciente
        WHERE id_profissional = ?
          AND status = 'Ativo'
    ");
    $stmt->execute([$id_usuario_logado]);
    $todos_planos_json = json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
}
?>

<form action="processa_agendamento.php" method="POST">

    <!-- PROFISSIONAL -->
    <?php if ($tipo_usuario_logado === 'admin'): ?>
        <div>
            <label for="id_profissional_ajax">Profissional:</label>
            <select id="id_profissional_ajax" name="id_profissional" required>
                <option value="">Selecione um profissional...</option>
                <?php foreach ($lista_profissionais as $prof): ?>
                    <option value="<?php echo (int)$prof['id']; ?>">
                        <?php echo htmlspecialchars($prof['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else: ?>
        <div>
            <label for="id_profissional_ajax">Profissional:</label>

            <!-- Mostra selecionado e travado (automático) -->
            <select id="id_profissional_ajax" disabled>
                <option value="<?php echo $id_usuario_logado; ?>" selected>
                    <?php echo htmlspecialchars($nome_profissional_logado, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            </select>

            <!-- Disabled não envia POST, então manda por hidden -->
            <input type="hidden" name="id_profissional" value="<?php echo $id_usuario_logado; ?>">
        </div>
    <?php endif; ?>

    <!-- PACIENTE -->
    <div>
        <label for="id_paciente">Paciente:</label>
        <select
            id="id_paciente"
            name="id_paciente"
            required
            <?php echo ($tipo_usuario_logado === 'admin') ? 'disabled' : ''; ?>
        >
            <option value="">Selecione um paciente...</option>
            <?php foreach ($lista_pacientes_inicial as $paciente): ?>
                <option value="<?php echo (int)$paciente['id']; ?>">
                    <?php echo htmlspecialchars($paciente['nome_completo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <small style="display:block; margin-top:5px;">
            Paciente não está na lista? <a href="cadastrar_paciente.php">Cadastrar novo paciente</a>.
        </small>
    </div>

    <div
        id="info-planos-paciente"
        style="display:none; padding:10px; background-color:#e8f4f8; border:1px solid #bce8f1; border-radius:4px; margin-bottom:15px; color:#31708f;"
    ></div>

    <!-- TIPO -->
    <div>
        <label for="tipo_atendimento">O que deseja agendar?</label>
        <select id="tipo_atendimento" name="tipo_atendimento" required>
            <option value="terapia_avulsa">Terapia (Avulsa - 1 Sessão)</option>
            <option value="terapia_pacote">Terapia Pacote (Fixo 4 Sessões)</option>
            <option value="plantao">Plantão (1 Sessão)</option>
            <option value="avaliacao">Avaliação (Fixo 10 Sessões)</option>
        </select>
    </div>

    <hr style="margin: 20px 0;">

    <h3 id="titulo-data">Data da Consulta</h3>
    <p id="texto-data" style="margin-bottom: 15px; color: #666;">Selecione a data e hora.</p>

    <div>
        <label for="data_inicio">Data:</label>
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
        <textarea id="observacoes" name="observacoes" rows="3"></textarea>
    </div>

    <button type="submit" style="background-color: #2ecc71; margin-top: 10px;">
        Confirmar Agendamento
    </button>
</form>

<script>
(() => {
  const isAdmin = <?php echo json_encode($tipo_usuario_logado === 'admin'); ?>;

  // Elements
  const profissionalSelect = document.getElementById('id_profissional_ajax'); // existe para admin e profissional
  const pacienteSelect     = document.getElementById('id_paciente');
  const tipoSelect         = document.getElementById('tipo_atendimento');
  const infoDiv            = document.getElementById('info-planos-paciente');
  const tituloData         = document.getElementById('titulo-data');
  const textoData          = document.getElementById('texto-data');

  // Planos (profissional já vem carregado; admin inicia vazio e atualiza via fetch)
  let planosData = <?php echo $todos_planos_json; ?>;

  // Helpers
  const setPacienteLoading = (loading) => {
    if (loading) {
      pacienteSelect.innerHTML = '<option value="">Carregando...</option>';
      pacienteSelect.disabled = true;
    }
  };

  const resetPaciente = () => {
    pacienteSelect.innerHTML = '<option value="">Selecione um paciente...</option>';
    pacienteSelect.value = '';
  };

  const hidePlanoInfo = () => {
    infoDiv.style.display = 'none';
    infoDiv.innerHTML = '';
  };

  async function carregarPacientesEPlanosAdmin(idProf) {
    if (!idProf) {
      resetPaciente();
      pacienteSelect.disabled = true;
      planosData = [];
      hidePlanoInfo();
      return;
    }

    setPacienteLoading(true);
    hidePlanoInfo();

    try {
      // Pacientes
      const respPac = await fetch(`buscar_pacientes.php?id_profissional=${encodeURIComponent(idProf)}`);
      const pacientes = await respPac.json();

      resetPaciente();
      pacientes.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.nome_completo;
        pacienteSelect.appendChild(opt);
      });

      pacienteSelect.disabled = false;

      // Planos
      const respPlanos = await fetch(`buscar_planos.php?id_profissional=${encodeURIComponent(idProf)}`);
      planosData = await respPlanos.json();

    } catch (e) {
      resetPaciente();
      pacienteSelect.disabled = true;
      planosData = [];
      hidePlanoInfo();
      console.error('Erro ao carregar pacientes/planos:', e);
      alert('Não foi possível carregar pacientes/planos. Verifique sua conexão ou tente novamente.');
    }
  }

  function atualizarUI() {
    const tipo = tipoSelect.value;
    const pacienteId = pacienteSelect.value;

    // Textos conforme tipo
    if (tipo === 'terapia_pacote' || tipo === 'avaliacao') {
      tituloData.innerText = "Início do Agendamento Automático";
      textoData.innerText  = "ATENÇÃO: Ao salvar, o sistema criará um NOVO PLANO e agendará todas as sessões (4 ou 10) semanalmente a partir desta data.";
    } else {
      tituloData.innerText = "Data da Consulta";
      textoData.innerText  = "Agendamento de uma única sessão.";
    }

    // Resumo de planos (se tiver paciente selecionado)
    if (!pacienteId) {
      hidePlanoInfo();
      return;
    }

    const planosAtivos = (Array.isArray(planosData) ? planosData : []).filter(p => String(p.id_paciente) === String(pacienteId));
    if (planosAtivos.length === 0) {
      hidePlanoInfo();
      return;
    }

    let html = '<strong>Planos Ativos deste Paciente:</strong><br>';
    planosAtivos.forEach(p => {
      const contratadas = Number(p.sessoes_contratadas || 0);
      const utilizadas  = Number(p.sessoes_utilizadas || 0);
      const saldo       = Math.max(0, contratadas - utilizadas);
      const tipoAtend   = (p.tipo_atendimento || '').toString().toUpperCase();
      html += `- ${tipoAtend}: ${saldo}/${contratadas} sessões restantes.<br>`;
    });

    infoDiv.innerHTML = html;
    infoDiv.style.display = 'block';
  }

  // Eventos
  if (isAdmin && profissionalSelect) {
    profissionalSelect.addEventListener('change', () => {
      carregarPacientesEPlanosAdmin(profissionalSelect.value);
    });
  }

  tipoSelect.addEventListener('change', atualizarUI);
  pacienteSelect.addEventListener('change', atualizarUI);

  // Inicialização
  document.addEventListener('DOMContentLoaded', () => {
    atualizarUI();
    if (isAdmin) {
      // Admin começa com paciente desabilitado até escolher profissional
      pacienteSelect.disabled = true;
    }
  });
})();
</script>

<?php require_once 'footer.php'; ?>
