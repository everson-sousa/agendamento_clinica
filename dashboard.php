<?php
// 1. Define o título desta página
$tituloPagina = "Dashboard";

// 2. Inclui o cabeçalho (que já tem a segurança e o menu)
require_once 'header.php'; 
?>

<p>Bem-vindo(a) ao painel de gerenciamento da clínica.</p>
<p>Use o menu ao lado para navegar pelo sistema.</p>

<hr style="margin: 20px 0;">

<h3>Resumo Rápido</h3>
<p>Aqui poderíamos ter gráficos ou estatísticas.</p>
<?php
// 3. Inclui o rodapé
require_once 'footer.php'; 
?>