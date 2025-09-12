<?php include_once __DIR__ . '/../includes/head.php' ?>

<div class="col-12 mb-3">
    <div class="row g-3">
        <div class="col-3">
            <div class="form-group">
                <label for="modelo" class="text-secondary mb-1">Concorrente</label>
                <input type="text" id="modelo" class="form-control form-control-sm bg-light" disabled value="<?= $concorrente ?>">
            </div>
        </div>
        <div class="col-3">
            <div class="form-group">
                <label for="layout" class="text-secondary mb-1">Layout</label>
                <input type="text" id="layout" class="form-control form-control-sm bg-light" disabled value="<?= $layout ?>">
            </div>
        </div>
        <div class="col-3">
            <div class="form-group">
                <label for="coluna_layout" class="text-secondary mb-1">Versão</label>
                <input type="text" id="coluna_layout" class="form-control form-control-sm bg-light" disabled>
            </div>
        </div>
        <div class="col-3">
            <div class="form-group">
                <label for="coluna_arquivo" class="text-secondary mb-1">Coluna</label>
                <input type="text" id="coluna_arquivo" class="form-control form-control-sm bg-light" disabled value="<?= $descricao_coluna ?>">
            </div>
        </div>
    </div>
</div>
<div class="card col-12">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">De / Para</h6>
            <div class="d-flex gap-2">
                <button onclick="adicionarDepara()" class="btn btn-success btn-sm">
                    <i class="bi bi-plus"></i> Adicionar De / Para
                </button>
                <div class="dropdown">
                    <button class="btn btn-danger btn-sm dropdown-toggle" type="button" id="dropdownMenuButtonDestroyDepara" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-trash"></i> Deletar Todos
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonDestroyDepara">
                        <div class="p-3">
                            <p class="text-center mb-2"><strong>Deseja realmente excluir todos os itens?</strong></p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="/conversao/deletarTodosDepara?id_layout_coluna=<?= $id_layout_coluna ?>&id_modelo=<?= $id_modelo ?>" class="btn btn-success btn-sm">Sim</a>
                                <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="dropdown">Não</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <form method="post" action="/conversao/deparaSalvar">
        <div class="card-body" id="body-depara">
            <input type="hidden" name="id_layout_coluna" value="<?= $id_layout_coluna ?>">
            <input type="hidden" name="id_modelo" value="<?= $id_modelo ?>">
            <?php
                if ($deparas): 
                    foreach ($deparas as $depara): 
                    ?>
                    <div class="card mb-2" data-tipo="existente" data-id="<?= $depara['id'] ?>">
                        <div class="card-body py-2">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="form-group">
                                        <label class="mb-1">De</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               id="depara_de_<?= $depara['id'] ?>" 
                                               name="depara_de_<?= $depara['id'] ?>" 
                                               value="<?= htmlspecialchars($depara['conteudo_de']) ?>">
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-group">
                                        <label class="mb-1">Para</label>
                                        <?php if ($tipo == 'sim_nao'): ?>
                                        <select class="form-select form-select-sm" 
                                                id="depara_para_<?= $depara['id'] ?>"
                                                name="depara_para_<?= $depara['id'] ?>">
                                            <?php if ($depara['Conteudo_para_livre'] != 'N' && $depara['Conteudo_para_livre'] != 'S'): ?>
                                                <option value="" disabled selected>Selecione...</option>
                                            <?php endif; ?>
                                            <option value="N" <?= ($depara['Conteudo_para_livre'] == 'N') ? 'selected' : '' ?>>Não</option>
                                            <option value="S" <?= ($depara['Conteudo_para_livre'] == 'S') ? 'selected' : '' ?>>Sim</option>
                                            
                                        </select>
                                        <?php else: ?>
                                        <input type="text" class="form-control form-control-sm" 
                                               id="depara_para_<?= $depara['id'] ?>"
                                               name="depara_para_<?= $depara['id'] ?>" 
                                               value="<?= htmlspecialchars($depara['Conteudo_para_livre']) ?>">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="form-group text-center">
                                        <label class="d-block mb-1">Substituir</label>
                                        <div class="d-flex justify-content-center">
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" 
                                                       id="substituir_<?= $depara['id'] ?>"
                                                       name="substituir_<?= $depara['id'] ?>" 
                                                       <?= $depara['substituir'] ? 'checked' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="form-group text-center">
                                        <label class="d-block mb-1">Qualquer Concorrente</label>
                                        <div class="d-flex justify-content-center">
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" 
                                                       id="qualquer_concorrente_<?= $depara['id'] ?>"
                                                       name="qualquer_concorrente_<?= $depara['id'] ?>" 
                                                       <?= !isset($depara['id_modelo_coluna']) ? 'checked' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto ps-0">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-danger btn-sm mt-4 dropdown-toggle" 
                                                id="dropdownMenuButtonDestroy_<?= $depara['id'] ?>" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonDestroy_<?= $depara['id'] ?>">
                                            <div class="p-3">
                                                <p class="text-center mb-2"><strong>Deseja realmente excluir este item?</strong></p>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-success btn-sm" onclick="deletarDepara(<?= $depara['id'] ?>)">Sim</button>
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="dropdown">Não</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    endforeach;
                endif; ?>
        </div>
        <div class="d-flex justify-content-center mb-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Salvar De / Para
            </button>
        </div>
    </form>
