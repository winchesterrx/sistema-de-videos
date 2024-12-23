<?php
session_start();
include 'db/conexao.php';

// Verifica se o usuário está logado
$is_logged_in = isset($_SESSION['user_id']);
$usuario_id = $_SESSION['user_id'] ?? null;
$usuario_nome = $_SESSION['user_nome'] ?? 'Usuário';
$usuario_adm = $_SESSION['user_adm'] ?? false;

// Captura o ID do vídeo pela URL
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($video_id <= 0) {
    die('ID de vídeo inválido.');
}

// Configuração da paginação de comentários
$comentarios_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $comentarios_por_pagina;

// Busca as informações do vídeo
$query = "SELECT videos.*, setores.nome AS setor_nome, 
                 (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = videos.id) AS curtidas, 
                 (SELECT COUNT(*) FROM curtidas WHERE curtidas.video_id = videos.id AND curtidas.usuario_id = ?) AS usuario_curtiu 
          FROM videos 
          JOIN setores ON videos.setor_id = setores.id 
          WHERE videos.id = ?";
$stmt = $conexao->prepare($query);
$stmt->bind_param('ii', $usuario_id, $video_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Vídeo não encontrado.');
}

$video = $result->fetch_assoc();
$total_curtidas = $video['curtidas'] ?? 0;
$usuario_curtiu = $video['usuario_curtiu'] > 0;

// Busca os comentários do vídeo
$query_comentarios = "SELECT comentarios.*, usuarios.nome AS usuario_nome 
                      FROM comentarios 
                      JOIN usuarios ON comentarios.usuario_id = usuarios.id 
                      WHERE comentarios.video_id = ? 
                      ORDER BY comentarios.data DESC";
$stmt_comentarios = $conexao->prepare($query_comentarios);
$stmt_comentarios->bind_param('i', $video_id);
$stmt_comentarios->execute();
$comentarios_result = $stmt_comentarios->get_result();
//respota
$respostas_query = "SELECT respostas.*, usuarios.nome AS usuario_nome 
                    FROM respostas 
                    JOIN usuarios ON respostas.usuario_id = usuarios.id 
                    WHERE respostas.comentario_id = ? 
                    ORDER BY respostas.data ASC";
$stmt_respostas = $conexao->prepare($respostas_query);
$stmt_respostas->bind_param('i', $comentario['id']);
$stmt_respostas->execute();
$respostas_result = $stmt_respostas->get_result();

