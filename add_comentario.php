<?php
session_start();
include 'db/conexao.php';
header('Content-Type: application/json');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

// Recebe os dados enviados via JSON
$input = json_decode(file_get_contents('php://input'), true);
$video_id = isset($input['video_id']) ? intval($input['video_id']) : null;
$conteudo = isset($input['conteudo']) ? trim($input['conteudo']) : null;

// Valida os dados recebidos
if (!$video_id || empty($conteudo)) {
    echo json_encode(['success' => false, 'error' => 'ID do vídeo ou conteúdo não fornecido.']);
    exit;
}

// Insere o comentário no banco de dados
$sql = "INSERT INTO comentarios (video_id, usuario_id, conteudo, data) VALUES (?, ?, ?, NOW())";
$stmt = $conexao->prepare($sql);

if ($stmt) {
    $usuario_id = $_SESSION['user_id'];
    $stmt->bind_param("iis", $video_id, $usuario_id, $conteudo);
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'conteudo' => htmlspecialchars($conteudo),
            'data' => date('Y-m-d H:i:s'),
            'usuario_nome' => htmlspecialchars($_SESSION['user_nome'])
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao adicionar comentário.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor.']);
}

$conexao->close();
?>
