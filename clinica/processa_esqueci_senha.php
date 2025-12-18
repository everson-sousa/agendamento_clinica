<?php
// 1. Inclui as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Inclui o autoload do Composer
// (Este arquivo 'vendor/autoload.php' foi criado quando você instalou o PHPMailer)
require 'vendor/autoload.php';

// 3. Inclui a conexão com o banco
require 'conexao.php'; // Traz a variável $pdo

// 4. Pega o e-mail do formulário
$email = $_POST['email'];

// 5. Verifica se o e-mail existe na tabela de usuários
try {
    $sql_check = "SELECT id FROM usuarios WHERE email = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$email]);
    $usuario = $stmt_check->fetch();

    if (!$usuario) {
        // E-mail não encontrado. Redireciona de volta com erro.
        header("Location: esqueci_senha.php?erro=1");
        exit;
    }

    // --- E-mail encontrado, vamos gerar o token ---

    // 6. Gera um token de segurança
    // random_bytes(32) cria 32 bytes aleatórios
    // bin2hex() converte em uma string de 64 caracteres
    $token = bin2hex(random_bytes(32));

    // 7. Define a data de expiração (ex: 1 hora a partir de agora)
    $data_expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // 8. Salva o token no banco de dados
    $sql_insert = "INSERT INTO password_resets (email, token, data_expiracao) VALUES (?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([$email, $token, $data_expiracao]);

    // 9. Monta o link de redefinição
    // (IMPORTANTE: Mude 'localhost' para o seu domínio real quando for para a internet)
    $link = "http://localhost/clinica/redefinir_senha.php?token=" . $token;

    // 10. Configura o PHPMailer para enviar o e-mail
    $mail = new PHPMailer(true);

    // --- CONFIGURAÇÃO DO SERVIDOR DE E-MAIL (SMTP) ---
    // (Este é o bloco que você precisa configurar)
    try {
        // Configurações do Servidor
        // $mail->SMTPDebug = 2; // Habilite para ver o log de erros (use 0 para produção)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Servidor do Gmail
        $mail->SMTPAuth   = true;
        $mail->Username   = 'eversonsousa@gmail.com'; // *** SEU EMAIL GMAIL ***
        $mail->Password   = 'gkqf suxp kzgx acdw'; // *** SUA "SENHA DE APP" ***
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Destinatários
        $mail->setFrom('seu-email@gmail.com', 'Clinica Silvia Almeida'); // Quem envia
        $mail->addAddress($email); // Quem recebe (o e-mail do usuário)

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = 'Redefinicao de Senha - Clinica Silvia Almeida';
        $mail->Body    = "Olá,<br><br>Recebemos uma solicitação para redefinir sua senha. Clique no link abaixo para criar uma nova senha:<br><br>"
                       . "<a href='{$link}'>{$link}</a><br><br>"
                       . "Se você não solicitou isso, por favor, ignore este e-mail.<br>"
                       . "Este link expira em 1 hora.<br><br>"
                       . "Atenciosamente,<br>Equipe Silvia Almeida Psicologia";
        $mail->AltBody = "Para redefinir sua senha, copie e cole este link no seu navegador: {$link}";

        $mail->send();
        
        // 11. Sucesso! Redireciona de volta
        header("Location: esqueci_senha.php?sucesso=1");
        exit;

    } catch (Exception $e) {
        echo "O e-mail não pôde ser enviado. Erro: {$mail->ErrorInfo}";
    }

} catch (PDOException $e) {
    echo "Erro no banco de dados: " . $e->getMessage();
}
?>