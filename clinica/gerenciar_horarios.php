<?php
$tituloPagina = "Gerenciar Disponibilidade (Vitrine)";
require_once 'header.php';
require_once 'conexao.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    die("Acesso negado.");
}

$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['usuario_tipo'] ?? 'comum';

// --- 1. SE FOR ADMIN, BUSCA LISTA DE PROFISSIONAIS ---
$profissionais = [];
$erro_sql = null;

if ($tipo_usuario == 'admin') {
    try {
        // Tenta buscar usando a coluna tipo_usuario
        $sql_prof = "SELECT id, nome FROM usuarios WHERE id != ? ORDER BY nome ASC";
        $stmt = $pdo->prepare($sql_prof);
        $stmt->execute([$id_usuario_logado]); // Remove o próprio admin da lista de "profissionais"
        $profissionais = $stmt->fetchAll();
    } catch (PDOException $e) {
        $erro_sql = "Erro ao buscar profissionais: " . $e->getMessage();
    }
}

// --- 2. LÓGICA DE ADICIONAR HORÁRIO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'adicionar') {
    
    if ($tipo_usuario == 'admin') {
        // Se o admin não selecionou ninguém, assume erro ou pega o primeiro
        $id_profissional_alvo = $_POST['id_profissional'] ?? $id_usuario_logado;
    } else {
        $id_profissional_alvo = $id_usuario_logado;
    }

    $data = $_POST['data'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    
    $inicio = $data . ' ' . $hora_inicio;
    $fim = $data . ' ' . $hora_fim;
    
    if (strtotime($inicio) < strtotime($fim)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO horarios_disponiveis (id_profissional, data_hora_inicio, data_hora_fim) VALUES (?, ?, ?)");
            $stmt->execute([$id_profissional_alvo, $inicio, $fim]);
            $sucesso = "Horário disponibilizado com sucesso!";
        } catch (PDOException $e) {
            $erro = "Erro ao gravar: " . $e->getMessage();
        }
    } else {
        $erro = "A hora final deve ser maior que a inicial.";
    }
}

// --- 3. LÓGICA DE EXCLUIR ---
if (isset($_GET['excluir'])) {
    $id_excluir = $_GET['excluir'];
    try {
        if ($tipo_usuario == 'admin') {
            $stmt = $pdo->prepare("DELETE FROM horarios_disponiveis WHERE id = ?");
            $stmt->execute([$id_excluir]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM horarios_disponiveis WHERE id = ? AND id_profissional = ?");
            $stmt->execute([$id_excluir, $id_usuario_logado]);
        }
        echo "<script>window.location.href='gerenciar_horarios.php';</script>";
        exit;
    } catch (PDOException $e) {
        $erro = "Erro ao excluir: " . $e->getMessage();
    }
}

// --- 4. BUSCA HORÁRIOS PARA LISTAGEM ---
$horarios = [];
try {
    if ($tipo_usuario == 'admin') {
        // Traz tudo e junta com o nome do usuário
        $sql = "SELECT h.*, u.nome as nome_profissional 
                FROM horarios_disponiveis h 
                LEFT JOIN usuarios u ON h.id_profissional = u.id
                WHERE h.status = 'livre' AND h.data_hora_inicio >= NOW() 
                ORDER BY h.data_hora_inicio ASC";
        $stmt = $pdo->query($sql);
    } else {
        // Traz só os meus
        $sql = "SELECT h.*, u.nome as nome_profissional 
                FROM horarios_disponiveis h 
                LEFT JOIN usuarios u ON h.id_profissional = u.id
                WHERE h.id_profissional = ? AND h.status = 'livre' AND h.data_hora_inicio >= NOW() 
                ORDER BY h.data_hora_inicio ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_usuario_logado]);
    }
    $horarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro_sql = "Erro na listagem: " . $e->getMessage();
}
?>

<div class="container" style="max-width: 900px; margin-top: 20px; padding: 20px;">
    
    <?php if($erro_sql): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin-bottom: 20px;">
            <strong>Erro Técnico:</strong> <?php echo $erro_sql; ?>
        </div>
    <?php endif; ?>

    <?php if(isset($sucesso)): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $sucesso; ?>
        </div>
    <?php endif; ?>
    <?php if(isset($erro)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo $erro; ?>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <h3>📅 Gerenciar Vitrine de Horários</h3>
        <p style="color: #666; margin-bottom: 20px;">Defina abaixo os horários que aparecerão disponíveis para os pacientes comprarem.</p>
        
        <form method="POST">
            <input type="hidden" name="acao" value="adicionar">
            
            <?php if ($tipo_usuario == 'admin'): ?>
            <div style="margin-bottom: 15px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Selecione o Profissional:</label>
                <select name="id_profissional" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #f8f9fa;">
                    <?php if (count($profissionais) > 0): ?>
                        <?php foreach ($profissionais as $prof): ?>
                            <option value="<?php echo $prof['id']; ?>"><?php echo htmlspecialchars($prof['nome']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="">Nenhum profissional encontrado</option>
                    <?php endif; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                
                <div style="flex-grow: 1; min-width: 150px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Data:</label>
                    <input type="date" name="data" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                
                <div style="width: 130px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Início:</label>
                    <input type="time" name="hora_inicio" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                
                <div style="width: 130px;">
                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Fim:</label>
                    <input type="time" name="hora_fim" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                
                <button type="submit" style="background: #007bff; color: white; border: none; padding: 10px 25px; border-radius: 4px; cursor: pointer; height: 42px; font-weight: bold; margin-bottom: 1px;">
                    + Adicionar
                </button>
            </div>
        </form>
        
    </div>

    <h4 style="margin-top: 40px; border-bottom: 2px solid #eee; padding-bottom: 10px;">Horários Disponíveis Atuais</h4>
    
    <table style="width: 100%; border-collapse: collapse; background: white; margin-top: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <tr style="background: #f8f9fa; text-align: left; border-bottom: 2px solid #ddd;">
            <th style="padding: 12px;">Profissional</th>
            <th style="padding: 12px;">Data</th>
            <th style="padding: 12px;">Horário</th>
            <th style="padding: 12px; text-align: center;">Ação</th>
        </tr>
        <?php if(count($horarios) > 0): ?>
            <?php foreach($horarios as $h): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px; color: #555;">
                        <span style="background: #eef; padding: 3px 8px; border-radius: 10px; font-size: 0.9em;">
                            <?php echo htmlspecialchars($h['nome_profissional'] ?? 'Desconhecido'); ?>
                        </span>
                    </td>
                    <td style="padding: 12px; font-weight: bold;"><?php echo date('d/m/Y', strtotime($h['data_hora_inicio'])); ?></td>
                    <td style="padding: 12px;">
                        <?php echo date('H:i', strtotime($h['data_hora_inicio'])); ?> às 
                        <?php echo date('H:i', strtotime($h['data_hora_fim'])); ?>
                    </td>
                    <td style="padding: 12px; text-align: center;">
                        <a href="?excluir=<?php echo $h['id']; ?>" onclick="return confirm('Tem certeza?')" style="color: #dc3545; text-decoration: none; font-weight: bold;">
                             Remover
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4" style="padding: 20px; text-align: center; color: #777;">Nenhum horário cadastrado.</td></tr>
        <?php endif; ?>
    </table>

</div>

<?php require_once 'footer.php'; ?>