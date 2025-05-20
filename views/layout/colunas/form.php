<?php include_once __DIR__ . '/../../includes/head.php' ?>

<div class="card col-10">
    <div class="card-header">
        Atualização de Layouts
    </div>
    <div class="card-body">
        <div class="col-12">
            <div class="d-flex justify-content-end">
                <a href="layout_colunas/index?id=<?php echo $layout_coluna->id_layout ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
            </div>
        </div>
        <form id="" action="layout_colunas/update" method="post">
            <div class="col-12 mt-2">
                <div class="col card p-2">
                    <input type="hidden" name="id_layout" id="id_layout" value="<?= $layout_coluna->id_layout ?>">
                    <input type="hidden" name="id_layout_coluna" id="id_layout_coluna" value="<?= $layout_coluna->id ?>">
                    <div class="d-flex gap-2">
                        <div class="col">
                            <label for="nome_exibicao">Nome da Coluna</label>
                            <input type="text" id="nome_exibicao" name="nome_exibicao" class="form-control form-control-sm" value="<?php echo $layout_coluna->nome_exibicao ?>">
                        </div>
                        <div class="col">
                            <label for="tipo">Tipo</label>
                            <select name="tipo" id="tipo" class="form-control form-control-sm">
                                <option <?php echo strtolower($layout_coluna->tipo) == 'livre' ? "selected" : '' ?> value="livre">Livre</option>

                                <option <?php echo strtolower($layout_coluna->tipo) == 'email' ? "selected" : '' ?> value="email">Email</option>

                                <option <?php echo strtolower($layout_coluna->tipo) == 'telefone' ? "selected" : '' ?> value="telefone">Telefone</option>

                                <option <?php echo strtolower($layout_coluna->tipo) == 'data' ? "selected" : '' ?> value="data">Data</option>

                                <option <?php echo strtolower($layout_coluna->tipo) == 'numerico' ? "selected" : '' ?> value="numerico">Numérico</option>

                                <option <?php echo strtolower($layout_coluna->tipo) == 'flag' ? "selected" : '' ?> value="flag">Flag</option>
                            </select>
                        </div>
                        <div class="col d-flex align-items-center flex-column justify-content-center">
                            <div class="form-check form-switch col-6">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php echo ($layout_coluna->ativo == "1") ? "checked" : '' ?>>
                                <label class="form-check-label" for="ativo">Ativo</label>
                            </div>
                            <div class="form-check form-switch col-6">
                                <input class="form-check-input" type="checkbox" id="obrigatorio" name="obrigatorio" <?php echo ($layout_coluna->obrigatorio == "1") ? "checked" : '' ?>>
                                <label class="form-check-label" for="obrigatorio">obrigatorio</label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="col-12 mt-2">
                <div class="card" id="card_conteudo">
                    <div class="card-header d-flex justify-content-between">
                        <div class="col">
                            Conteudo
                        </div>
                        <div class="col justify-content-end" id="btns_add_remove_conteudo">
                            <div class="col">
                                <a onclick="adicionarConteudo()" class="btn btn-secondary btn-sm"><i class="bi bi-plus"></i> Add Conteudo</a>
                            </div>
                            <div class="col">
                                <a class="btn btn-danger btn-sm dropdown dropdown-toggle" id="dropdownMenuButtonDestroyLayoutConteudos" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="bi bi-trash"></i> Deletar Todos
                                </a>
                                <!--  -->
                                <!-- dropdownMenuButtonDestroy -->
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonDestroyLayoutConteudos">
                                    <div class="col-sm-12 text-center">
                                        <p><strong>Deseja realmente excluir este item?</strong></p>
                                    </div>
                                    <hr>
                                    <div class="col-sm-12 text-center">
                                        <a href="layout_colunas/deleteConteudosColuna?id_layout_coluna=<?= $layout_coluna->id ?>&id_layout=<?= $layout_coluna->id_layout ?>" class='btn btn-success btn-sm'>Sim</a>
                                        <a class="btn btn-danger btn-sm">Não</a>
                                    </div>
                                </div>
                                <!--  -->
                            </div>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow:scroll" id="body-conteudo">
                        <?php if ($layout_coluna->tipo == 'flag') {
                        ?>
                            <?php
                            if ($layout_coluna->flags && count($layout_coluna->flags) > 0) {
                                for ($i = 0; count($layout_coluna->flags) > $i; $i++) {
                            ?>
                                    <input type="hidden" name="conteudo[colunas_conteudo_id][]" value="<?= $layout_coluna->flags[$i]['colunas_conteudo_id'] ?>">
                                    <div class="card my-3 p-2" id="row_<?= $i ?>">
                                        <div class="d-flex gap-2">
                                            <div class="col">
                                                <label for="">Conteudo</label>
                                                <input type="text" id="conteudo" name="conteudo[nome][]" class="form-control form-control-sm" value="<?php echo strval($layout_coluna->flags[$i]['conteudo']) ?>">
                                            </div>
                                            <div class="col">
                                                <label for="">Descrição</label>
                                                <input type="text" id="descricao" name="conteudo[descricao][]" class="form-control form-control-sm" value="<?php echo $layout_coluna->flags[$i]['descricao'] ?>">
                                            </div>
                                            <div class="col-1 d-flex align-items-center">
                                                <button onclick="removerConteudo(<?= $i ?>)" class="btn btn-danger btn-sm"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                }
                            } ?>
                        <?php } ?>
                    </div>
                </div>

            </div>
            <div class="col-12 mt-3">
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-primary btn-sm">Salvar</button>
                </div>
            </div>
        </form>
    </div>
