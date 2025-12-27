<?php
// ATIVA EXIBIÇÃO DE ERROS
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'conexao.php';

// 1. Descobre qual profissional mostrar
$id_profissional = $_GET['p'] ?? null;

if (!$id_profissional) {
    try {
        $stmt = $pdo->query("SELECT id FROM usuarios WHERE tipo_usuario != 'admin' LIMIT 1");
        $prof = $stmt->fetch();
        $id_profissional = $prof['id'] ?? 1; 
    } catch (Exception $e) {
        $id_profissional = 1;
    }
}

// 2. Busca dados do Profissional
$stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id = ?");
$stmt->execute([$id_profissional]);
$profissional = $stmt->fetch();

if (!$profissional) {
    die("Erro: Profissional (ID $id_profissional) não encontrado no banco de dados.");
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar com <?php echo htmlspecialchars($profissional['nome']); ?></title>
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.10/locales/pt-br.global.min.js"></script>
    
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header-profile { text-align: center; margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin: 5px 0; }
        .slot-livre { background-color: #28a745 !important; border-color: #28a745 !important; cursor: pointer; }
        .fc-event-title { font-weight: bold; }

        /* --- AQUI ESTÁ A CORREÇÃO DOS NÚMEROS CORTADOS --- */
        .fc-daygrid-day-top {
            flex-direction: row !important; 
        }
        .fc-daygrid-day-number {
            padding-right: 20px !important; /* Empurra o número para a esquerda */
            font-weight: bold;
            z-index: 5; 
        }
        /* Ajuste da barra de rolagem */
        .fc-scroller-harness, .fc-scroller {
            overflow: visible !important;
            height: auto !important;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-profile">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($profissional['nome']); ?>&background=0D8ABC&color=fff&size=200" class="avatar">
        <h1><?php echo htmlspecialchars($profissional['nome']); ?></h1>
        <p>Selecione um horário disponível:</p>
    </div>
    <div id='calendar'></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth', // Voltei para visualização de MÊS (onde dá o erro visual)
            contentHeight: 'auto',       // Ajusta altura para não cortar
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
            locale: 'pt-br',
            buttonText: { today: 'Hoje', month: 'Mês', week: 'Semana', list: 'Lista' },
            events: 'api_horarios.php?id_profissional=<?php echo $profissional['id']; ?>',
            eventClick: function(info) {
                // Formata a data para mostrar bonitinho
                var dataFormatada = info.event.start.toLocaleDateString('pt-BR') + ' às ' + 
                                    info.event.start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});

                var confirmacao = confirm("Deseja reservar este horário?\n\n" + dataFormatada);
                
                if (confirmacao) {
                    window.location.href = "reservar_horario.php?id_slot=" + info.event.id;
                }
            },
        });
        calendar.render();
    });
</script>

</body>
</html>