<?php
$host = 'localhost'; // Ou o IP do servidor do banco
$user = 'root'; // Usuário do banco
$password = ''; // Senha do banco
$database = 'treinamentos'; // Nome do banco de dados

// Inicializa a conexão
$conn = new mysqli($host, $user, $password, $database);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Erro ao conectar ao banco de dados: " . $conn->connect_error);
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $cidade = $_POST['cidade'];
    $adm = $_POST['adm'];

    $sql = "UPDATE usuarios SET nome = ?, email = ?, municipio_id = ?, ADM = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('sssii', $nome, $email, $cidade, $adm, $id);
        if ($stmt->execute()) {
            header('Location: listar_usuarios.php?msg=success');
        } else {
            die("Erro ao atualizar o usuário: " . $stmt->error);
        }
    } else {
        die("Erro ao preparar a consulta: " . $conn->error);
    }
}
?>
