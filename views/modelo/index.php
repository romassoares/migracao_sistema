<?php include_once __DIR__ . '/../includes/head.php' ?>

    <div class="card col-12">
        <div class="card-header">
            Modelos
        </div>
        <div class="card-body">
            <table id="table_modelos" class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nome</th>
                        <th>Layout</th>
                        <th>Concorrente</th>
                        <th>Tipo de arquivo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modelos as $modelo): ?>
                        <tr>
                            <td><?php echo $modelo['id_modelo'] ?></td>
                            <td><?php echo $modelo['nome_modelo'] ?></td>
                            <td><?php echo $modelo['layout'] ?></td>
                            <td><?php echo $modelo['concorrente'] ?></td>
                            <td><?php echo $modelo['tipo_arquivo'] ?></td>
                            <td>
                                <div class="d-flex">
                                    <a href="/modelo/detalhar?id=<?php echo $modelo['id_modelo'] ?>" class="btn btn-primary btn-sm" title="Detalhar"><i class="bi bi-diagram-3"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php include_once __DIR__ . '/../includes/scripts.php' ?>
<script>
$(document).ready(function() {
    $('#table_modelos').DataTable({
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
        columnDefs: [
            { orderable: false, targets: [5] }
        ],
        dom: '<"row mb-2"' +
                '<"col-sm-6 text-start"f>' + // Pesquisa à esquerda
                '<"col-sm-6 text-end"l>' +   // Quantidade à direita
             '>' +
             '<"row mb-2"<"col-12 d-flex justify-content-start gap-2" B>>' +
             'rt<"row"<"col-sm-6"i><"col-sm-6"p>>',
        buttons: [
            {
                text: '<i class="bi bi-plus"></i> Novo modelo',
                className: 'btn btn-success btn-sm rounded me-2',
                action: function () { 
                    window.location.href = '/modelo/create';
                 }
            },
        ],
        responsive: true,
        order: [1, 'asc'],
    });
});
</script>