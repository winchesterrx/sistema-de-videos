<?php
session_start();
include 'db/conexao.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

// Obtém o ID do vídeo
$video_id = intval($_POST['video_id']);

if (!$video_id) {
    echo json_encode(['success' => false, 'error' => 'ID do vídeo inválido.']);
    exit;
}

// Executa a exclusão
$query = "DELETE FROM videos WHERE id = $video_id";
if (mysqli_query($conexao, $query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao excluir o vídeo.']);
}
?>
