<?php include_once __DIR__ . '/../../includes/head.php' ?>

<div class="card col-10">
    <div class="card-header">
        Atualização de Layouts
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <div class="col">
                <form id="id_form" action="layout/store" method="post">
                    <input type="hidden" name="id" id="id">
                    <div class="d-flex">
                        <input type="text" id="nome" name="nome" class="form-control" value="<?php echo $layout->nome ?>">
                        <button type="submit" class="btn btn-primary">salvar</button>
                    </div>
                </form>
            </div>
            <div class="col">
                <a href="layout/index" class="btn btn-primary">Voltar</a>
            </div>
        </div>
        <div class="d-flex">
            <div class="card col-12 mx-2 p-2">
                <!-- <table id="table_layout"> -->
                <table id="table_layout_colunas" class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nome Coluna</th>
                            <th>Tipo</th>
                            <th data-orderable='false'>Obrigatório</th>
                            <th data-orderable='false'>Posição</th>
                            <th data-orderable='false'>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($layout_colunas as $colunas) { ?>
                            <tr>
                                <td><?php echo $colunas['nome_exibicao'] ?></td>
                                <td><?php echo $colunas['tipo'] ?></td>
                                <td><?php echo $colunas['obrigatorio'] ?></td>
                                <td><?php echo $colunas['posicao'] ?></td>
                                <td>
                                    <div class="d-flex">
                                        <button onclick="setFieldsForUpdate('<?php echo $layout->id ?>','<?php echo $layout->nome ?>')" class="btn btn-primary"><i class="bi bi-pencil"></i></button>
                                    </div>
                                    <div class="d-flex">
                                        <a href="layout_colunas/edit?id_layout=<?php echo $layout->id ?>&id_layout_coluna=<?php echo $colunas['id'] ?>" class="btn btn-primary"><i class="bi bi-pencil"></i></a>
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

<?php include_once __DIR__ . '/../../includes/scripts.php' ?>
<script>
    function setFieldsForUpdate(id, value) {
        window.scrollTo(0, 0)
        document.querySelector("#id").value = id
        document.querySelector("#nome").value = value

        var el_form = document.querySelector('#id_form');
        var current_action = el_form.getAttribute('action');
        var new_action = current_action.replace('store', 'update');
        el_form.setAttribute('action', new_action);
    }

    $('#table_layout_colunas').DataTable({
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
        initComplete: function() {
            $(".custom-container").prepend(
                `<div class="cust_contai_cust">
            <button class="btn btn-primary">NOVO</button>
            </div>`);
        },
        dom: '<"top"<"float-start"f> <"float-end"l><"custom-container">>t<"bottom"ip>',
        responsive: true,
        order: [],
    });
</script>

<!-- '' -->