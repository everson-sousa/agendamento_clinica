<?php
// 1. Inicia a sessão e faz a verificação de segurança
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Inclui a conexão
require_once 'conexao.php'; // Traz a variável $pdo

// 3. Pega os dados do usuário logado
$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// 4. Prepara a consulta SQL
$sql_base = "SELECT 
                ag.*, 
                usr.nome AS nome_profissional 
            FROM 
                agendamentos AS ag
            JOIN 
                usuarios AS usr ON ag.id_profissional = usr.id";

$params = []; // Array para os parâmetros da consulta

// 5. LÓGICA CONDICIONAL: Filtra por profissional ou mostra tudo
if ($tipo_usuario_logado == 'profissional') {
    // Se é profissional, mostra APENAS os seus
    $sql_base .= " WHERE ag.id_profissional = ?";
    $params[] = $id_usuario_logado;
}

// Ordena pelos mais próximos (data de início)
$sql_base .= " ORDER BY ag.data_hora_inicio ASC";

// 6. Executa a consulta
try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $agendamentos = $stmt->fetchAll(); // Pega todos os resultados

} catch (PDOException $e) {
    die("Erro ao buscar agendamentos: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header a { text-decoration: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background-color: #f4f4f4; }
        .status-marcado { color: blue; }
        .status-realizado { color: green; }
        .status-cancelado { color: red; }
    </style>
</head>
<body>

    <div class="header">
        <h2>
            <?php 
            // Título dinâmico
            if ($tipo_usuario_logado == 'admin') {
                echo "Todos os Agendamentos";
            } else {
                echo "Meus Agendamentos";
            }
            ?>
        </h2>
        <a href="dashboard.php">Voltar ao Painel</a>
    </div>

    <hr>

    <?php
    // 7. Verifica se há agendamentos para mostrar
    if (count($agendamentos) == 0) {
        
        echo "<p>Nenhum agendamento encontrado.</p>";

    } else {
        // 8. Se houver, cria a tabela
    ?>
        <table>
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Status</th>
                    
                    <?php
                    // Admin vê a coluna "Profissional"
                    if ($tipo_usuario_logado == 'admin') {
                        echo "<th>Profissional</th>";
                    }
                    ?>
                    
                    <th>Observações</th>
                    <th>Ações</th> </tr>
            </thead>
            <tbody>
                <?php
                // 9. Loop para exibir cada agendamento
                foreach ($agendamentos as $ag) {
                    
                    // Formata as datas para ficar mais amigável
                    $inicio_formatado = date('d/m/Y H:i', strtotime($ag['data_hora_inicio']));
                    $fim_formatado = date('d/m/Y H:i', strtotime($ag['data_hora_fim']));
                    
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($ag['nome_paciente']) . "</td>";
                    echo "<td>" . $inicio_formatado . "</td>";
                    echo "<td>" . $fim_formatado . "</td>";
                    
                    // Adiciona uma classe CSS para colorir o status
                    echo "<td class='status-" . $ag['status'] . "'>" . ucfirst($ag['status']) . "</td>";

                    // Admin vê o nome do profissional
                    if ($tipo_usuario_logado == 'admin') {
                        echo "<td>" . htmlspecialchars($ag['nome_profissional']) . "</td>";
                    }
                    
                    echo "<td>" . htmlspecialchars($ag['observacoes']) . "</td>";
                    
                    // Links para futuras ações (editar, cancelar)
                    // Links para futuras ações (editar, cancelar)
                    echo "<td>";

                    // SÓ mostra o link de edição se o status ainda for 'marcado'
                    if ($ag['status'] == 'marcado') {
                        echo "<a href='editar_agendamento.php?id=" . $ag['id'] . "'>Gerenciar</a>";
                    } else {
                        // Se não for 'marcado', apenas exibe um traço
                        echo "—";
                    }

                    echo "</td>";
                }
                ?>
            </tbody>
        </table>
    <?php 
    } // Fim do "else" 
    ?>

</body>
</html>