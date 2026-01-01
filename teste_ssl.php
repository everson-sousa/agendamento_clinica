<?php
// Teste de conexão SSL simples
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.google.com");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Tenta executar
$result = curl_exec($ch);

if ($result === false) {
    echo "<h2 style='color:red'>FALHA NO SSL</h2>";
    echo "Erro cURL: " . curl_error($ch);
    echo "<br><br>";
    echo "Isso confirma que o certificado não foi configurado corretamente no php.ini.";
} else {
    echo "<h2 style='color:green'>SUCESSO!</h2>";
    echo "O SSL está funcionando. O PHP conseguiu conectar via HTTPS.";
}

curl_close($ch);
?>