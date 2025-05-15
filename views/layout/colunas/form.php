<?php include_once __DIR__ . '/../../includes/head.php' ?>

<div class="card col-10">
    <div class="card-header">
        Atualização de Layouts
    </div>
    <div class="card-body">
        <div class="col-12">
            <div class="d-flex justify-content-end">
                <a href="layout_colunas/index?id=<?php echo $layout_coluna->id_layout ?>" class="btn btn-primary">Voltar</a>
            </div>
        </div>
        <div class="col-12">
            <div class="col card p-2">
                <form id="id_form" action="layout_colunas/update" method="post">
                    <input type="hidden" name="id_layout" id="id_layout">
                    <input type="hidden" name="id_layout_coluna" id="id_layout_coluna">
                    <div class="d-flex">
                        <div class="col">
                            <label for="nome_exibicao">Nome da Coluna</label>
                            <input type="text" id="nome_exibicao" name="nome_exibicao" class="form-control" value="<?php echo $layout_coluna->nome_exibicao ?>">
                        </div>
                        <div class="col">
                            <label for="tipo">Tipo</label>
                            <input type="text" id="tipo" name="tipo" class="form-control" value="<?php echo $layout_coluna->tipo ?>">
                        </div>
                        <div class="col">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php ($layout_coluna->ativo == "1") ? "checked" : '' ?>>
                                <label class="form-check-label" for="ativo">Ativo</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="obrigatorio" name="obrigatorio" <?php ($layout_coluna->obrigatorio == "1") ? "checked" : '' ?>>
                                <label class="form-check-label" for="obrigatorio">obrigatorio</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">salvar</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Conteudo
                </div>
                <div class="card-body">
                    <?php if ($layout_coluna->tipo == 'flag') {
                    ?>
                        <?php foreach ($layout_coluna->flags as $flag) {
                            $flag = (array) $flag; ?>
                            <div class="card">
                                <div class="col">
                                    <label for="">Conteudo</label>
                                    <input type="text" id="Conteudo_para_livre" name="Conteudo_para_livre" class="form-control" value="<?php echo strval($layout_coluna['Conteudo_para_livre']) ?>">
                                </div>
                                <div class="col">
                                    <label for="">Descrição</label>
                                    <input type="text" id="conteudo_de" name="conteudo_de" class="form-control" value="<?php echo $layout_coluna['conteudo_de'] ?>">
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
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