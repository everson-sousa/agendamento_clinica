<?php
$tituloPagina = "Adicionar Novo Plano/Pacote"; 
require_once 'header.php'; 
require_once 'conexao.php'; 

$id_usuario_logado = $_SESSION['usuario_id'];
$tipo_usuario_logado = $_SESSION['usuario_tipo'];

// LÓGICA: Buscar os PACIENTES deste profissional
$lista_pacientes = [];
try {
    $sql_pacientes = "SELECT id, nome_completo FROM pacientes 
                      WHERE id_profissional_responsavel = ? AND status = 'ativo' 
                      ORDER BY nome_completo ASC";
    
    $stmt_pacientes = $pdo->prepare($sql_pacientes);
    $stmt_pacientes->execute([$id_usuario_logado]);
    $lista_pacientes = $stmt_pacientes->fetchAll();

} catch (PDOException $e) {
    echo "Erro ao buscar pacientes: " . $e->getMessage();
}

// SE FOR ADMIN, precisa buscar os profissionais
$lista_profissionais = [];
if ($tipo_usuario_logado == 'admin') {
    try {
        $sql_profissionais = "SELECT id, nome FROM usuarios 
                              WHERE tipo_acesso = 'profissional' AND status = 'ativo' ORDER BY nome ASC";
        $stmt_profissionais = $pdo->query($sql_profissionais);
        $lista_profissionais = $stmt_profissionais->fetchAll();
    } catch (PDOException $e) {
        echo "Erro ao buscar profissionais: " . $e->getMessage();
    }
}
?>

<p>Use este formulário para criar um novo plano e agendar as sessões sequenciais.</p>

<form action="processa_plano.php" method="POST">

    <?php if ($tipo_usuario_logado == 'admin'): ?>
        <div>
            <label for="id_profissional">Profissional:</label>
            <select id="id_profissional" name="id_profissional" required>
                <option value="">Selecione o profissional...</option>
                <?php foreach ($lista_profissionais as $prof): ?>
                    <option value="<?php echo $prof['id']; ?>"><?php echo htmlspecialchars($prof['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else: ?>
        <input type="hidden" name="id_profissional" value="<?php echo $id_usuario_logado; ?>">
    <?php endif; ?>

    <div>
        <label for="id_paciente">Paciente:</label>
        <select id="id_paciente" name="id_paciente" required>
            <option value="">
                <?php echo ($tipo_usuario_logado == 'admin') ? 'Selecione um profissional acima' : 'Selecione seu paciente...'; ?>
            </option>
            
            <?php if ($tipo_usuario_logado == 'profissional'): ?>
                <?php foreach ($lista_pacientes as $paciente): ?>
                    <option value="<?php echo $paciente['id']; ?>"><?php echo htmlspecialchars($paciente['nome_completo']); ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>
    
    <hr style="margin: 20px 0;">

    <div>
        <label for="tipo_plano">Tipo de Registro:</label>
        <select id="tipo_plano" name="tipo_plano" required>
            <option value="Tratamento">Plano de Tratamento (Total de sessões)</option>
            <option value="Pacote">Pacote de Pagamento (Sessões pré-pagas)</option>
        </select>
    </div>
    
    <div>
        <label for="tipo_atendimento">Tipo de Atendimento (Define o N° de Sessões):</label>
        <select id="tipo_atendimento" name="tipo_atendimento" required>
            <option value="terapia">Terapia (Fixo: 4 Sessões)</option>
            <option value="avaliacao">Avaliação (Fixo: 10 Sessões)</option>
        </select>
    </div>
    
    <hr style="margin: 20px 0;">

    <h3 style="margin-bottom: 10px;">Agendamento da Primeira Sessão</h3>
    <p style="margin-bottom: 15px;">As sessões seguintes serão marcadas semanalmente neste mesmo horário.</p>

    <div>
        <label for="data_inicio">Data da 1ª Consulta:</label>
        <input type="date" id="data_inicio" name="data_inicio" required>
    </div>
    <div>
        <label for="hora_inicio">Hora de Início:</label>
        <input type="time" id="hora_inicio" name="hora_inicio" required>
    </div>
    <div>
        <label for="hora_fim">Hora de Fim:</label>
        <input type="time" id="hora_fim" name="hora_fim" required>
    </div>

    <button type="submit" style="background-color: #e67e22;">Salvar Plano e Agendar Sessões</button>
</form>

<?php
// (Vamos pular o AJAX do admin por enquanto)
require_once 'footer.php'; 
?>