// Contagem total de comentários
$total_comentarios_query = "SELECT COUNT(*) AS total FROM comentarios WHERE video_id = ?";
$stmt_total = $conexao->prepare($total_comentarios_query);
$stmt_total->bind_param('i', $video_id);
$stmt_total->execute();
$total_comentarios = $stmt_total->get_result()->fetch_assoc()['total'];
$total_paginas = ceil($total_comentarios / $comentarios_por_pagina);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($video['titulo']) ?> - Detalhes</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            color: #333;
        }

        .header {
            background: linear-gradient(90deg, #ff6f00, #ff8c1a);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header img {
            height: 50px;
        }

        .header h1 {
            font-size: 1.8rem;
            margin: 0;
            font-weight: 700;
        }

        .video-container, .comments-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .video-container video {
            width: 100%;
            border-radius: 10px;
        }

        .btn-like {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: <?= $usuario_curtiu ? '#ff6f00' : '#f1f1f1' ?>;
            color: <?= $usuario_curtiu ? 'white' : '#333' ?>;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-like:hover {
            background-color: #ff8c1a;
            color: white;
        }

        .comments-container h4 {
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .comment, .reply {
            background: #f4f4f4;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }

        .comment img, .reply img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .comment .content, .reply .content {
            flex: 1;
        }

        .comment strong, .reply strong {
            font-size: 14px;
            color: #333;
            font-weight: 600;
        }

        .comment p, .reply p {
            font-size: 14px;
            margin: 5px 0;
            color: #555;
        }

        .comment small, .reply small {
            font-size: 12px;
            color: #999;
        }

        .reply {
            margin-left: 50px;
            background: #f9f9f9;
        }

        .btn-delete {
            background: none;
            color: #ff6f00;
            border: none;
            font-size: 0.9rem;
            cursor: pointer;
            margin-left: 10px;
            padding: 5px 10px;
            transition: color 0.3s ease;
        }

        .btn-delete:hover {
            color: #e63900;
        }

        .btn-reply {
            background: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-reply:hover {
            background-color: #0056b3;
        }

        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 20px;
        }
      
.btn-danger {
    background-color: #dc3545;
    color: #fff;
    border: none;
    font-size: 16px;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.btn-danger:hover {
    background-color: #b52d3c;
    color: #fff;
}
</style>

    </style>
</head>
<body>
    <div class="header">
        <img src="img/martinez.png" alt="Logo Martinez">
        <h1>Detalhes do Vídeo</h1>
    </div>

    <div class="video-container">
        <video controls>
            <source src="<?= htmlspecialchars($video['url_video']) ?>" type="video/mp4">
        </video>
        <h3><?= htmlspecialchars($video['titulo']) ?></h3>
        <p><?= htmlspecialchars($video['descricao']) ?></p>
        <p><strong>Setor:</strong> <?= htmlspecialchars($video['setor_nome']) ?></p>
        <p>
            <button id="btn-like" class="btn-like" onclick="curtirOuDescurtir(<?= $video_id ?>)">
                <i class="fa fa-thumbs-up"></i> <span id="curtidas-count"><?= $total_curtidas ?></span>
            </button>
        </p>
    </div>

   
   
    <div id="toast-container"></div>

    <div class="comments-container">
    <h4>Comentários</h4>
    <?php if ($is_logged_in): ?>
        <textarea id="commentText" class="form-control comment-input" placeholder="Adicione um comentário..."></textarea>
        <button class="btn btn-comment" onclick="adicionarComentario(<?= $video_id ?>)">Comentar</button>
    <?php else: ?>
        <div class="alert alert-warning">
            Você precisa estar logado para comentar. <a href="login.php" class="btn btn-login">Login</a>
        </div>
    <?php endif; ?>

    <form id="formExcluir" method="POST">
        <div class="mb-3">
            <select class="form-select select-excluir" id="tipoExclusao" name="tipo" required>
                <option value="comentarios">Excluir Comentários e Respostas</option>
                <option value="respostas">Excluir Apenas Respostas</option>
            </select>
        </div>
        <button type="button" class="btn btn-excluir" onclick="excluirSelecionados()">Excluir Selecionados</button>
        <div id="comentarios-list">
            <?php while ($comentario = $comentarios_result->fetch_assoc()): ?>
                <div class="comment d-flex align-items-start">
                    <input type="checkbox" class="form-check-input me-2" name="ids[]" value="<?= $comentario['id'] ?>">
                    <div class="content">
                        <strong><?= htmlspecialchars($comentario['usuario_nome']) ?></strong>
                        <p><?= htmlspecialchars($comentario['conteudo']) ?></p>
                        <small><?= date('d/m/Y H:i', strtotime($comentario['data'])) ?></small>

                        <?php
                        $respostas_query = "SELECT respostas.*, usuarios.nome AS usuario_nome 
                                            FROM respostas 
                                            JOIN usuarios ON respostas.usuario_id = usuarios.id 
                                            WHERE respostas.comentario_id = ? 
                                            ORDER BY respostas.data ASC";
                        $stmt_respostas = $conexao->prepare($respostas_query);
                        $stmt_respostas->bind_param('i', $comentario['id']);
                        $stmt_respostas->execute();
                        $respostas_result = $stmt_respostas->get_result();
                        ?>
                        <?php while ($resposta = $respostas_result->fetch_assoc()): ?>
                            <div class="reply">
                                <input type="checkbox" class="form-check-input me-2" name="ids[]" value="<?= $resposta['id'] ?>">
                                <div class="content">
                                    <strong><?= htmlspecialchars($resposta['usuario_nome']) ?></strong>
                                    <p><?= htmlspecialchars($resposta['conteudo']) ?></p>
                                    <small><?= date('d/m/Y H:i', strtotime($resposta['data'])) ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <?php if ($is_logged_in): ?>
                            <button type="button" class="btn btn-reply" onclick="abrirRespostaForm(<?= $comentario['id'] ?>)">Responder</button>
                            <div id="resposta-form-<?= $comentario['id'] ?>" class="resposta-form" style="display: none;">
                                <textarea class="form-control resposta-input" id="resposta-conteudo-<?= $comentario['id'] ?>" placeholder="Escreva sua resposta..." required></textarea>
                                <button type="button" class="btn btn-send" onclick="enviarResposta(<?= $comentario['id'] ?>)">Enviar</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </form>
</div>

<div id="toast-container"></div>

<style>
/* Fundo atualizado */
body {
    background: linear-gradient(to bottom, #f3f3f3, #ffe8d6);
    color: #333;
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
}

/* Container de comentários */
.comments-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
}

/* Títulos */
h4 {
    color: #ff6f00;
    font-weight: bold;
    font-size: 1.6rem;
    text-align: center;
    margin-bottom: 20px;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
}

/* Comentários e respostas */
.comment, .reply {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    border: 1px solid #ddd;
    transition: box-shadow 0.3s ease;
}

.comment:hover, .reply:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.comment .content, .reply .content {
    flex: 1;
}

strong {
    color: #333;
    font-size: 14px;
    font-weight: bold;
}

.comment p, .reply p {
    font-size: 14px;
    color: #555;
    margin: 5px 0;
}

small {
    font-size: 12px;
    color: #888;
}

/* Botões */
.btn {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: bold;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
}

.btn-comment {
    background: #ff6f00;
    color: white;
}

.btn-login {
    background: #28a745;
    color: white;
}

.btn-excluir {
    background: #e63946;
    color: white;
}

.btn-reply, .btn-send {
    background: #28a745;
    color: white;
}

textarea {
    width: 100%;
    background: #ffffff;
    border: 1px solid #ddd;
    color: #333;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
}

/* Notificações flutuantes */
#toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: fadeInOut 4s;
}

.toast.success {
    border-left: 5px solid #28a745;
}

.toast.error {
    border-left: 5px solid #e63946;
}

.toast button {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 1.2rem;
    margin-left: 10px;
}

@keyframes fadeInOut {
    0% { opacity: 0; transform: translateY(-20px); }
    10%, 90% { opacity: 1; transform: translateY(0); }
    100% { opacity: 0; transform: translateY(-20px); }
}
</style>

<script>
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.classList.add('toast', type);
    toast.innerHTML = `<span>${message}</span><button onclick="this.parentElement.remove()">✖</button>`;
    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 4000);
}

function abrirRespostaForm(comentarioId) {
    const respostaForm = document.getElementById(`resposta-form-${comentarioId}`);
    respostaForm.style.display = respostaForm.style.display === 'none' ? 'block' : 'none';
}

function enviarResposta(comentarioId) {
    const conteudo = document.getElementById(`resposta-conteudo-${comentarioId}`).value.trim();

    if (!conteudo) {
        showToast('A resposta não pode estar vazia.', 'error');
        return false;
    }

    // Simulação de envio
    showToast('Resposta enviada com sucesso!', 'success');
}
</script>


        

    <script>
        function curtirOuDescurtir(videoId) {
            const btnLike = document.getElementById('btn-like');
            const curtidasCount = document.getElementById('curtidas-count');

            fetch('add_curtida.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    curtidasCount.textContent = data.curtidas;
                    btnLike.classList.toggle('liked', data.action === 'added');
                    btnLike.style.backgroundColor = data.action === 'added' ? '#ff6f00' : '#f1f1f1';
                    btnLike.style.color = data.action === 'added' ? '#fff' : '#333';
                } else {
                    alert(data.error || 'Erro ao processar.');
                }
            })
            .catch(() => alert('Erro ao processar a solicitação.'));
        }

        function adicionarComentario(videoId) {
            const comentarioInput = document.getElementById('commentText');
            const comentario = comentarioInput.value.trim();

            if (comentario === '') {
                alert('O comentário não pode estar vazio.');
                return;
            }

            fetch('add_comentario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ video_id: videoId, conteudo: comentario })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao adicionar o comentário.');
                }
            })
            .catch(() => alert('Erro ao processar a solicitação.'));
        }

        function adicionarResposta(comentarioId) {
            const respostaInput = document.getElementById(`resposta-text-${comentarioId}`);
            const resposta = respostaInput.value.trim();

            if (resposta === '') {
                alert('A resposta não pode estar vazia.');
                return;
            }

            fetch('add_resposta.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comentario_id: comentarioId, conteudo: resposta })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao adicionar a resposta.');
                }
            })
            .catch(() => alert('Erro ao processar a solicitação.'));
        }

        function abrirRespostaForm(comentarioId) {
            const respostaForm = document.getElementById(`resposta-form-${comentarioId}`);
            respostaForm.style.display = respostaForm.style.display === 'none' ? 'block' : 'none';
        }

        function excluirComentario(comentarioId) {
            if (!confirm("Tem certeza de que deseja excluir este comentário?")) {
                return;
            }

            fetch('delete_comentario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comentario_id: comentarioId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Comentário excluído com sucesso!');
                    location.reload();
                } else {
                    alert(data.error || 'Erro ao excluir o comentário.');
                }
            })
            .catch(() => alert('Erro ao processar a solicitação.'));
        }
        
    function excluirSelecionados() {
    const checkboxes = document.querySelectorAll('input[name="ids[]"]:checked');
    const tipoExclusao = document.getElementById('tipoExclusao').value;

    if (checkboxes.length === 0) {
        showToast('Por favor, selecione pelo menos um comentário ou resposta para excluir.', 'error');
        return;
    }

    const ids = Array.from(checkboxes).map(checkbox => checkbox.value);

    const formData = new FormData();
    ids.forEach(id => formData.append('ids[]', id));
    formData.append('tipo', tipoExclusao);

    fetch('admin_bulk_delete.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Itens excluídos com sucesso!', 'success');
            location.reload();
        } else {
            showToast(data.error || 'Erro ao processar a exclusão.', 'error');
        }
    })
    .catch(() => showToast('Erro ao processar a solicitação.', 'error'));
}


    function abrirRespostaForm(comentarioId) {
        const respostaForm = document.getElementById(`resposta-form-${comentarioId}`);
        respostaForm.style.display = respostaForm.style.display === 'none' ? 'block' : 'none';
    }

    function enviarResposta(comentarioId) {
        const respostaConteudo = document.getElementById(`resposta-conteudo-${comentarioId}`).value.trim();

        if (!respostaConteudo) {
            alert('A resposta não pode estar vazia.');
            return;
        }

        fetch('add_resposta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comentario_id: comentarioId, conteudo: respostaConteudo })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Resposta adicionada com sucesso!');
                location.reload();
            } else {
                alert(data.error || 'Erro ao adicionar a resposta.');
            }
        })
        .catch(() => alert('Erro ao processar a solicitação.'));
    }



    </script>
</body>
</html>
