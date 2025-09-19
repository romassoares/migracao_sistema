<?php include_once __DIR__ . '/../includes/head.php' ?>

<div class="card col-12">
    <div class="card-header">
        Concorrente
    </div>
    <div class="card-body">
        <form id="id_form" action="/concorrente/store" method="post">
            <input type="hidden" name="id" id="id">
            <div class="d-flex">
                <input type="text" id="nome" name="nome" class="form-control form-control-sm">
                <button type="submit" class="btn btn-primary btn-sm">salvar</button>
            </div>
        </form>
        <div class=" mt-3">
            <table class="table table-striped display" id="table_layout"></table>

        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/scripts.php' ?>
<script>
    var concorrentes = <?= json_encode($concorrentes) ?>;

    $('#table_layout').DataTable({
        order: [
            [1, 'asc']
        ],
        responsive: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
        },
        data: concorrentes,
        columns: [{
            title: '',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                return `<button class="btn btn-primary btn-sm"
                            onclick="setFieldsForUpdate(${row.id}, '${String(row.nome).replace(/'/g, "\\'")}')">
                            <i class="bi bi-pencil"></i>
                        </button>`;
            }
        }, {
            title: 'Nome ',
            data: 'nome'
        }]
    });

    function setFieldsForUpdate(id, value) {
        window.scrollTo(0, 0)
        document.querySelector("#id").value = id
        document.querySelector("#nome").value = value

        var el_form = document.querySelector('#id_form');
        var current_action = el_form.getAttribute('action');
        var new_action = current_action.replace('store', 'update');
        el_form.setAttribute('action', new_action);
    }
</script>