<?php include_once __DIR__ . '/../includes/head.php' ?>

<div class="card col-12">
    <div class="card-header">
        Conversão
    </div>
    <div class="card-body">
        <!-- <div class="d-flex justify-content-end">
            <button class="btn btn-primary btn-sm">NOVO</button>
        </div> -->
        <div class="d-flex mt-3">
            <div class=" card col-4 p-2 gap-2">
                <form id="id_form" action="conversao/store" method="post">
                    <!-- <input type="hidden" name="id" id="id"> -->
                    <div class="col">
                        <label for="concorrente_id">Concorrente</label>
                        <select class="form-control form-control-sm" name="concorrente_id" id="concorrente_id">
                            <option value="">Selecione ...</option>
                            <?php foreach ($concorrentes as $concorrente) { ?>
                                <option value="<?php echo $concorrente['id'] ?>"><?php echo $concorrente['nome'] ?></option>
                            <?php } ?>
                        </select>

                    </div>
                    <div class="col-12 mt-2">
                        <label for="modelo_id">Modelo</label>
                        <div class="input-group input-group-sm">
                            <select class="custom-select" name="modelo_id" id="modelo_id">
                            </select>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" id="btn_abrir_modal_modelo" type="button" onclick="abrirModalFormModelo()">Criar Modelo</button>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="col mt-2">
                        <button type="submit" id="btn_abrir_modal_modelo" class="btn btn-primary btn-sm">salvar</button>
                    </div> -->
                </form>
                <div class="col-12 d-none" id="div_upload_arquivo">
                    <form method="post" id="id_form_upload_arquivo" enctype="multipart/form-data">
                        <div class="col-12">
                            <input type="file" name="arquivo" id="arquivo" class="form-control form-control-sm">
                        </div>
                        <div class="col mt-2">
                            <button type="submit" class="btn btn-success btn-sm">carregar arquivo</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- <input type="hidden" name="layout_id"> -->
            <div class="card col-8 mx-2 p-2">
                <table id="table_converted">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-12" id="div_arquivo_convertido">

        </div>
    </div>
</div>

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
<script>
    // function setFieldsForUpdate(id, value) {
    //     window.scrollTo(0, 0)
    //     document.querySelector("#id").value = id
    //     document.querySelector("#nome").value = value

    //     var el_form = document.querySelector('#id_form');
    //     var current_action = el_form.getAttribute('action');
    //     var new_action = current_action.replace('store', 'update');
    //     el_form.setAttribute('action', new_action);
    // }

    function abrirModalFormModelo() {
        var value_id_concorrente = document.querySelector("#concorrente_id").value
        var errors = ''
        if (value_id_concorrente == '')
            errors += 'Selecione um Concorrente antes'

        if (errors !== '') {
            alert(errors)
            return
        }

        mudaNomeModelo('')

        $('#modal_novo_modelo').modal('show')
    }

    document.querySelector('#layout_id').addEventListener('change', function(e) {
        e.preventDefault()
        const layoutId = this.value;
        const layoutText = this.options[this.selectedIndex].text;

        mudaNomeModelo(layoutText);
    })

    function mudaNomeModelo(layout_text) {
        var el_concorrente_id = document.querySelector("#concorrente_id")

        var index = el_concorrente_id.selectedIndex
        var text_concor = el_concorrente_id.options[index].text

        var el_nome_modelo = document.querySelector("#nome_modelo_modal")

        var new_nome = ''

        new_nome += text_concor + '-'
        new_nome += layout_text

        el_nome_modelo.value = new_nome
    }

    document.querySelector("#modelo_id").addEventListener("change", function(e) {
        e.preventDefault()
        habilitaDesabilitaModelo(true)
    })

    function habilitaDesabilitaModelo(action) {
        document.querySelector("#modelo_id").disabled = action
        document.querySelector("#btn_abrir_modal_modelo").disabled = action

        classNameDivUpload = 'd-none'
        if (action == true) {
            classNameDivUpload = 'd-flex'
        }
        // console.log(classNameDivUpload)
        document.querySelector("#div_upload_arquivo").className = classNameDivUpload
    }

    var class_input_form_control = ' form-control '
    document.querySelector("#concorrente_id").addEventListener('change', async function(e) {
        e.preventDefault()
        habilitaDesabilitaModelo(false)
        try {
            var resultado = await method_post('/modelos/getModelos', {
                id_concorrente: this.value
            })

            var modelos = Object.values(resultado.data.modelos)
            // console.log(modelos.length)
            if (modelos.length > 0) {
                document.querySelector('#modelo_id').className = 'd-flex' + class_input_form_control
                montaSelect('#modelo_id', 'id_modelo', 'nome_modelo', modelos)
            } else {
                document.querySelector('#modelo_id').className = 'd-none'
                abrirModalFormModelo()
            }
        } catch (error) {
            alert(error)
        }
    })

    document.querySelector("#id_form_modelo").addEventListener("submit", async function(e) {
        e.preventDefault()

        var value_input_nome = document.querySelector("#nome_modelo_modal").value
        var value_input_layout_id = document.querySelector("#layout_id").value
        var value_id_concorrente = document.querySelector("#concorrente_id").value
        var id_tipo_arquivo = document.querySelector("#id_tipo_arquivo").value
        var errors = ''

        if (value_input_nome == '') {
            errors += 'campo Nome do modelo é obrigatório\n'
        }

        if (value_input_layout_id == '')
            errors += 'campo Layout é obrigatório\n'

        if (id_tipo_arquivo == '')
            errors += 'campo Layout é obrigatório\n'

        if (value_id_concorrente == '')
            errors += 'campo Concorrente é obrigatório'

        if (errors !== '') {
            alert(errors)
            return
        }

        try {
            var resultado = await method_post('/modelos/store', {
                nome_modelo: value_input_nome,
                id_layout: value_input_layout_id,
                id_concorrente: value_id_concorrente,
                id_tipo_arquivo: id_tipo_arquivo
            })


            if (resultado.status == true) {
                var modelo = resultado.data.modelo
                var el_modelo = document.querySelector("#modelo_id")
                el_modelo.className = 'd-flex' + class_input_form_control


                const option = document.createElement('option');
                option.value = modelo.id_modelo;
                option.textContent = modelo.nome_modelo;
                el_modelo.appendChild(option);

                el_modelo.value = modelo.id_modelo;

                habilitaDesabilitaModelo(true)
                el_modelo.disabled = true
                $('#modal_novo_modelo').modal('hide')
            }
        } catch (error) {
            alert(error)
        }
    })

    document.querySelector("#id_form_upload_arquivo").addEventListener('submit', async function(e) {
        e.preventDefault()

        var modelo_id = document.querySelector("#modelo_id").value
        var arquivo = document.querySelector("#arquivo")

        if (!arquivo.files[0]) {
            alert('Por favor, selecione um arquivo.');
            return;
        }

        const formData = new FormData();
        formData.append('arquivo', arquivo.files[0]);
        formData.append('modelo_id', modelo_id);

        try {
            var resultado = await method_post('/conversao/uploadArquivo', formData)
            if (resultado.status == true) {
                document.querySelector("#div_arquivo_convertido").textContent = JSON.
                stringify(resultado.data.arquivo_convertido, null, 2)
            }
        } catch (error) {
            alert(error)
        }
    })
</script>