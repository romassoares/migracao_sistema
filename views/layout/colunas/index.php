<?php include_once __DIR__ . '/../../includes/head.php' ?>

<div class="card col-10">
    <div class="card-header">
        Atualização de Layouts
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3 gap-2">
            <div class="col-4">
                <form id="id_form" action="layout/update" method="post">
                    <input type="hidden" name="id" id="id" value="<?php echo $layout->id ?>">
                    <div class="d-flex">
                        <input type="text" id="nome" name="nome" class="form-control form-control-sm" value="<?php echo $layout->nome ?>">
                        <button type="submit" class="btn btn-primary btn-sm">salvar</button>
                    </div>
                </form>
            </div>
            <div class="col-2 d-flex justify-content-end">
                <a href="layout/index" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
            </div>
        </div>
        <!-- <div class="d-flex"> -->
        <div class="card col-12 p-2">
            <div class="col-12">
                <button class="btn btn-success btn-sm" onclick="$('#modal_form').modal('show')"><i class="bi bi-plus"></i> Coluna</button>
            </div>
            <!-- <table id="table_layout"> -->
            <table id="table_layout_colunas" class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome Coluna</th>
                        <th>Tipo</th>
                        <th data-orderable='false'>Obrigatório</th>
                        <th data-orderable='false'>Posição</th>
                        <th data-orderable='false'>Ações</th>
                    </tr>
                </thead>
                <tbody id="table-body">
                    <?php
                    $i = 0;
                    foreach ($layout_colunas as $colunas) { ?>
                        <?php

                        $id_tr = $colunas['id'] . '_' . $i;
                        ?>
                        <tr class="" id="<?php echo $id_tr ?>" draggable="true"
                            ondragstart="dragstart_handler(event)"
                            ondrop="drop_handler(event)"
                            ondragover="dragover_handler(event)">
                            <td><?php echo $colunas['nome_exibicao'] ?></td>
                            <td><?php echo $colunas['tipo'] ?></td>
                            <td><?php echo ($colunas['obrigatorio'] == 1) ? 'Sim' : 'Não' ?></td>
                            <td id="col_posi"><?php echo $colunas['posicao'] ?></td>
                            <td>
                                <div class="d-flex">
                                    <div class="col">
                                        <a href="layout_colunas/edit?id_layout=<?php echo $layout->id ?>&id_layout_coluna=<?php echo $colunas['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                    </div>
                                    <div class="col">
                                        <a class="btn btn-danger btn-sm dropdown dropdown-toggle" id="dropdownMenuButtonDestroyLayoutConteudos" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="bi bi-trash"></i></a>
                                        <!--  -->
                                        <!-- dropdownMenuButtonDestroy -->
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonDestroyLayoutConteudos">
                                            <div class="col-sm-12 text-center">
                                                <p><strong>Deseja realmente excluir este item?</strong></p>
                                            </div>
                                            <hr>
                                            <div class="col-sm-12 text-center">
                                                <a href="layout_colunas/delete?id_layout=<?php echo $layout->id ?>&id_layout_coluna=<?php echo $colunas['id'] ?>" class='btn btn-success btn-sm'>Sim</a>
                                                <a class="btn btn-danger btn-sm">Não</a>
                                            </div>
                                        </div>
                                        <!--  -->
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php
                        $i++;
                    } ?>
                </tbody>
            </table>
        </div>
        <!-- </div> -->
    </div>
</div>
<!-- modal inserir nova coluna ao layout -->
<div class="modal fade" id="modal_form">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class=""><b>Novo Layout</b></h6>
                <span class="btn-close" id="btn_modal_close" data-bs-dismiss="modal" aria-label="Close"></span>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center">
                    <form action="layout_colunas/store" method="post" id="id_form">
                        <input type="hidden" name="id_layout" value="<?php echo $layout->id ?>">
                        <div class="m-2 order-1" id="div_btn_form_inserUpda">
                            <button type="submit" class="btn btn-success btn-sm" id="btn_submit">Inserir</button>
                        </div>
                </div>
                <div class="d-flex justify-content-around align-items-center mt-3 gap-2">
                    <h6 for="nome_exibicao" class="font_blue">Nome</h6>
                    <input type="text" name="nome_exibicao" id="nome_exibicao" class="form-control form-control-sm">
                </div>
                <div class="d-flex justify-content-around align-items-center mt-3 gap-2">
                    <h6 for="tipo">Tipo</h6>
                    <select name="tipo" id="tipo" class="form-control form-control-sm">
                        <option value="livre">Livre</option>
                        <option value="email">Email</option>
                        <option value="telefone">Telefone</option>
                        <option value="data">Data</option>
                        <option value="numerico">Numérico</option>
                        <option value="flag">Flag</option>
                    </select>
                </div>
                <div class="col-6 mt-3 d-flex gap-3">
                    <div class="form-check  ">
                        <input class="form-check-input" type="radio" id="obrigatorio_sim" name="obrigatorio" checked value="1">
                        <label class="form-check-label" for="obrigatorio">sim</label>
                    </div>
                    <div class="form-check ">
                        <input class="form-check-input" type="radio" id="obrigatorio_nao" name="obrigatorio" value="0">
                        <label class="form-check-label" for="obrigatorio">não</label>
                    </div>
                </div>
            </div>
            </form>
            <div class="body-footer">
                <div class="d-flex justify-content-center">
                    <strong id="footer-form-inserUpdat" class="text-danger"></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../../includes/scripts.php' ?>
