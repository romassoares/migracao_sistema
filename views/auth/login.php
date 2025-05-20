<?php
require_once __DIR__ . '/../../app/Controller/AuthController.php';

session_unset();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (login()) {
        header('Location: /auth/companys');
        exit;
    } else {
        $error = "Usuário ou senha inválidos!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tela de Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow login-card p-4" style="max-width: 400px; width: 100%; border-radius: 1rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);">
            <div class="card-body">
            <h3 class="card-title text-center mb-4">Login</h3>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger mt-2"><?= $error ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                <label for="user" class="form-label">Usuário</label>
                <input type="text" class="form-control" id="user" name="user" required>
                </div>

                <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="password" required />
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                        <i class="bi bi-eye-slash" id="eyeIcon"></i>
                    </button>
                </div>
                </div>

                <div class="d-grid">
                <button type="submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>

