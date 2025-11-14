<?php
// 1. Inicia a sessão e verifica segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 3. Pega o ID do profissional logado (o "dono" do paciente)
$id_profissional_responsavel = $_SESSION['usuario_id'];

// 4. Verifica se os dados vieram por POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 5. Pega os dados do formulário
    $nome_completo = $_POST['nome_completo'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $data_nascimento = $_POST['data_nascimento'];

    // 6. Prepara o Comando SQL
    $sql = "INSERT INTO pacientes (id_profissional_responsavel, nome_completo, email, telefone, data_nascimento)
            VALUES (?, ?, ?, ?, ?)";

    // 7. Tenta executar no banco
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $id_profissional_responsavel,
            $nome_completo,
            $email,
            $telefone,
            $data_nascimento
        ]);

        // 8. Sucesso!
        // (Aqui vamos ter que adicionar um CSS para a tela de sucesso)
        echo "Paciente cadastrado com sucesso!";
        echo '<br><a href="cadastrar_paciente.php">Cadastrar Outro Paciente</a>';
        echo '<br><a href="dashboard.php">Voltar ao Painel</a>';

    } catch (PDOException $e) {
        // 9. Erro!
        echo "Erro ao cadastrar o paciente: " . $e->getMessage();
    }

} else {
    // Se alguém tentar acessar o arquivo direto pela URL
    echo "Acesso inválido.";
}
?>