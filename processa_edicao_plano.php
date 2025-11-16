<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Pega os dados
    $id_plano = $_POST['id_plano'];
    $sessoes_contratadas = $_POST['sessoes_contratadas'];
    $status = $_POST['status']; // 'Ativo' ou 'Concluido'

    try {
        // Checagem de segurança
        $sql_check = "SELECT id_profissional FROM planos_paciente WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id_plano]);
        $plano_original = $stmt_check->fetch();

        if (!$plano_original) {
            die("Plano não encontrado.");
        }
        
        if ($_SESSION['usuario_tipo'] != 'admin' && $plano_original['id_profissional'] != $_SESSION['usuario_id']) {
            die("Acesso negado.");
        }
        
        // Atualiza o plano
        $sql_update = "UPDATE planos_paciente SET 
                            sessoes_contratadas = ?,
                            status = ?
                       WHERE id = ?";
        
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$sessoes_contratadas, $status, $id_plano]);
        
        echo "Plano atualizado com sucesso!";
        echo '<br><a href="ver_planos.php">Voltar para a Lista de Planos</a>';
        echo '<br><a href="dashboard.php">Voltar ao Painel</a>';

    } catch (PDOException $e) {
        echo "Erro ao salvar: " . $e->getMessage();
    }
}
?>