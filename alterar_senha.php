<?php
// 1. Define o título desta página
$tituloPagina = "Alterar Senha"; 

// 2. Inclui o cabeçalho (que já tem a segurança)
require_once 'header.php'; 

// O header.php já fez o session_start() e verificou 
// se o usuário está logado. Como qualquer usuário logado
// pode (e deve) alterar a própria senha, não precisamos 
// de nenhuma outra verificação de segurança aqui.
?>

<form action="processa_alterar_senha.php" method="POST">
    <div>
        <label for="senha_atual">Senha Atual:</label>
        <input type="password" id="senha_atual" name="senha_atual" required>
    </div>
    <div>
        <label for="nova_senha">Nova Senha:</label>
        <input type="password" id="nova_senha" name="nova_senha" required>
    </div>
    <div>
        <label for="confirma_senha">Confirme a Nova Senha:</label>
        <input type="password" id="confirma_senha" name="confirma_senha" required>
    </div>
    
    <button type="submit">Atualizar Senha</button>
</form>

<?php
// 3. Inclui o rodapé
require_once 'footer.php'; 
?>