</div>

<script>

function deletarDepara(id) {
    fetch(`/conversao/deletarDepara?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const card = document.querySelector(`.card[data-tipo="existente"][data-id="${id}"]`);
                if (card) {
                    card.remove();
                    alert('Item deletado com sucesso.');
                }
            } else {
                alert('Erro ao deletar o item.');
            }
        })
}

let contadorNovosDepara = 1;

function criarCampoPara(tipo, index) {
    if(tipo == 'sim_nao') {
        return `
            <select class="form-select form-select-sm" 
                    id="novo_depara_para_${index}" 
                    name="novo_depara_para_${index}">
                <option value="" selected disabled>Selecione...</option>
                <option value="N">Não</option>
                <option value="S">Sim</option>
            </select>`;
    } else {
        return `<input type="text" class="form-control form-control-sm" 
                       id="novo_depara_para_${index}" 
                       name="novo_depara_para_${index}">`;
    }
}

function adicionarDepara() {
    const bodyDepara = document.getElementById('body-depara');
    const novoCard = document.createElement('div');
    const tipo = '<?= $tipo ?>';
    const index = contadorNovosDepara++;
    
    const campoPara = criarCampoPara(tipo, index);

    novoCard.className = 'card mb-2';
    novoCard.setAttribute('data-tipo', 'novo');
    novoCard.setAttribute('data-id', index);
    
    novoCard.innerHTML = `
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col">
                    <div class="form-group">
                        <label class="mb-1">De</label>
                        <input type="text" class="form-control form-control-sm" 
                               id="novo_depara_de_${index}" 
                               name="novo_depara_de_${index}">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label class="mb-1">Para</label>
                        ${campoPara}
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-group text-center">
                        <label class="d-block mb-1">Substituir</label>
                        <div class="d-flex justify-content-center">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" 
                                       id="novo_substituir_${index}" 
                                       name="novo_substituir_${index}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-group text-center">
                        <label class="d-block mb-1">Qualquer Concorrente</label>
                        <div class="d-flex justify-content-center">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" 
                                       id="novo_qualquer_concorrente_${index}" 
                                       name="novo_qualquer_concorrente_${index}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-auto ps-0">
                    <div class="dropdown">
                        <button type="button" class="btn btn-danger btn-sm mt-4 dropdown-toggle" 
                                id="dropdownMenuButtonDestroy_${index}" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-trash"></i>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButtonDestroy_${index}">
                            <div class="p-3">
                                <p class="text-center mb-2"><strong>Deseja realmente excluir este item?</strong></p>
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-success btn-sm" onclick="removerLinha(this)">Sim</button>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="dropdown">Não</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="novo_card_ids[]" value="${index}">
    `;
    
    bodyDepara.appendChild(novoCard);
}

function removerLinha(button) {
    const card = button.closest('.card');
    card.remove();
}
</script>

<?php include_once __DIR__ . '/../includes/scripts.php' ?>