<?php include_once __DIR__ . '/../includes/head.php' ?>

<div class="d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow login-card p-4" style="max-width: 400px; width: 100%; border-radius: 1rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">Alterar Senha</h3>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success mt-2">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger mt-2">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="/auth/salvar_senha">
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha atual</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="senha" id="senha" required />
                        <button class="btn btn-outline-secondary" type="button" id="toggleSenha" tabindex="-1">
                            <i class="bi bi-eye-slash" id="eyeIconSenha"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="nova_senha" class="form-label">Nova senha</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="nova_senha" id="nova_senha" required />
                        <button class="btn btn-outline-secondary" type="button" id="toggleNovaSenha" tabindex="-1">
                            <i class="bi bi-eye-slash" id="eyeIconNovaSenha"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="confirma_senha" class="form-label">Repita a nova senha</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="confirma_senha" id="confirma_senha" required />
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmaSenha" tabindex="-1">
                            <i class="bi bi-eye-slash" id="eyeIconConfirmaSenha"></i>
                        </button>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Salvar nova senha</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        icon.parentElement.addEventListener('click', function () {
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
    togglePassword('senha', 'eyeIconSenha');
    togglePassword('nova_senha', 'eyeIconNovaSenha');
    togglePassword('confirma_senha', 'eyeIconConfirmaSenha');
</script>