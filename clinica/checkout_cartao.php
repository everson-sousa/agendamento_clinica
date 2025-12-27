<?php
// checkout_cartao.php - FRONTEND (Formulário Visual)
session_start();
require_once 'conexao.php';

// 1. Validação básica
if (!isset($_GET['id_agendamento'])) {
    die("Erro: ID do agendamento não fornecido.");
}
$id_agendamento = $_GET['id_agendamento'];

// 2. Busca a Public Key no banco
// (Seu sistema usa usuario_id na sessão ou pega o admin padrão ID 1)
$id_usuario = $_SESSION['usuario_id'] ?? 1;
$stmt = $pdo->prepare("SELECT mp_public_key, email FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$id_usuario]);
$user_data = $stmt->fetch();
$public_key = $user_data['mp_public_key'] ?? '';

if (empty($public_key)) {
    die("Erro: Public Key não configurada no banco de dados.");
}

// Dados simulados do paciente (em produção viria do banco)
$valor_sessao = 1.00; // Valor de teste
$email_paciente = "paciente_teste@email.com";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Seguro</title>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding: 20px; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h3 { text-align: center; color: #333; margin-bottom: 20px; }
        .info-valor { text-align: center; margin-bottom: 20px; font-size: 18px; color: #555; }
    </style>
</head>
<body>

    <div class="container">
        <h3>💳 Pagamento Seguro</h3>
        <p class="info-valor">Valor a pagar: <strong>R$ <?php echo number_format($valor_sessao, 2, ',', '.'); ?></strong></p>
        
        <div id="paymentBrick_container"></div>
    </div>

    <script>
    const mp = new MercadoPago('<?php echo $public_key; ?>', {
        locale: 'pt-BR'
    });

    const bricksBuilder = mp.bricks();

    const renderPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: <?php echo $valor_sessao; ?>,
                payer: {
                    email: '<?php echo $email_paciente; ?>',
                },
            },
            customization: {
                paymentMethods: {
                    creditCard: "all",
                    bankTransfer: "all", // Permite PIX também
                    maxInstallments: 3
                },
                visual: {
                    style: {
                        theme: 'default' 
                    }
                }
            },
            callbacks: {
                onReady: () => {
                    // Ocultar loading se tiver
                },
                onSubmit: ({ selectedPaymentMethod, formData }) => {
                    // Função que processa o clique no botão "Pagar"
                    return new Promise((resolve, reject) => {
                        
                        fetch("processar_cartao.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                            },
                            body: JSON.stringify({
                                ...formData, 
                                id_agendamento: "<?php echo $id_agendamento; ?>"
                            })
                        })
                        .then((response) => response.json()) // Transforma a resposta em objeto
                        .then((response) => {
                            console.log("Resposta do servidor:", response);

                            // LÓGICA DE RESPOSTA ATUALIZADA
                            if (response.status === "approved") {
                                alert("✅ Pagamento Aprovado com Sucesso!");
                                window.location.href = "ver_agendamentos.php";
                            } 
                            else if (response.status === "pix_created") {
                                // Se o usuário escolheu PIX no formulário híbrido
                                document.querySelector('.container').innerHTML = `
                                    <div style="text-align:center">
                                        <h3 style="color:#28a745">✅ PIX Gerado!</h3>
                                        <img src="data:image/png;base64,${response.qr_code_base64}" width="200" style="margin:10px 0"><br>
                                        <p>Copie e cole no app do banco:</p>
                                        <textarea style="width:100%; height:80px; font-size:12px; padding:5px" readonly>${response.qr_code}</textarea>
                                        <br><br>
                                        <button onclick="window.location.reload()" style="padding:10px 20px; cursor:pointer">Voltar</button>
                                    </div>
                                `;
                            } 
                            else {
                                // MOSTRA O ERRO REAL (Não mostra mais JSON Input)
                                var motivo = response.message || "Erro desconhecido ao processar.";
                                alert("❌ Ocorreu um problema: " + motivo);
                            }
                            resolve();
                        })
                        .catch((error) => {
                            // Captura erros graves (como arquivo PHP não encontrado ou erro de sintaxe)
                            console.error(error);
                            alert("Erro crítico de comunicação com o servidor. Verifique o console (F12).");
                            reject();
                        });
                    });
                },
                onError: (error) => {
                    console.error(error);
                },
            },
        };
        window.paymentBrickController = await bricksBuilder.create(
            'payment',
            'paymentBrick_container',
            settings
        );
    };

    renderPaymentBrick(bricksBuilder);
    </script>
</body>
</html>