<script>
    var ids_order = '<?php echo json_encode($ids_order) ?>'
    var itemAlterado = {}
    $(document).ready(function() {

    });

    function setFieldsForUpdate(id, value) {
        window.scrollTo(0, 0)
        document.querySelector("#id").value = id
        document.querySelector("#nome").value = value

        var el_form = document.querySelector('#id_form');
        var current_action = el_form.getAttribute('action');
        var new_action = current_action.replace('store', 'update');
        el_form.setAttribute('action', new_action);
    }

    let draggedId = null;

    function dragstart_handler(ev) {
        draggedId = ev.target.id;
        ev.dataTransfer.setData("text/plain", draggedId);

        const dragDiv = document.createElement('div');
        dragDiv.innerHTML = "Arrastando...";
        dragDiv.style.width = '120px';
        dragDiv.style.height = '40px';
        dragDiv.style.background = '#3498db';
        dragDiv.style.color = 'white';
        dragDiv.style.display = 'flex';
        dragDiv.style.alignItems = 'center';
        dragDiv.style.justifyContent = 'center';
        dragDiv.style.borderRadius = '8px';
        dragDiv.style.fontFamily = 'Arial';
        dragDiv.style.fontSize = '14px';
        dragDiv.style.boxShadow = '0 0 5px rgba(0,0,0,0.3)';
        dragDiv.style.position = 'absolute';
        dragDiv.style.top = '-1000px';
        dragDiv.style.left = '-1000px';
        dragDiv.style.zIndex = '9999';

        document.body.appendChild(dragDiv);

        ev.dataTransfer.setDragImage(dragDiv, 10, 10);

        ev.target.addEventListener('dragend', function cleanup() {
            if (dragDiv.parentNode) {
                document.body.removeChild(dragDiv);
            }
            ev.target.removeEventListener('dragend', cleanup);
        });
    }


    function dragover_handler(ev) {
        ev.preventDefault();
    }

    async function drop_handler(ev) {
        ev.preventDefault();
        const droppedTr = ev.target.closest('tr');
        const draggedTr = document.getElementById(draggedId);

        if (droppedTr && draggedTr && droppedTr !== draggedTr) {
            const tbody = document.getElementById('table-body');

            tbody.insertBefore(draggedTr, droppedTr);

            // console.log(prev, next)
            const prev = draggedTr.previousElementSibling;
            const next = draggedTr.nextElementSibling;

            const prevId = prev ? prev.id : '0';
            const nextId = next ? next.id : null;

            console.log("Dropped:", draggedTr.id);
            console.log("Previous ID:", prevId);
            console.log("Next ID:", nextId);

            var arrastado = draggedTr.id.split('_')
            var id_layout_arrastado = arrastado[0]
            var posicao_arrastado = arrastado[1]

            var ids = prevId.split('_')
            // var id_layout = ids[0]
            var posicao_alvo = ids[1]

            itemAlterado = {
                posicao_alvo: parseInt(posicao_alvo),
                posicao_dragged: parseInt(posicao_arrastado),
                id_layout: parseInt(id_layout_arrastado),
            }
            method_post('layout_colunas/novaOrdenacao', itemAlterado)
            location.reload()

            // atualizaOrdem(itemAlterado)
            // atualizarPosicoes();
        }
    }

    function atualizaOrdem(itemAlterado) {
        if (typeof ids_order === "string")
            ids_order = JSON.parse(ids_order);

        let posicao_alvo = parseInt(itemAlterado.posicao_alvo)
        let posicao_arrastado = parseInt(itemAlterado.posicao_arrastado)
        let id_layout_arrastado = parseInt(itemAlterado.id_layout_arrastado)

        ids_order.splice(posicao_arrastado, 1);

        ids_order.splice(posicao_alvo, 0, id_layout_arrastado.toString());
    }

    function atualizarPosicoes() {
        const tbody = document.getElementById('table-body');
        const linhas = tbody.querySelectorAll('tr');

        linhas.forEach((linha, index) => {
            const id_layout = ids_order[index];
            const novo_id = `${id_layout}_${1+index}`;

            linha.id = novo_id;

            const posicaoCell = linha.querySelector('#col_posi');
            if (posicaoCell) {
                posicaoCell.textContent = 1 + index;
            }
        });
    }
</script>
