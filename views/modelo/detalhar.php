<?php include_once __DIR__ . '/../includes/head.php'; ?>

<div class="card col-12">
    <div class="card-header">
        Detalhes do Modelo
    </div>
    <div class="card-body p-0">
        <div class="p-3">
            <div class="row mb-2 align-items-center">
                <div class="col-md-3">
                    <strong>Nome do Modelo:</strong>
                    <span><?= $modelo['nome_modelo'] ?? '' ?></span>
                </div>
                <div class="col-md-3">
                    <strong>Layout:</strong>
                    <span><?= $modelo['layout'] ?? '' ?></span>
                </div>
                <div class="col-md-3">
                    <strong>Concorrente:</strong>
                    <span><?= $modelo['concorrente'] ?? '' ?></span>
                </div>
            </div>
            <div class="row mb-2">
                <?php if (!empty($arquivos)): ?>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <strong>Arquivos importados:</strong>
                        </div>
                        <table class="table table-sm table-bordered table-striped mb-0 mt-1">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:40px;">#</th>
                                    <th>Nome do Arquivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($arquivos as $i => $arquivo): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td><?= $arquivo['nome_arquivo'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <strong>Arquivos importados:</strong>
                            <a href="/modelo/gerar-arquivo?id=<?= urlencode($modelo['id_modelo']) ?>"
                                class="btn btn-success btn-sm ms-2">
                                <i class="bi bi-file-earmark-plus"></i> Gerar Arquivo
                            </a>
                        </div>
                        <div class="text-muted small mt-2">Nenhum arquivo importado ainda.</div>
                    </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <div>
                        <strong>Faça download dos arquivos processados</strong>
                    </div>
                    <div>
                        <div class="mt-1">
                            <a href="/modelo/gerar-arquivo?id=<?= urlencode($modelo['id_modelo']) ?>"
                               class="btn btn-success btn-sm text-start"
                               style="min-width: 156px;">
                                <i class="bi bi-file-earmark-plus"></i> Registros corretos
                            </a>
                        </div>
                        <div class="mt-2">
                            <a href="/modelo/gerar-arquivo?id=<?= urlencode($modelo['id_modelo']) ?>"
                               class="btn btn-success btn-sm text-start"
                               style="min-width: 156px;">
                                <i class="bi bi-file-earmark-plus"></i> Registros criticados
                            </a>
                        </div>
                        <div class="mt-2">
                            <a href="/modelo/gerar-arquivo-geral?id=2"
                               class="btn btn-success btn-sm text-start"
                               style="min-width: 156px;">
                                <i class="bi bi-file-earmark-plus"></i> Todos os registros
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr style="border-top: 2px solid #bbb; margin: 0;">
        <div class="table-responsive">
            <div class="p-3">
                <table id="table_colunas" class="table table-striped">
                    <thead>
                        <tr>
                            <?php
                            for($index = 0; $index < count($colunas); $index++):
                            ?>
                                <th>
                                    <select class="form-select form-select-sm select2-col" data-col="<?= $index ?>">
                                        <?php
                                        foreach ($colunas as $index_item => $coluna):
                                        ?>
                                            <option value="<?= $coluna['id_layout_coluna'] ?>" <?= $index_item == $index ? "selected" : "" ?>>
                                                <?= $coluna['nome_layout_coluna'] ?>
                                            </option>
                                        <?php
                                        endforeach;
                                        ?>
                                    </select>
                                </th>
                            <?php
                            endfor;
                            ?>
                        </tr>
                        <tr>
                            <?php
                            foreach ($colunas as $coluna):
                            ?>
                                <th style="padding-left:25px;"><?= $coluna['nome_modelo_coluna'] ?></th>
                            <?php
                            endforeach;
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dadosArquivo as $index => $dado): ?>
                            <tr>
                                <?php foreach ($colunas as $coluna): ?>
                                    <td style="padding-left:25px"><?= $dado[$coluna['nome_layout_coluna']] ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/scripts.php'; ?>
<script>
$(document).ready(function() {
    var table = $('#table_colunas').DataTable({
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json",
            oPaginate: {
                sNext: "<i class='bi bi-chevron-right'></i>",
                sPrevious: "<i class='bi bi-chevron-left'></i>",
                sLast: "<i class='bi bi-chevron-double-right'></i>",
                sFirst: "<i class='bi bi-chevron-double-left'></i>",
            },
            sInfo: "_START_ a _END_ de _TOTAL_ registros",
            sLengthMenu: " Exibindo _MENU_",
            sInfoFiltered: "",
            sEmptyTable: "Nenhum registro encontrado",
            search: "",
        },
        dom: '<"row mb-2"' +
        '<"col-sm-6 text-start"f>' + // Pesquisa à esquerda
        '<"col-sm-6 text-end"l>' +   // Quantidade à direita
        '>' +
        '<"row mb-2"<"col-12 d-flex justify-content-start gap-2" B>>' +
        'rt<"row"<"col-sm-6"i><"col-sm-6"p>>',
        order: [[4, 'asc']],
        responsive: true,
        initComplete: function () {
            // Para cada coluna, preenche o select com valores únicos
            // this.api().columns().every(function () {
            //     var column = this;
            //     var colIdx = column.index();
            //     var select = $('select[data-col="'+colIdx+'"]');
            //     if (select.length) {
            //         var uniqueValues = [];
            //         column.data().unique().sort().each(function (d) {
            //             d = $('<div>').html(d).text(); // Remove HTML
            //             if (uniqueValues.indexOf(d) === -1 && d !== '') {
            //                 uniqueValues.push(d);
            //                 select.append('<option value="'+d+'">'+d+'</option>');
            //             }
            //         });
            //     }
            // });
            // Inicializa Select2 nos selects
            $('.select2-col').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });
        }
    });

    // Filtro ao mudar o select
    $('.select2-col').on('change', function () {
        
    });
});

</script>