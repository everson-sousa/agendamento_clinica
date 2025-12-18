<?php
// 1. Inicia a sessão e verifica segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    // Se não estiver logado, não retorna nada
    http_response_code(403); // Proibido
    exit;
}

// 2. Inclui a conexão
require_once 'conexao.php';

// 3. Define o tipo de resposta como JSON
header('Content-Type: application/json');

// 4. Pega o ID do profissional enviado pelo JavaScript
$id_profissional = $_GET['id_profissional'];

if (empty($id_profissional)) {
    echo json_encode([]); // Retorna um array vazio se nenhum ID foi enviado
    exit;
}

// 5. Lógica de Segurança
// O Admin pode buscar de qualquer um. 
// O Profissional só pode buscar os seus próprios.
if ($_SESSION['usuario_tipo'] == 'profissional' && $id_profissional != $_SESSION['usuario_id']) {
    http_response_code(403); // Proibido
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

// 6. Busca os pacientes no banco
try {
    $sql_pacientes = "SELECT id, nome_completo 
                      FROM pacientes 
                      WHERE id_profissional_responsavel = ? AND status = 'ativo' 
                      ORDER BY nome_completo ASC";
    
    $stmt_pacientes = $pdo->prepare($sql_pacientes);
    $stmt_pacientes->execute([$id_profissional]);
    $lista_pacientes = $stmt_pacientes->fetchAll(PDO::FETCH_ASSOC);

    // 7. Retorna a lista como JSON
    echo json_encode($lista_pacientes);

} catch (PDOException $e) {
    http_response_code(500); // Erro do servidor
    echo json_encode(['erro' => $e->getMessage()]);
}
?>