</div>



<?php include_once __DIR__ . '/../../includes/scripts.php' ?>
<script>
    $(document).ready(function() {
        verificaTipo(document.querySelector("#tipo"))
    });

    document.querySelector("#tipo").addEventListener('change', function() {
        verificaTipo(this)
    })

    function verificaTipo(el) {
        habilitaDesabilita = false
        if (el.value !== 'flag')
            habilitaDesabilita = true

        document.querySelector("#btns_add_remove_conteudo").className = habilitaDesabilita == true ? 'd-none' : 'd-flex gap-2'
        console.log(habilitaDesabilita)

        var container = document.querySelector('#card_conteudo')
        const elementsToDisable = container.querySelectorAll('input, select, textarea, button, fieldset');

        elementsToDisable.forEach((element) => {
            element.disabled = habilitaDesabilita;
        });
    }

    function adicionarConteudo() {
        var el_body = document.querySelector('#body-conteudo')

        var el_div = document.createElement('div')
        el_div.className = "card my-3 p-2"

        // ===================
        var count_body_conteudo = el_body ? el_body.children.length : 0;
        el_div.id = 'row_' + count_body_conteudo


        // ===================
        var el_div_row = document.createElement('div')
        el_div_row.className = "d-flex gap-2"

        // conteudo
        var el_div_conteudo = document.createElement('div')
        el_div_conteudo.className = "col"

        var el_label_conteudo = document.createElement('label')
        el_label_conteudo.textContent = "Conteudo"

        var el_input_conteudo = document.createElement("input")
        el_input_conteudo.id = 'conteudo'
        el_input_conteudo.name = 'conteudoNew[nome][]'
        el_input_conteudo.className = 'form-control form-control-sm'

        el_div_conteudo.appendChild(el_label_conteudo)
        el_div_conteudo.appendChild(el_input_conteudo)

        el_div_row.appendChild(el_div_conteudo)


        // descricao
        var el_div_descricao = document.createElement('div')
        el_div_descricao.className = "col"

        var el_label_descricao = document.createElement('label')
        el_label_descricao.textContent = "Descrição"

        var el_input_descricao = document.createElement("input")
        el_input_descricao.id = 'descricao'
        el_input_descricao.name = 'conteudoNew[descricao][]'
        el_input_descricao.className = 'form-control form-control-sm'

        el_div_descricao.appendChild(el_label_descricao)
        el_div_descricao.appendChild(el_input_descricao)

        el_div_row.appendChild(el_div_descricao)


        // ======================
        var el_div_btn_trash = document.createElement('div')
        el_div_btn_trash.className = "col-1 d-flex align-items-center"

        var el_input_button = document.createElement("button")
        el_input_button.className = 'btn btn-danger btn-sm'

        el_input_button.onclick = () => {
            removerConteudo(count_body_conteudo)
        }

        var el_i = document.createElement("i")
        el_i.className = 'bi bi-trash'

        el_input_button.appendChild(el_i)

        el_div_btn_trash.appendChild(el_input_button)

        el_div_row.appendChild(el_div_btn_trash)

        // ======================
        el_div.appendChild(el_div_row)
        el_body.appendChild(el_div)
    }

    function removerConteudo(posicao) {
        var element = document.querySelector("#row_" + posicao)
        if (element.parentNode) {
            element.parentNode.removeChild(element);
        }
    }
</script>

<!-- '' -->