<?php
session_start();
include 'db/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Você não tem permissão para responder.']);
    exit;
}

// Verifica se os dados foram enviados via JSON ou formulário HTML
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true);
    $comentario_id = intval($data['comentario_id'] ?? 0);
    $resposta_conteudo = trim($data['conteudo'] ?? '');
} else {
    $comentario_id = intval($_POST['comentario_id'] ?? 0);
    $resposta_conteudo = trim($_POST['conteudo'] ?? '');
}

$usuario_id = $_SESSION['user_id'];
$usuario_nome = $_SESSION['user_nome'] ?? '';

// Verifica se os dados são válidos
if (empty($comentario_id) || empty($resposta_conteudo)) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos ou resposta vazia.']);
    exit;
}

// Insere a resposta no banco de dados
$query = "INSERT INTO respostas (comentario_id, usuario_id, conteudo, data) VALUES (?, ?, ?, NOW())";
$stmt = $conexao->prepare($query);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Erro ao preparar a consulta.']);
    exit;
}

$stmt->bind_param('iis', $comentario_id, $usuario_id, $resposta_conteudo);

if ($stmt->execute()) {
    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        // Retorna JSON se for uma requisição AJAX
        echo json_encode([
            'success' => true,
            'message' => 'Resposta adicionada com sucesso.',
            'usuario_nome' => $usuario_nome,
            'data' => date('Y-m-d H:i:s') // Retorna a data atual
        ]);
    } else {
        // Redireciona de volta se for um formulário HTML
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao adicionar a resposta.']);
}

$stmt->close();
$conexao->close();
?>
