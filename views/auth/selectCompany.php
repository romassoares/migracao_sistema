<?php
require_once __DIR__ . '/../../app/Controller/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['create'])) {
        header('Location: /auth/new_company');
        exit;
    } else {
        if(check_company()) {
            header('Location: /');
        } else {
            $error = "Selecione um cliente!";
        }
    }
}

$companys = getCompanys();
?>



<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Selecionar cliente</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.0/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow login-card p-4" style="max-width: 400px; width: 100%; border-radius: 1rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);">
            <div class="card-body">
            <h3 class="card-title text-center mb-4">Clientes</h3>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger mt-2"><?= $error ?></div>
            <?php endif; ?>
            <form method="post">
                    <div class="mb-4">
                        <label for="company" class="form-label">Cliente</label>
                        <select class="form-select" id="company" name="company">
                            <option value="" disabled selected>Selecione um cliente</option>
                            <?php foreach ($companys as $company): ?>
                                <option value="<?= htmlspecialchars($company['id_cliente']) ?>">
                                    <?= htmlspecialchars($company['nome_cliente']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="select" class="btn btn-primary">Selecionar</button>
                        <button type="submit" name="create" class="btn btn-success">Criar novo cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        $( '#company' ).select2({
            theme: "bootstrap-5",
            width: "100%",
            placeholder: "Selecione um cliente",
            language: {
                noResults: function() {
                    return "Nenhum cliente encontrado";
                }
            }
        });
    </script>
</body>
</html>