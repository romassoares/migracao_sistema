<?php
require_once __DIR__ . '/../../app/Controller/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['return'])) {
        header('Location: /auth/companys');
        exit;
    }
    if(createCompany()) {
        header('Location: /');
        exit;
    } else {
        $error = "Erro ao criar cliente! Por favor, preencha todos os campos corretamente.";
    }
}

$concorrentes = getConcorrentes();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar Novo Cliente</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        /* Remove as setas do lado do input number */
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow p-4" style="max-width: 400px; width: 100%; border-radius: 1rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);">
            <div class="card-body">
                <h3 class="card-title text-center mb-4">Criar novo cliente</h3>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger mt-2"><?= $error ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label for="codigo_cliente" class="form-label">Código do Cliente</label>
                        <input type="number" class="form-control" id="codigo_cliente" name="codigo_cliente">
                    </div>
                    <div class="mb-3">
                        <label for="nome_cliente" class="form-label">Nome do Cliente</label>
                        <input type="text" class="form-control" id="nome_cliente" name="nome_cliente">
                    </div>
                    <div class="mb-3">
                        <label for="concorrente" class="form-label">Concorrente</label>
                        <select class="form-select" id="concorrente" name="concorrente">
                            <option value="" disabled selected>Selecione um concorrente</option>
                            <?php foreach ($concorrentes as $concorrente): ?>
                                <option value="<?= htmlspecialchars($concorrente['id']) ?>">
                                    <?= htmlspecialchars($concorrente['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                        <button type="submit" name="return" class="btn btn-danger btn-sm">Voltar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#concorrente').select2({
                theme: "bootstrap-5",
                dropdownParent: $('.card-body'), // Garante que o dropdown fique dentro do card
                width: '100%',
                placeholder: "Selecione um concorrente",
                language: {
                    noResults: function() {
                        return "Nenhum cliente encontrado";
                    }
                }
            });
        });
    </script>
</body>
</html>
