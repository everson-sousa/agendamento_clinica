<?php
// 1. Define o título desta página
$tituloPagina = "Novo Agendamento";

// 2. Inclui o cabeçalho (que já tem a segurança e o menu)
require_once 'header.php';

// 3. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 4. Pega os dados do usuário logado da SESSÃO
// (O header.php já iniciou a sessão e pegou $tipo_usuario)
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo']; // ou apenas $tipo_usuario

// 5. LÓGICA DE ADMIN: Buscar lista de profissionais
$lista_profissionais = [];
if ($tipo_usuario_logado == 'admin') {
    // Se for admin, busca todos os usuários que são 'profissional' e estão 'ativos'
    $sql = "SELECT id, nome FROM usuarios WHERE tipo_acesso = 'profissional' AND status = 'ativo' ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    $lista_profissionais = $stmt->fetchAll();
}
?>

<form action="processa_agendamento.php" method="POST">

    <?php
    // 6. LÓGICA CONDICIONAL: Mostra o dropdown se for ADMIN
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
        // 7. LÓGICA DE PROFISSIONAL: Passa o ID escondido
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

<?php
// 8. Inclui o rodapé
require_once 'footer.php';
?>