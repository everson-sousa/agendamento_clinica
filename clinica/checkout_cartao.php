<?php
// checkout_cartao.php
// Lógica: Usa credenciais do Admin (ID 1) para cobrar, mas agenda para o Profissional escolhido.

session_start();
require_once 'conexao.php';

// 1. Verifica se veio do formulário de reserva
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_slot'])) {
    // Se tentar acessar direto, mata o script
    die("Erro: Acesso inválido. Por favor, inicie o agendamento selecionando um horário.");
}

// 2. Coleta os dados vindos do POST (Formulário anterior)
$id_slot = (int)$_POST['id_slot'];
$id_servico = (int)$_POST['id_servico'];
$id_profissional = (int)$_POST['id_profissional']; // ID da Silvia (ou outro)
$nome = htmlspecialchars($_POST['nome'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$cpf = htmlspecialchars($_POST['cpf'] ?? '');
$whatsapp = htmlspecialchars($_POST['whatsapp'] ?? '');

try {
    // 3. Busca dados do Serviço (Preço Real)
    $stmt = $pdo->prepare("SELECT nome_servico, preco FROM servicos WHERE id = ? LIMIT 1");
    $stmt->execute([$id_servico]);
    $servico = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$servico) die("Erro: Serviço não encontrado no banco.");
    
    $preco_real = (float)$servico['preco'];
    $nome_servico = $servico['nome_servico'];

    // 4. Busca a Public Key DO ADMIN (ID 1) - CENTRALIZAÇÃO FINANCEIRA
    // O dinheiro vai cair na conta do ID 1
    $stmt_key = $pdo->query("SELECT mp_public_key FROM usuarios WHERE id = 1 LIMIT 1");
    $public_key = $stmt_key->fetchColumn();

    if (empty($public_key)) {
        die("Erro Crítico: Chave de pagamento (Public Key) não configurada no usuário Admin (ID 1).");
    }

} catch (PDOException $e) {
    die("Erro de banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Seguro</title>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; padding: 20px; display: flex; justify-content: center; }
        .checkout-container { background: white; width: 100%; max-width: 480px; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h2 { text-align: center; color: #333; margin-top: 0; font-size: 22px; margin-bottom: 20px; }
        .resumo { background: #e9ecef; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; border: 1px solid #dee2e6; }
        .total { font-size: 28px; font-weight: bold; color: #27ae60; margin-top: 5px; }
        .item { font-size: 16px; color: #555; font-weight: 500; }
        .user-data { font-size: 13px; color: #777; margin-bottom: 25px; text-align: center; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    </style>
</head>
<body>

<div class="checkout-container">
    <h2>Finalizar Agendamento</h2>

    <div class="resumo">
        <div class="item"><?php echo htmlspecialchars($nome_servico); ?></div>
        <div class="total">R$ <?php echo number_format($preco_real, 2, ',', '.'); ?></div>
    </div>

    <div class="user-data">
        Paciente: <strong><?php echo $nome; ?></strong><br>
        CPF: <?php echo $cpf; ?>
    </div>
   
    <div id="paymentBrick_container"></div>
</div>

<script>
    // Inicializa com a chave do ADMIN (ID 1)
    const mp = new MercadoPago('<?php echo $public_key; ?>', { locale: 'pt-BR' });
    const bricksBuilder = mp.bricks();

    const renderPaymentBrick = async (bricksBuilder) => {
        const settings = {
            initialization: {
                amount: <?php echo $preco_real; ?>,
                payer: {
                    email: '<?php echo $email; ?>', // Preenche e-mail automático
                },
            },
            customization: {
                paymentMethods: {
                    creditCard: "all",
                    bankTransfer: "all", // Pix
                    maxInstallments: 6
                },
                visual: {
                    style: { theme: 'default' }
                }
            },
            callbacks: {
                onReady: () => {
                    // Carregou
                },
                onSubmit: ({ selectedPaymentMethod, formData }) => {
                    // Monta o pacote de dados para enviar ao backend
                    const payload = {
                        ...formData,
                        agendamento: {
                            id_slot: <?php echo $id_slot; ?>,
                            id_servico: <?php echo $id_servico; ?>,
                            id_profissional: <?php echo $id_profissional; ?>, // Envia o ID da Silvia para agendar corretamente
                            nome: "<?php echo $nome; ?>",
                            email: "<?php echo $email; ?>",
                            cpf: "<?php echo $cpf; ?>",
                            whatsapp: "<?php echo $whatsapp; ?>"
                        }
                    };

                    return new Promise((resolve, reject) => {
                        fetch("processar_cartao.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify(payload)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === "approved") {
                                // Sucesso Cartão -> Redireciona para comprovante
                                window.location.href = "sucesso.php?id=" + data.id_pagamento; 
                            } 
                            else if (data.status === "pending" && data.payment_method_id === 'pix') {
                                // Sucesso Pix -> Mostra alerta e QR Code (simplificado)
                                alert("Pix Gerado! Copie o código na próxima tela.");
                                // Redireciona para o sucesso.php mas passa flag de pix se quiser tratar lá
                                // Por enquanto, vamos manter simples:
                                document.body.innerHTML = '<div style="padding:20px;text-align:center;font-family:sans-serif"><h1>Pagamento Pix</h1><p>Copie e cole no seu banco:</p><textarea style="width:100%;height:100px;padding:10px">'+data.qr_code+'</textarea><br><br><img src="data:image/jpeg;base64,'+data.qr_code_base64+'" style="width:250px"><br><br><a href="sucesso.php?id='+data.id_pagamento+'">Já paguei (Ver Comprovante)</a></div>';
                            }
                            else {
                                alert("❌ Ocorreu um erro: " + (data.message || "Pagamento recusado. Verifique os dados."));
                                resolve(); // Permite tentar de novo na mesma tela
                            }
                        })
                        .catch(error => {
                            console.error(error);
                            alert("Erro de comunicação com o servidor.");
                            reject();
                        });
                    });
                },
                onError: (error) => {
                    console.error(error);
                },
            },
        };
        window.paymentBrickController = await bricksBuilder.create('payment', 'paymentBrick_container', settings);
    };

    renderPaymentBrick(bricksBuilder);
</script>

</body>
</html>