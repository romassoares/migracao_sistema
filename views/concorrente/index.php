<?php include_once __DIR__ . '/../includes/head.php' ?>

<div class="card col-10">
    <div class="card-header">
        Concorrente
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-end">
            <button class="btn btn-primary btn-sm">NOVO</button>
        </div>
        <div class="d-flex mt-3">
            <div class=" card col-4 p-2">
                <form id="id_form" action="concorrente/store" method="post">
                    <input type="hidden" name="id" id="id">
                    <div class="d-flex">
                        <input type="text" id="nome" name="nome" class="form-control form-control-sm">
                        <button type="submit" class="btn btn-primary btn-sm">salvar</button>
                    </div>
                </form>
            </div>

            <div class="card col-8 mx-2 p-2">
                <table id="table_layout">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($concorrentes as $concorrente) { ?>
                            <tr>
                                <td><?php echo $concorrente['id'] ?></td>
                                <td><?php echo $concorrente['nome'] ?></td>
                                <td>
                                    <div class="d-flex">
                                        <button onclick="setFieldsForUpdate('<?php echo $concorrente['id'] ?>','<?php echo $concorrente['nome'] ?>')" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></button>
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

<!-- '' -->