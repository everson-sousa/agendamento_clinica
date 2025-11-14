<?php
// 1. Define o título desta página
$tituloPagina = "Cadastrar Paciente"; 

// 2. Inclui o cabeçalho
require_once 'header.php'; 

// O header.php já verifica se o usuário está logado.
// Qualquer usuário logado (admin ou profissional) pode cadastrar pacientes.
?>

<p>Use este formulário para criar um novo registro (prontuário) de paciente.</p>
<p>Este paciente ficará vinculado à sua conta de profissional.</p>

<form action="processa_cadastro_paciente.php" method="POST">
    
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
// 3. Inclui o rodapé
require_once 'footer.php'; 
?>