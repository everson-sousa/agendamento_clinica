<?php
// 1. Define o título
$tituloPagina = "Cadastrar Paciente"; 

// 2. Inclui o cabeçalho
require_once 'header.php'; 

// 3. Inclui a conexão
require_once 'conexao.php'; // <--- PRECISAMOS DISSO AGORA

// 4. LÓGICA DE ADMIN: Buscar lista de profissionais
// (A var $tipo_usuario vem do header.php)
$lista_profissionais = [];
if ($tipo_usuario == 'admin') {
    $sql = "SELECT id, nome FROM usuarios WHERE tipo_acesso = 'profissional' AND status = 'ativo' ORDER BY nome ASC";
    $stmt = $pdo->query($sql);
    $lista_profissionais = $stmt->fetchAll();
}
?>

<p>Use este formulário para criar um novo registro (prontuário) de paciente.</p>

<form action="processa_cadastro_paciente.php" method="POST">
    
    <?php
    // 5. LÓGICA CONDICIONAL: Mostra o dropdown se for ADMIN
    if ($tipo_usuario == 'admin') {
        echo '<div>';
        echo '    <label for="id_profissional">Profissional Responsável:</label>';
        echo '    <select id="id_profissional" name="id_profissional_responsavel" required>';
        echo '        <option value="">Selecione um profissional</option>';
        
        foreach ($lista_profissionais as $profissional) {
            echo '<option value="' . $profissional['id'] . '">' . htmlspecialchars($profissional['nome']) . '</option>';
        }
        
        echo '    </select>';
        echo '</div>';
        echo '<p>Este paciente ficará vinculado ao profissional selecionado.</p>';
    
    } else {
        // Se for um profissional, ele não vê a seleção.
        // O próximo script vai pegar o ID dele da SESSÃO.
        echo '<p>Este paciente ficará vinculado à sua conta.</p>';
    }
    ?>
    
    <hr style="margin: 20px 0;">

    <div>
        <label for="nome_completo">Nome Completo do Paciente:</label>
        <input type="text" id="nome_completo" name="nome_completo" required>
    </div>
    <div>
        <label for="email">E-mail (Opcional):</label>
        <input type="email" id="email" name="email">
    </div>
    <div>
        <label for="telefone">Telefone (Opcional):</label>
        <input type="text" id="telefone" name="telefone">
    </div>
    <div>
        <label for="data_nascimento">Data de Nascimento (Opcional):</label>
        <input type="date" id="data_nascimento" name="data_nascimento">
    </div>
    
    <button type="submit">Salvar Paciente</button>
</form>

<?php
// 6. Inclui o rodapé
require_once 'footer.php'; 
?>