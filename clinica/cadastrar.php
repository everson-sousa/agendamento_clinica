<?php
// 1. Define o título desta página
$tituloPagina = "Gerenciar Usuários"; 

// 2. Inclui o cabeçalho (que já tem a segurança de login)
require_once 'header.php'; 

// 3. ADICIONA NOVA CHECAGEM DE PERMISSÃO
// O header.php já verificou se está logado.
// Agora, verificamos se é ADMIN. A var $tipo_usuario vem do header.php
if ($tipo_usuario != 'admin') {
    
    // Se não for admin, mostra erro e para
    echo "<p>Acesso negado. Você não tem permissão para acessar esta página.</p>";
    
    // Inclui o rodapé para fechar o HTML corretamente
    require_once 'footer.php'; 
    
    // Para a execução do script
    exit; 
}

// --- Se chegou aqui, é um Admin logado ---
?>

<p>Use este formulário para criar novos usuários (profissionais ou outros admins).</p>

<form action="processa_cadastro.php" method="POST">
    <div>
        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required>
    </div>
    <div>
        <label for="email">E-mail (Login):</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div>
        <label for="senha">Senha:</label>
        <input type="password" id="senha" name="senha" required>
    </div>
    <div>
        <label for="telefone">Telefone (Opcional):</label>
        <input type="text" id="telefone" name="telefone">
    </div>
    <div>
        <label for="tipo_acesso">Tipo de Acesso:</label>
        <select id="tipo_accesso" name="tipo_acesso" required>
            <option value="profissional">Profissional</option>
            <option value="admin">Admin (Root)</option>
        </select>
    </div>
    
    <button type="submit">Cadastrar Usuário</button>
</form>

<?php
// 4. Inclui o rodapé
require_once 'footer.php'; 
?>