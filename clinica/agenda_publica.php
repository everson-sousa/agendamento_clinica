<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';

// Profissional
$id_profissional = $_GET['p'] ?? null;

if (!$id_profissional) {
    try {
        $stmt = $pdo->query("SELECT id FROM usuarios WHERE tipo_usuario != 'admin' LIMIT 1");
        $prof = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_profissional = $prof['id'] ?? 1;
    } catch (Exception $e) {
        $id_profissional = 1;
    }
}

$stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id = ?");
$stmt->execute([$id_profissional]);
$profissional = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profissional) {
    die("Erro: Profissional (ID $id_profissional) não encontrado no banco de dados.");
}

// Serviços (do banco)
$stmt_serv = $pdo->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY preco ASC");
$servicos = $stmt_serv->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agendar com <?php echo htmlspecialchars($profissional['nome']); ?></title>

  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/pt-br.global.min.js"></script>

  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }

    .header-profile { text-align: center; margin-bottom: 16px; border-bottom: 1px solid #eee; padding-bottom: 16px; }
    .avatar { width: 92px; height: 92px; border-radius: 50%; object-fit: cover; margin-bottom: 8px; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #2c3e50; margin: 5px 0; }

    /* Serviços */
    .steps { margin: 14px 0 10px; text-align:center; color:#666; }
    .servicos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 14px 0 18px; }
    .opcao-servico {
      border: 1px solid #e0e0e0; border-radius: 10px; padding: 12px; background:#fff;
      cursor:pointer; transition: .2s;
    }
    .opcao-servico:hover { border-color:#3498db; background:#f0f8ff; }
    .card-selecionado { border-color:#3498db; background:#e8f4fd; border-width:2px; }
    .servico-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
    .servico-nome { font-weight:700; color:#2c3e50; }
    .servico-preco { font-weight:800; color:#27ae60; white-space:nowrap; }
    .servico-desc { margin-top:6px; font-size:13px; color:#7f8c8d; line-height:1.35; }
    .servico-pacote { margin-top:6px; font-size:13px; font-weight:700; color:#d35400; }

    input[type="radio"] { display:none; }
    #aviso-servico { display:none; margin: 10px 0 14px; color:#c0392b; text-align:center; font-weight:700; }

    @media (max-width: 720px) {
      body { padding: 12px; }
      .container { padding: 18px; }
      .servicos-grid { grid-template-columns: 1fr; }
    }

    /* Correção números cortados */
    .fc-daygrid-day-top { flex-direction: row !important; }
    .fc-daygrid-day-number { padding-right: 20px !important; font-weight: bold; z-index: 5; }
    .fc-scroller-harness, .fc-scroller { overflow: visible !important; height: auto !important; }
  </style>
</head>
<body>

<div class="container">
  <div class="header-profile">
    <img
      src="https://ui-avatars.com/api/?name=<?php echo urlencode($profissional['nome']); ?>&background=0D8ABC&color=fff&size=200"
      class="avatar" alt="Avatar"
    >
    <h1><?php echo htmlspecialchars($profissional['nome']); ?></h1>
    <div class="steps">
      <div><strong>1.</strong> Escolha o serviço • <strong>2.</strong> Escolha o horário</div>
    </div>
  </div>

  <div id="aviso-servico">⚠️ Selecione um serviço antes de escolher o horário.</div>

  <!-- SERVIÇOS (SELEÇÃO OBRIGATÓRIA) -->
  <div class="servicos-grid" id="servicosGrid">
    <?php foreach ($servicos as $s): ?>
      <?php
        $id = (int)$s['id'];
        $nome = $s['nome_servico'] ?? '';
        $preco = (float)($s['preco'] ?? 0);
        $desc = $s['descricao_comercial'] ?? '';
        $qtd  = (int)($s['quantidade_sessoes'] ?? 1);

        $isAvaliacao = (stripos($nome, 'avalia') !== false);
        $textoPacote = '';
        if ($qtd > 1) {
          $textoPacote = $isAvaliacao ? "Pacote com até {$qtd} Sessões" : "Pacote com {$qtd} Sessões";
        }
      ?>
      <label class="opcao-servico" id="card_<?php echo $id; ?>">
        <input
          type="radio"
          name="id_servico"
          value="<?php echo $id; ?>"
          onchange="selecionarServico(this)"
        >

        <div class="servico-top">
          <div class="servico-nome"><?php echo htmlspecialchars($nome); ?></div>
          <div class="servico-preco">R$ <?php echo number_format($preco, 2, ',', '.'); ?></div>
        </div>

        <?php if (!empty($desc)): ?>
          <div class="servico-desc"><?php echo htmlspecialchars($desc); ?></div>
        <?php endif; ?>

        <?php if (!empty($textoPacote)): ?>
          <div class="servico-pacote"><?php echo htmlspecialchars($textoPacote); ?></div>
        <?php endif; ?>
      </label>
    <?php endforeach; ?>
  </div>

  <!-- CALENDÁRIO -->
  <div id="calendar"></div>
</div>

<script>
  let idServicoSelecionado = null;

  function selecionarServico(radio) {
    idServicoSelecionado = radio.value;

    document.querySelectorAll('.opcao-servico').forEach(el => el.classList.remove('card-selecionado'));
    const card = document.getElementById('card_' + radio.value);
    if (card) card.classList.add('card-selecionado');

    document.getElementById('aviso-servico').style.display = 'none';

    // opcional: manter seleção ao recarregar
    localStorage.setItem('servicoSelecionado', String(idServicoSelecionado));
  }

  // Restaura seleção (se tiver)
  (function restoreServico() {
    const saved = localStorage.getItem('servicoSelecionado');
    if (!saved) return;

    const input = document.querySelector(`input[name="id_servico"][value="${saved}"]`);
    if (input) {
      input.checked = true;
      selecionarServico(input);
    }
  })();

  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      contentHeight: 'auto',
      headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
      locale: 'pt-br',
      buttonText: { today: 'Hoje', month: 'Mês', week: 'Semana', list: 'Lista' },
      events: 'api_horarios.php?id_profissional=<?php echo (int)$profissional['id']; ?>',

      eventClick: function(info) {
        if (!idServicoSelecionado) {
          document.getElementById('aviso-servico').style.display = 'block';
          document.getElementById('aviso-servico').scrollIntoView({ behavior: 'smooth' });
          return;
        }

        var dataFormatada =
          info.event.start.toLocaleDateString('pt-BR') + ' às ' +
          info.event.start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});

        var confirmacao = confirm("Deseja reservar este horário?\n\n" + dataFormatada);

        if (confirmacao) {
          // ✅ fluxo original, mas agora passando o serviço escolhido
          window.location.href =
            "reservar_horario.php?id_slot=" + encodeURIComponent(info.event.id) +
            "&id_servico=" + encodeURIComponent(idServicoSelecionado);
        }
      }
    });

    calendar.render();
  });
</script>

</body>
</html>
