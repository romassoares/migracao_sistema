<?php include_once __DIR__ . '/../includes/head.php' ?>

<div class="card col-10">
    <div class="card-header">
        Layouts
    </div>
    <div class="card-body">
        <div class="d-flex">
            <div class="card col-12 mx-2 p-2">
                <table id="table_layout" class="table table-striped">
                    <thead>
                        <tr>
                            <th data-orderable='false'>#</th>
                            <th>Nome</th>
                            <th data-orderable='false'>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($layouts as $layout) { ?>
                            <tr>
                                <td><?php echo $layout['id'] ?></td>
                                <td><?php echo $layout['nome'] ?></td>
                                <td>

                                    <div class="d-flex">
                                        <a href="layout_colunas/index?id=<?php echo $layout['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
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

<div class="modal fade" id="modal_form">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class=""><b>Novo Layout</b></h6>
                <span class="btn-close" id="btn_modal_close" data-bs-dismiss="modal" aria-label="Close"></span>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center">
                    <form action="layout/store" method="post" id="id_form">
                        <div class="m-2 order-1" id="div_btn_form_inserUpda">
                            <button type="submit" class="btn btn-success btn-sm" id="btn_submit">Inserir</button>
                        </div>
                </div>
                <div class="d-flex justify-content-around align-items-center mt-3">
                    <h6 for="nome" class="font_blue">Nome</h6>
                    <input type="text" name="nome" id="nome" class="form-control mx-2 form-control-sm">
                </div>
                </form>
            </div>
            <div class="body-footer">
                <div class="d-flex justify-content-center">
                    <strong id="footer-form-inserUpdat" class="text-danger"></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/scripts.php' ?>
<script>
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
            sEmptyTable: "Nenhum registro encontrado",
            search: "",
        },
        initComplete: function() {
            $(".custom-container").prepend(
                `<div class="cust_contai_cust">
            <button onclick="$('#modal_form').modal('show')" class="btn btn-success btn-sm"><i class="bi bi-plus"></i> Layout</button>
            </div>`);
        },
        dom: '<"top"<"float-start"f> <"float-end"l><"custom-container">>t<"bottom"ip>',
        responsive: true,
        order: [],
    });
</script>

<!-- '' -->