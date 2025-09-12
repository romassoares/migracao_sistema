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
    <div class="card-header">Conversão</div>
    <div class="card-body">
        <div class="d-flex">
            <div class="card col-md-6 col-sm-12 p-2 gap-2">
                <form id="id_form" action="conversao/store" method="post">
                    <div class="col">
                        <label for="concorrente_id">Concorrente</label>
                        <select class="form-control form-control-sm" name="concorrente_id" id="concorrente_id">
                            <option value="">Selecione ...</option>
                            <?php foreach ($concorrentes as $concorrente) { ?>
                                <option <?= (isset($modelo->id_concorrente) && $modelo->id_concorrente == $concorrente['id']) ? 'selected' : '' ?> value="<?php echo $concorrente['id'] ?>"><?php echo $concorrente['nome'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-12 mt-2">
                        <label for="modelo_id">Modelo</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="tipoArquivoInputGroup">?</span>
                            </div>
                            <select class="custom-select" name="modelo_id" id="modelo_id">
                            </select>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" id="btn_abrir_modal_modelo" type="button" onclick="abrirModalFormModelo()">Criar Modelo</button>
                            </div>
                        </div>
                        <span id="tipo_arquivo_ref"></span>
                    </div>
                </form>
                <div class="col-12 d-none" id="div_upload_arquivo">
                    <form method="post" id="id_form_upload_arquivo" enctype="multipart/form-data">
                        <div class="col-12">
                            <input type="file" name="arquivo" id="arquivo" class="form-control form-control-sm">
                        </div>
                        <?php if (empty($modelo->id_modelo)) { ?>
                            <div class="col mt-2">
                                <button type="submit" class="btn btn-success btn-sm">
                                    carregar arquivo
                                </button>
                            </div>
                        <?php } ?>
                    </form>
                </div>

                <div class="d-none" id="btn_processa_arquivo">
                    <button type="button" onclick="processaArquivo()" class="btn btn-primary btn-sm d-flex">Processa arquivo</button>
                </div>

            </div>
            <div class="d-none" id="div_btn_processados_arquivo">
                <div class="col-6 d-flex flex-column gap-2">
                    <p>Faça download dos arquivos processados:</p>
                    <button class="btn btn-secondary btn-sm">Corretor</button>
                    <button class="btn btn-secondary btn-sm">Criticados</button>
                    <button class="btn btn-secondary btn-sm">Todos</button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 word-wrap overflow-auto mt-4 m-2" id="div_arquivo_convertido">
    </div>
    <div class="col-12 overflow-auto mt-2">
        <table class="table table-striped">
            <tbody id="tbody_values_convertidos">

            </tbody>
        </table>
    </div>
</div>
</div>
<img id="load" style="display:none" class="loader" src="../../views/assets/imgload.svg" alt="">
<!--  -->
<div class="modal fade" id="modal_novo_modelo">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class=""><b>Novo Modelo</b></h6>
                <span class="btn-close" id="btn_modal_close" data-bs-dismiss="modal" aria-label="Close"></span>
            </div>
            <form method="post" id="id_form_modelo">
                <div class="modal-body">
                    <div class="d-flex justify-content-center">
                        <div class="m-2 order-1" id="div_btn_form_inserUpda">
                            <button type="submit" class="btn btn-success btn-sm" id="btn_submit">Inserir</button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-around align-items-center mt-3 gap-2">
                        <label for="layout_id" class="font_blue">Layout</label>
                        <select class="form-control form-control-sm" name="layout_id" id="layout_id">
                            <option value="">Selecione ...</option>
                            <?php foreach ($layouts as $layout) { ?>
                                <option value="<?php echo $layout['id'] ?>"><?php echo $layout['nome'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-around align-items-center mt-3 gap-2">
                        <label for="id_tipo_arquivo" class="font_blue">Tipo do arquivo</label>
                        <select class="form-control form-control-sm" name="id_tipo_arquivo" id="id_tipo_arquivo">
                            <option value="">Selecione ...</option>
                            <?php foreach ($tipos_arquivo as $tipo) { ?>
                                <option value="<?php echo $tipo['id_tipo_arquivo'] ?>"><?php echo $tipo['descr_tipo_arquivo'] ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="d-flex justify-content-around align-items-center mt-3 gap-2">
                        <label for="nome_modelo_modal" class="font_blue">Nome</label>
                        <input type="text" name="nome_modelo_modal" id="nome_modelo_modal" class="form-control form-control-sm">
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
<!--  -->

<?php include_once __DIR__ . '/../includes/scripts.php' ?>
<script rel="stylesheet" src="../../app/core/js/functions.js?$version ?>"></script>
<script rel="stylesheet" src="../../views/js/metodos_axios.js?version=<?= $version ?>"></script>
<script rel="stylesheet" src="../../views/js/functions.js?version=<?= $version ?>"></script>
<script rel="stylesheet" src="../../views/conversao/conversao_index.js?version=<?= $version ?>"></script>
<script>
    var modelo = <?= json_encode($modelo) ?>;
    var modelos_colunas = <?= json_encode($modelos_colunas) ?>;
</script>