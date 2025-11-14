<?php
// 1. Inicia a sessão e faz a verificação de segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 3. Pega os dados do usuário logado
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// 4. LÓGICA DE ADMIN: Buscar lista de profissionais
$lista_profissionais = [];
if ($tipo_usuario_logado == 'admin') {
    // Se for admin, busca todos os usuários que são 'profissional' e estão 'ativos'
    $sql = "SELECT id, nome FROM usuarios WHERE tipo_acesso = 'profissional' AND status = 'ativo' ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    $lista_profissionais = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Agendamento</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        form { max-width: 500px; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="datetime-local"], select, textarea { 
            width: 100%; padding: 8px; box-sizing: border-box; 
        }
        button { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header a { text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Cadastrar Novo Agendamento</h2>
        <a href="dashboard.php">Voltar ao Painel</a>
    </div>

    <form action="processa_agendamento.php" method="POST">

        <?php
        // 5. LÓGICA CONDICIONAL: Mostra o dropdown se for ADMIN
        if ($tipo_usuario_logado == 'admin') {
            echo '<div>';
            echo '    <label for="id_profissional">Profissional:</label>';
            echo '    <select id="id_profissional" name="id_profissional" required>';
            echo '        <option value="">Selecione um profissional</option>';
            // Loop para criar as opções
            foreach ($lista_profissionais as $profissional) {
                echo '<option value="' . $profissional['id'] . '">' . htmlspecialchars($profissional['nome']) . '</option>';
            }
            echo '    </select>';
            echo '</div>';
        } else {
            // 6. LÓGICA DE PROFISSIONAL: Passa o ID escondido
            // Se é um profissional, o agendamento é para ele mesmo.
            echo '<input type="hidden" name="id_profissional" value="' . $id_usuario_logado . '">';
        }
        ?>

        <div>
            <label for="nome_paciente">Nome do Paciente:</label>
            <input type="text" id="nome_paciente" name="nome_paciente" required>
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

</body>
</html>