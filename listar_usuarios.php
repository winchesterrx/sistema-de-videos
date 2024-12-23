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

// Inicializa a variável $search
$search = isset($_GET['search']) ? $_GET['search'] : '';
$is_logged_in = isset($_SESSION['user_id']);
$usuario_nome = $_SESSION['user_nome'] ?? null;
// Prepara a consulta SQL
$sql = "SELECT u.id, u.nome, u.email, u.data_cadastro, u.ADM, m.nome AS cidade, e.sigla AS estado
        FROM usuarios u
        LEFT JOIN municipio m ON u.municipio_id = m.id
        LEFT JOIN uf e ON u.estado_id = e.id
        WHERE u.nome LIKE ? OR u.email LIKE ? OR m.nome LIKE ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar a consulta SQL: " . $conn->error);
}

// Executa a consulta
$searchParam = "%$search%";
$stmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Erro ao executar a consulta SQL: " . $stmt->error);
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Usuários</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .header {
            background-color: #ff6f00;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header .logo {
            height: 50px;
        }

        .header .welcome {
            font-size: 18px;
            font-weight: 600;
        }

        .main-title {
            text-align: center;
            margin: 20px 0;
            font-size: 2rem;
            color: #ff6f00;
            font-weight: bold;
        }

        .btn {
            font-size: 14px;
            font-weight: 500;
            border-radius: 30px;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #007bff;
            color: white;
            border: none;
        }

        .btn-edit:hover {
            background: #0056b3;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .table {
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background: #ff6f00;
            color: white;
        }

        .admin-badge {
            color: white;
            background-color: #28a745;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }

        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <img src="img/martinez.png" alt="Logo" class="logo">
        <div class="welcome">Bem-vindo, <?= htmlspecialchars($usuario_nome) ?>!</div>
    </div>

    <!-- Main Title -->
    <h1 class="main-title">Listagem de Usuários</h1>

    <!-- Mensagem de Sucesso -->
    <?php if (!empty($success_message)): ?>
        <div class="container alert alert-success mt-3">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <!-- Tabela de Usuários -->
    <div class="container mt-4">
        <table class="table table-striped text-center">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Cidade</th>
                    <th>Estado</th>
                    <th>Administrador</th>
                    <th>Data de Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT u.id, u.nome, u.email, u.data_cadastro, u.ADM, m.nome AS cidade, e.sigla AS estado
                        FROM usuarios u
                        LEFT JOIN municipio m ON u.municipio_id = m.id
                        LEFT JOIN uf e ON u.estado_id = e.id
                        WHERE u.nome LIKE ? OR u.email LIKE ? OR m.nome LIKE ?";
                $stmt = $conn->prepare($sql);
                $searchParam = "%$search%";
                $stmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['nome']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['cidade'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($row['estado'] ?? 'N/A') ?></td>
                        <td><?= $row['ADM'] === 'S' ? '<span class="admin-badge">Sim</span>' : 'Não' ?></td>
                        <td><?= htmlspecialchars($row['data_cadastro']) ?></td>
                        <td>
                            <button class="btn btn-edit" data-bs-toggle="modal" data-bs-target="#editModal" 
                                    data-id="<?= $row['id'] ?>" 
                                    data-nome="<?= $row['nome'] ?>" 
                                    data-email="<?= $row['email'] ?>" 
                                    data-municipio="<?= $row['cidade'] ?>" 
                                    data-adm="<?= $row['ADM'] ?>">
                                Editar
                            </button>
                            <button class="btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                    data-id="<?= $row['id'] ?>" 
                                    data-nome="<?= $row['nome'] ?>">
                                Excluir
                            </button>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="8" class="text-center">Nenhum usuário encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="update_id" id="edit-id">
                    <div class="mb-3">
                        <label for="edit-nome" class="form-label">Nome</label>
                        <input type="text" class="form-control" name="nome" id="edit-nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit-email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-municipio" class="form-label">Município</label>
                        <input type="text" class="form-control" name="municipio" id="edit-municipio" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-adm" class="form-label">Administrador</label>
                        <select class="form-select" name="adm" id="edit-adm" required>
                            <option value="S">Sim</option>
                            <option value="N">Não</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Excluir Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir o usuário <strong id="delete-username"></strong>?
                    <input type="hidden" name="delete_id" id="delete-id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal de Edição
        document.querySelectorAll('.btn-edit').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const nome = button.getAttribute('data-nome');
                const email = button.getAttribute('data-email');
                const municipio = button.getAttribute('data-municipio');
                const adm = button.getAttribute('data-adm');

                document.getElementById('edit-id').value = id;
                document.getElementById('edit-nome').value = nome;
                document.getElementById('edit-email').value = email;
                document.getElementById('edit-municipio').value = municipio;
                document.getElementById('edit-adm').value = adm;
            });
        });

        // Modal de Exclusão
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-id');
                const nome = button.getAttribute('data-nome');

                document.getElementById('delete-id').value = id;
                document.getElementById('delete-username').innerText = nome;
            });
        });
    </script>
</body>
</html>
