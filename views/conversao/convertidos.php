<?php include_once __DIR__ . '/../includes/head.php' ?>

<style>
    .loader {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: block;
        margin: 0 auto;
        z-index: 9999;
    }
</style>

<div class="card col-12">
    <div class="card-header">Convertidos</div>
    <div class="card-body">
        <table class="table table-striped display" id="table_convertidos"></table>
    </div>
</div>
<img id="load" style="display:none" class="loader" src="../../views/assets/imgload.svg" alt="">

<?php include_once __DIR__ . '/../includes/scripts.php' ?>
<script rel="stylesheet" src="../../app/core/js/functions.js?$version ?>"></script>
<script rel="stylesheet" src="../../views/js/metodos_axios.js?version=<?= $version ?>"></script>
<script rel="stylesheet" src="../../views/js/functions.js?version=<?= $version ?>"></script>
<script rel="stylesheet" src="../../views/conversao/conversao_index.js?version=<?= $version ?>"></script>
<script>
    var convertidos = <?= json_encode($convertidos) ?>;


    // $(document).ready(function() {
    // if ($.fn.DataTable.isDataTable('#table_convertidos'))
    //     $('#table_convertidos').DataTable().clear().destroy();

    $('#table_convertidos').DataTable({
        order: [
            [0, 'desc']
        ],
        responsive: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
        },
        data: convertidos,
        columns: [{
                title: '',
                data: 'id_modelo',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<a class="btn btn-primary btn-sm" href="/conversao/index?id_modelo=' + data + '&id_arquivo=' + row.id_arquivo + '"><i class="bi bi-pencil"></i></a>';
                }
            }, {
                title: 'Nome arquivo',
                data: 'nome_arquivo'
            },
            {
                title: 'Modelo',
                data: 'nome_modelo'
            },
            {
                title: 'Layout',
                data: 'nome_layout'
            },
            {
                title: 'Tipo de Arquivo',
                data: 'descr_tipo_arquivo'
            },
            {
                title: 'Status',
                data: 'status'
            }
        ]
    });
    // });
</script>