<?php include_once __DIR__ . '/../includes/head.php' ?>

<div class="card">
    <div class="card-header">
        Layouts
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-end">
            <button class="btn btn-primary">NOVO</button>
        </div>
        <div class="d-flex">
            <div class="col-4">

                <form method="post">
                    <input type="text" id="nome">
                    <button type="submit">salvar</button>
                </form>
            </div>

            <div class="col">
                <table id="table_layout">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($layouts as $layout) { ?>
                            <tr>
                                <td><?php echo $layout['id'] ?></td>
                                <td><?php echo $layout['nome'] ?></td>
                                <td>
                                    <div class="d-flex">
                                        <button>asdf</button>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/scripts.php' ?>
<script>
    var layouts = '<?php $layouts ?>'

    function montaDataPrinc() {

        if ($.fn.DataTable.isDataTable('#table_layout'))
            $('#table_layout').DataTable().clear().destroy();

        $('#table_layout').DataTable({
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
                sInfoPostFix: "",
                sSearch: "",
                sEmptyTable: "Nenhum registro encontrado",
            },
            dom: '<"top">t<"bottom"p>',
            initComplete: function() {
                // $("#overlay").css("display", "none");
            },
            pagingType: "full_numbers",
            responsive: false,
            lengthMenu: [
                [15, 30, 100, -1],
                [15, 30, 100, 'All']
            ],
            order: [],
            data: layouts,
            columns: [{
                    data: 'id'
                },
                {
                    data: 'nome'
                },
                {
                    data: null,
                    render: function(data) {
                        return "<div class='d-flex'><a title='Editar' style='cursor:pointer; width:100%' onClick='editarItem(" + data.id + ")'><i class='bi bi-pencil-fill'></i></a><a title='visualizarLayout' style='cursor:pointer; width:100%' onClick='visualizarLayout(" + data.id + ")'><i class='bi bi-pencil-fill'></i></a><a title='inativarLayout' style='cursor:pointer; width:100%' onClick='inativarLayout(" + data.id + ")'><i class='bi bi-pencil-fill'></i></a></div>";
                    }
                }
            ]
        });
    }
</script>

<!-- '' -->