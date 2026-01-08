<?php include_once __DIR__ . '/../includes/head.php' ?>

<link rel="stylesheet" href="../views/css/style.css">

<div class="card col-12">
    <div class="card-header">
        Vista
    </div>
    <div class="card-body">
        <div class="d-flex">
            <form action="/vista/store" method="post" enctype="multipart/form-data">
                <input type="file" name="file" id="file" class="form-control mb-2" required>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </form>
        </div>
        <div class="col" id="counts"></div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/scripts.php' ?>
<script rel="stylesheet" src="../views/js/metodos_axios.js?version=<?= $version ?>"></script>
<script rel="stylesheet" src="../views/js/functions.js?version=<?= $version ?>"></script>
<script rel="stylesheet" src="../../views/vista/index.js?version=<?= $version ?>"></script>

<script>
    counts = <?= json_encode($counts) ?>;
</script>