<?php
include 'db/conexao.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido. Use POST.');
    }

    if (!isset($_FILES['video'])) {
        throw new Exception('Nenhum arquivo enviado.');
    }

    $titulo = $_POST['titulo'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $setor_id = intval($_POST['setor_id'] ?? 0);

    if (empty($titulo) || empty($descricao) || $setor_id <= 0) {
        throw new Exception('Todos os campos são obrigatórios.');
    }

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $videoName = time() . '-' . basename($_FILES['video']['name']);
    $videoPath = $uploadDir . $videoName;

    if (!move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
        throw new Exception('Erro ao salvar o arquivo.');
    }

    $sql = "INSERT INTO videos (titulo, descricao, url_video, setor_id, data_upload) 
            VALUES ('$titulo', '$descricao', '$videoPath', $setor_id, NOW())";

    if (!mysqli_query($conexao, $sql)) {
        throw new Exception('Erro ao salvar os dados no banco de dados: ' . mysqli_error($conexao));
    }

    $id = mysqli_insert_id($conexao);
    echo json_encode(['success' => true, 'message' => 'Vídeo enviado com sucesso!', 'id' => $id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
