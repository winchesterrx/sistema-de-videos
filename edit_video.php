<?php
session_start();
include 'db/conexao.php';

// Verifica se o usuário está logado e é admin
if (!isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

// Obtém os dados do formulário
$video_id = intval($_POST['video_id']);
$titulo = mysqli_real_escape_string($conexao, $_POST['titulo']);
$descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
$setor_id = intval($_POST['setor_id']);

// Validações
if (!$video_id || empty($titulo) || empty($descricao) || !$setor_id) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
    exit;
}

// Atualiza o vídeo no banco de dados
$query = "UPDATE videos SET titulo = '$titulo', descricao = '$descricao', setor_id = $setor_id WHERE id = $video_id";
if (mysqli_query($conexao, $query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar o vídeo.']);
}
?>
