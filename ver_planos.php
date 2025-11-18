<?php
$tituloPagina = "Gerenciar Planos e Pacotes"; 
require_once 'header.php'; 
require_once 'conexao.php'; 

$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

$sql_base = "SELECT plan.*, pac.nome_completo AS nome_paciente, usr.nome AS nome_profissional
            FROM planos_paciente AS plan
            JOIN pacientes AS pac ON plan.id_paciente = pac.id
            JOIN usuarios AS usr ON plan.id_profissional = usr.id";

$params = []; 

if ($tipo_usuario_logado == 'profissional') {
    $sql_base .= " WHERE plan.id_profissional = ?";
    $params[] = $id_usuario_logado;
}

$sql_base .= " ORDER BY pac.nome_completo ASC, plan.data_criacao DESC";

try {
    $stmt = $pdo->prepare($sql_base);
    $stmt->execute($params);
    $planos = $stmt->fetchAll(); 
} catch (PDOException $e) {
    die("Erro ao buscar planos: " . $e->getMessage());
}
?>

<div style="margin-bottom: 20px;">
    <a href="adicionar_plano.php" style="text-decoration: none; padding: 10px 15px; background-color: #2ecc71; color: white; border-radius: 4px; font-weight: bold;">
        + Adicionar Novo Plano/Pacote
    </a>
</div>

<?php if (count($planos) == 0): ?>
    <p>Nenhum plano ou pacote cadastrado.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Paciente</th>
                <th>Tipo</th>
                <th>Atendimento</th>
                <th>Valor</th> <th>Progresso</th>
                <th>Status</th>
                <?php if ($tipo_usuario_logado == 'admin') echo "<th>Profissional</th>"; ?>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($planos as $plano): 
                $progresso = $plano['sessoes_utilizadas'] . ' / ' . $plano['sessoes_contratadas'];
                $style = ($plano['status'] == 'Concluido') ? 'style="color: #95a5a6;"' : '';
            ?>
                <tr <?php echo $style; ?>>
                    <td><?php echo htmlspecialchars($plano['nome_paciente']); ?></td>
                    <td><?php echo htmlspecialchars($plano['tipo_plano']); ?></td>
                    <td><?php echo ucfirst($plano['tipo_atendimento']); ?></td>
                    <td>R$ <?php echo number_format($plano['valor'], 2, ',', '.'); ?></td>
                    
                    <td><b><?php echo $progresso; ?></b></td>
                    <td><?php echo htmlspecialchars($plano['status']); ?></td>

                    <?php if ($tipo_usuario_logado == 'admin'): ?>
                        <td><?php echo htmlspecialchars($plano['nome_profissional']); ?></td>
                    <?php endif; ?>
                    
                    <td>
                        <a href='editar_plano.php?id=<?php echo $plano['id']; ?>'>Editar</a>
                        <?php if ($plano['status'] == 'Ativo'): ?>
                            | <a href='gerar_pagamento.php?id_plano=<?php echo $plano['id']; ?>' target='_blank' style='color: #27ae60; font-weight: bold;'>Pagar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once 'footer.php'; ?>