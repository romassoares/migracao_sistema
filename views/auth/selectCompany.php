<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ckeck_company();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>tests - login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2-bootstrap-theme/0.1.0-beta.10/select2-bootstrap.css" integrity="sha512-CbQfNVBSMAYmnzP3IC+mZZmYMP2HUnVkV4+PwuhpiMUmITtSpS7Prr3fNncV1RBOnWxzz4pYQ5EAGG4ck46Oig==" crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>

<body class="d-flex justify-content-center align-items-center bg-dark">
    <div class="card col-6 p-5">
        <h1>Empresas</h1>
        <div>
            <form method="post">
                <div class="col-12 mt-4">
                    <!-- <div class="input-group"> -->
                    <!-- <div class="input-group-prepend"> -->
                    <label for="inputGroupSelect01">Clientes</label>
                    <!-- </div> -->
                    <select class="form-control select2-single" id="inputGroupSelect01">
                        <option value="1">One</option>
                        <option value="2">Two</option>
                        <option value="3">Three</option>
                    </select>
                    <!-- </div> -->
                </div>
                <div class="d-flex mt-4 justify-content-between">
                    <div class="order-2">
                        <button type="submit" class="btn btn-primary">Selecionar</button>
                    </div>
            </form>
            <div class="order-1">
                <button class="btn btn-success">Criar cliente</button>
            </div>
        </div>
    </div>
    </div>
</body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/v/dt/dt-1.13.6/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script rel="stylesheet" src="views/js/metodos_axios.js?version=<?= $version ?>"></script>

<script>
    $(document).ready(function() {
        $('.select2-single').select2({
            theme: "bootstrap",
            placeholder: "Search"
        });
    });
</script>

</html>