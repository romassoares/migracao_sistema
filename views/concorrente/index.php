<?php include_once __DIR__ . '/../includes/head.php' ?>

<div class="card">
    <div class="card-header">
        Concorrente
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
                <table id="table_concorrente">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($concorrentes)) { ?>
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
                        <?php } else { ?>
                            <tr>
                                <td>
                                    Nenhum concorrente cadastrado
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

</script>

<!-- '' -->