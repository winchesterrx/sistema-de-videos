<?php
include 'db/conexao.php';

// Inicialize as variáveis
$sucesso = false;
$erro = "";

// Carregar estados (UF) do banco de dados
$ufs_query = "SELECT id, nome FROM UF ORDER BY nome ASC";
$ufs_result = mysqli_query($conexao, $ufs_query);
$ufs = [];
while ($row = mysqli_fetch_assoc($ufs_result)) {
    $ufs[] = $row;
}

// Carregar municípios e organizá-los por UF
$municipios_query = "SELECT id, nome, estado_id FROM municipio ORDER BY nome ASC";
$municipios_result = mysqli_query($conexao, $municipios_query);
$municipios = [];
while ($row = mysqli_fetch_assoc($municipios_result)) {
    $municipios[$row['estado_id']][] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_BCRYPT);
    $uf_id = intval($_POST['uf']);
    $municipio_id = intval($_POST['municipio']);

    // Verificar se o e-mail já existe
    $email_check_query = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conexao->prepare($email_check_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $erro = "O e-mail informado já está cadastrado.";
    } else {
        // Inserir o usuário no banco de dados
        $insert_query = "INSERT INTO usuarios (nome, email, senha, estado_id, municipio_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexao->prepare($insert_query);
        $stmt->bind_param("sssii", $nome, $email, $senha, $uf_id, $municipio_id);

        if ($stmt->execute()) {
            $sucesso = true;
        } else {
            $erro = "Erro ao registrar usuário. Tente novamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuário</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to bottom right, #f57c00, #ff9800);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .register-container {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0px 6px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 15px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control:focus {
            box-shadow: 0 0 5px rgba(245, 124, 0, 0.5);
            border-color: #f57c00;
        }

        .btn-primary, .btn-secondary {
            width: 100%;
            padding: 10px;
            font-size: 15px;
            font-weight: bold;
            border-radius: 8px;
            border: none;
            transition: background 0.3s ease, box-shadow 0.3s ease;
        }

        .btn-primary {
            background: #f57c00;
            color: white;
        }

        .btn-primary:hover {
            background: #ffa726;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: #555;
            color: white;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #777;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        .progress {
            height: 8px;
            border-radius: 5px;
            margin-top: 5px;
        }

        #feedback-senha {
            font-size: 13px;
            margin-top: 5px;
            font-weight: bold;
            color: #555;
        }

        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: auto;
            width: 100%;
        }

        footer a {
            color: #f57c00;
            text-decoration: none;
        }

        footer a:hover {
            color: #ffa726;
            text-decoration: underline;
        }
    </style>
    <script>
        const municipios = <?= json_encode($municipios); ?>;

        function atualizarMunicipios() {
            const ufId = document.getElementById('uf').value;
            const municipioSelect = document.getElementById('municipio');
            municipioSelect.innerHTML = '<option value="" disabled selected>Selecione um município</option>';

            if (municipios[ufId]) {
                municipios[ufId].forEach((municipio) => {
                    const option = document.createElement('option');
                    option.value = municipio.id;
                    option.textContent = municipio.nome;
                    municipioSelect.appendChild(option);
                });
            }
        }

        function verificarForcaSenha() {
            const senha = document.getElementById('senha').value;
            const barraProgresso = document.getElementById('barra-progresso');
            const feedback = document.getElementById('feedback-senha');

            let forca = 0;

            if (senha.length >= 8) forca++;
            if (/[A-Z]/.test(senha)) forca++;
            if (/[a-z]/.test(senha)) forca++;
            if (/\d/.test(senha)) forca++;
            if (/[@$!%*?&]/.test(senha)) forca++;

            barraProgresso.style.width = `${forca * 20}%`;

            if (forca === 0) {
                feedback.textContent = "Muito fraca";
                barraProgresso.className = "progress-bar bg-danger";
            } else if (forca <= 2) {
                feedback.textContent = "Fraca";
                barraProgresso.className = "progress-bar bg-warning";
            } else if (forca === 3) {
                feedback.textContent = "Moderada";
                barraProgresso.className = "progress-bar bg-info";
            } else if (forca === 4) {
                feedback.textContent = "Forte";
                barraProgresso.className = "progress-bar bg-success";
            } else if (forca === 5) {
                feedback.textContent = "Muito forte";
                barraProgresso.className = "progress-bar bg-primary";
            }
        }
    </script>
</head>
<body>
<div class="register-container">
    <h2>Registrar</h2>
    <?php if ($sucesso): ?>
        <div class="alert alert-success" role="alert">
            Cadastro realizado com sucesso! Você será redirecionado para o login em breve.
        </div>
        <script>
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        </script>
    <?php elseif (!empty($erro)): ?>
        <div class="alert alert-danger" role="alert">
            <?= $erro ?>
        </div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group mb-3">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" class="form-control" placeholder="Digite seu nome" required>
        </div>
        <div class="form-group mb-3">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Digite seu email" required>
        </div>
        <div class="form-group mb-3">
            <label for="uf">Estado (UF)</label>
            <select id="uf" name="uf" class="form-control" onchange="atualizarMunicipios()" required>
                <option value="" disabled selected>Selecione um estado</option>
                <?php foreach ($ufs as $uf): ?>
                    <option value="<?= $uf['id'] ?>"><?= htmlspecialchars($uf['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group mb-3">
            <label for="municipio">Município</label>
            <select id="municipio" name="municipio" class="form-control" required>
                <option value="" disabled selected>Selecione um município</option>
            </select>
        </div>
        <div class="form-group mb-3">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" class="form-control" placeholder="Crie uma senha forte" oninput="verificarForcaSenha()" required>
            <div class="progress mt-2">
                <div id="barra-progresso" class="progress-bar bg-danger" role="progressbar" style="width: 0%;"></div>
            </div>
            <span id="feedback-senha" class="d-block mt-2">Muito fraca</span>
        </div>
        <button type="submit" class="btn btn-primary">Registrar</button>
    </form>
    <a href="login.php" class="btn btn-secondary">Voltar para Login</a>
</div>

<footer>
    <p>&copy; 2024 Sua Empresa. Todos os direitos reservados. <a href="#">Política de Privacidade</a></p>
</footer>
</body>
</html>
