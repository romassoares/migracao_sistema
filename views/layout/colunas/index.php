<?php include_once __DIR__ . '/../../includes/head.php' ?>

<div class="card col-10">
    <div class="card-header">
        Atualização de Layouts
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-end">
            <button class="btn btn-primary">NOVO</button>
        </div>
        <div class="d-flex">
            <div class=" card col-4 p-2">
                <form id="id_form" action="layout/update" method="post">
                    <input type="hidden" name="id" id="id">
                    <div class="d-flex">
                        <input type="text" id="nome" name="nome" class="form-control">
                        <button type="submit" class="btn btn-primary">salvar</button>
                    </div>
                </form>
            </div>

            <div class="card col-8 mx-2 p-2">
                <table id="table_layout">
                    <thead>
                        <tr>
                            <th>Nome Coluna</th>
                            <th>Tipo</th>
                            <th>Obrigatório</th>
                            <th>Posição</th>
                            <th>Ações</th>
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
    // function setFieldsForUpdate(id, id_layout) {
    //     window.scrollTo(0, 0)
    //     document.querySelector("#id").value = id
    //     document.querySelector("#nome").value = value

    //     var el_form = document.querySelector('#id_form');
    //     var current_action = el_form.getAttribute('action');
    //     var new_action = current_action.replace('store', 'update');
    //     el_form.setAttribute('action', new_action);
    // }
</script>

<!-- '' -->