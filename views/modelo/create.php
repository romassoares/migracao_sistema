<?php include_once __DIR__ . '/../includes/head.php' ?>

<div class="card col-12 mx-auto" style="max-width: 600px;">
    <div class="card-header">
        Novo Modelo
    </div>
    <div class="card-body">
        <form action="/modelo/store" method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="nome_modelo" class="form-label">Nome do Modelo</label>
                <input type="text" class="form-control" id="nome_modelo" name="nome_modelo" required>
            </div>
            <div class="mb-3">
                <label for="layout" class="form-label">Layout</label>
                <select class="form-select select2" id="layout" name="layout" required>
                    <option value="">Selecione</option>
                    <?php foreach ($layouts as $layout): ?>
                        <option value="<?= $layout['id'] ?>"><?= $layout['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="concorrente" class="form-label">Concorrente</label>
                <select class="form-select select2" id="concorrente" name="concorrente" required>
                    <option value="">Selecione</option>
                    <?php foreach ($concorrentes as $concorrente): ?>
                        <option value="<?= $concorrente['id'] ?>"><?= $concorrente['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="tipo_arquivo" class="form-label">Tipo de Arquivo</label>
                <select class="form-select select2" id="tipo_arquivo" name="tipo_arquivo" required>
                    <option value="">Selecione</option>
                    <?php foreach ($tipos_arquivos as $tipo): ?>
                        <option value="<?= $tipo['id'] ?>"><?= $tipo['nome'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="form-label me-3">Ativo</label>
                <div class="form-check form-switch d-inline-block align-middle">
                    <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" checked>
                    <label class="form-check-label" for="ativo"></label>
                </div>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <a href="/modelo/index" class="btn btn-danger">Cancelar</a>
                <button type="submit" class="btn btn-success">Salvar</button>
            </div>
        </form>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/scripts.php' ?>
<script>
    $(document).ready(function() {
        $('#layout').select2({
            theme: "bootstrap-5",
            width: "100%",
            placeholder: "Selecione um layout",
            language: {
                noResults: function() {
                    return "Nenhum layout encontrado";
                }
            }
        });

        $('#concorrente').select2({
            theme: "bootstrap-5",
            width: "100%",
            placeholder: "Selecione um concorrente",
            language: {
                noResults: function() {
                    return "Nenhum concorrente encontrado";
                }
            }
        });

        $('#tipo_arquivo').select2({
            theme: "bootstrap-5",
            width: "100%",
            placeholder: "Selecione um tipo de arquivo",
            language: {
                noResults: function() {
                    return "Nenhum tipo de arquivo encontrado";
                }
            }
        });
    });
</script>