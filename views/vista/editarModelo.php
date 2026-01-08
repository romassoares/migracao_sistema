<?php include_once __DIR__ . '/../includes/head.php' ?>

<link rel="stylesheet" href="../views/css/style.css">

<div class="card col-12">
    <div class="card-header d-flex justify-content-between">
        <span>Edita modelo - <?= isset($_GET['tabela']) ? htmlspecialchars($_GET['tabela']) : '' ?></span>
    </div>
    <div class="card-body">
        <div class="row">
            <span>
                <a href="vista/index" class="btn btn-secondary">Voltar</a>
            </span>
        </div>
        <div class="row">
            <?php
            var_dump($modeloColunas, $dadosDB);
            ?>
        </div>

    </div>
</div>

<?php include_once __DIR__ . '/../includes/scripts.php' ?>
<script rel="stylesheet" src="../views/js/metodos_axios.js?version=<?= $version ?>"></script>
<script rel="stylesheet" src="../views/js/functions.js?version=<?= $version ?>"></script>
<script rel="stylesheet" src="../../views/vista/index.js?version=<?= $version ?>"></script>

<script>

</script>