<?php
session_start();
include 'db/conexao.php';

// Verifica se o usuário é administrador
if (!isset($_SESSION['user_adm']) || !$_SESSION['user_adm']) {
    die(json_encode(['success' => false, 'error' => 'Acesso negado.']));
}

// Recebe o ID da resposta
$resposta_id = intval($_POST['resposta_id'] ?? 0);
if ($resposta_id <= 0) {
    die(json_encode(['success' => false, 'error' => 'Resposta inválida.']));
}

// Exclui a resposta
$stmt = $conexao->prepare("DELETE FROM respostas WHERE id = ?");
$stmt->bind_param('i', $resposta_id);
$stmt->execute();

echo json_encode(['success' => true]);
?>
