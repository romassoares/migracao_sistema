var convertidos = []
var modelo = []
var convertidosHeaders = []
var layout_colunas = []
var modelos_colunas = []

$(document).ready(function () {

    $('#input_layout_coluna').select2({
        theme: "bootstrap-5",
        width: '100%',
        placeholder: "Selecione ...",
        language: {
            noResults: function () {
                return "Nenhuma coluna encontrado";
            }
        }
    });


    if (modelo.id_concorrente) {
        document.querySelector("#load").style.display = 'block'
        dispararEventoEmSelect(document.querySelector("#concorrente_id"), modelo.id_concorrente)

        setTimeout(() => {
            dispararEventoEmSelect(document.querySelector("#modelo_id"), modelo.id_modelo)
        }, 1000)

        setTimeout(async () => {
            var resultado = await method_post('/conversao/EditVinculacaoArquivo', {
                id_modelo: modelo.id_modelo,
                id_layout: modelo.id_layout,
                id_concorrente: modelo.id_concorrente,
                id_tipo_arquivo: modelo.id_tipo_arquivo
            })

            layout_colunas = Object.entries(resultado.data.layout_colunas)
            modelo = resultado.data.modelo
            convertidosHeaders = Object.values(resultado.data.arquivo_convertido[0])
            if (modelo.descr_tipo_arquivo == "xml" || modelo.descr_tipo_arquivo == "json") {
                convertidos = Object.values(resultado.data.arquivo_convertido[1])
            } else {
                convertidos = Object.values(resultado.data.arquivo_convertido)
            }

            modelos_colunas = Object.entries(resultado.data.modelos_colunas)

            montaSelectsParaAssociacaoColunas(modelo, layout_colunas, convertidos, convertidosHeaders)

            setTimeout(() => {
                modelos_colunas.forEach((el) => {
                    const el_select = document.querySelector("#caminho_absol_arquivo_convertido_" + el[1].id_layout_coluna);
                    if (!el_select) return;

                    const valor = el[1].descricao_coluna;

                    const oldOnChange = el_select.onchange;
                    el_select.onchange = null;

                    $(el_select).val(valor).trigger('change.select2');

                    const optionToDisable = el_select.querySelector(`option[value="${valor}"]`);
                    if (optionToDisable) {
                        optionToDisable.disabled = true;
                    }
                    document.querySelector("#load").style.display = 'none'
                    atualizaColunas(el_select)

                    el_select.onchange = oldOnChange;
                });
            }, 500)
        }, 1000)
    }
});

function dispararEventoEmSelect(el_select, value_id) {
    el_select.value = value_id;
    const evento = new Event('change', { bubbles: true });
    el_select.dispatchEvent(evento);
}
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

document.querySelector('#layout_id').addEventListener('change', function (e) {
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

document.querySelector("#modelo_id").addEventListener("change", function (e) {
    e.preventDefault()
    const selectedOption = this.options[this.selectedIndex];
    document.querySelector("#tipoArquivoInputGroup").textContent = selectedOption.getAttribute('descr_tipo_arquivo')

    let tipo = selectedOption.getAttribute('descr_tipo_arquivo')
    var accepts = tipo == 'xlsx' ? '.xlsx, .xls' : '.' + tipo
    document.querySelector("#arquivo").setAttribute('accept', accepts)
    habilitaDesabilitaModelo(true)
})

function habilitaDesabilitaModelo(action) {
    classNameDivUpload = 'd-none'
    if (action == true)
        classNameDivUpload = 'd-flex'

    document.querySelector("#div_upload_arquivo").className = classNameDivUpload
}

var class_input_form_control = ' form-control '
document.querySelector("#concorrente_id").addEventListener('change', async function (e) {
    e.preventDefault()
    document.querySelector("#tipoArquivoInputGroup").textContent = '?'
    habilitaDesabilitaModelo(false)
    try {
        var resultado = await method_post('/modelos/getModelos', {
            id_concorrente: this.value
        })

        var modelos = Object.values(resultado.data.modelos)

        if (modelos.length > 0) {
            document.querySelector('#modelo_id').className = 'd-flex' + class_input_form_control
            montaSelect('#modelo_id', 'id_modelo', 'nome_modelo', 'descr_tipo_arquivo', modelos)
        } else {
            document.querySelector('#modelo_id').className = 'd-none'
            abrirModalFormModelo()
        }
    } catch (error) {
        alert(error)
    }
})

document.querySelector("#id_form_modelo").addEventListener("submit", async function (e) {
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
            option.textContent = modelo.nome_modelo
            document.querySelector("#tipo_arquivo_ref").textContent = modelo.descr_tipo_arquivo;
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


document.querySelector("#id_form_upload_arquivo").addEventListener('submit', async function (e) {
    e.preventDefault()
    document.querySelector("#div_upload_arquivo").className = 'd-none'

    document.querySelector("#load").style.display = 'block'

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

            layout_colunas = Object.entries(resultado.data.layout_colunas)
            modelo = resultado.data.modelo
            convertidosHeaders = Object.values(resultado.data.arquivo_convertido[0])
            if (modelo.descr_tipo_arquivo == "xml" || modelo.descr_tipo_arquivo == "json") {
                convertidos = Object.values(resultado.data.arquivo_convertido[1])
            } else {
                convertidos = Object.values(resultado.data.arquivo_convertido)
            }

            montaSelectsParaAssociacaoColunas(modelo, layout_colunas, convertidos, convertidosHeaders)

        }
    } catch (error) {
        alert(error)
    }
    document.querySelector("#load").style.display = 'none'
})

function montaSelectsParaAssociacaoColunas(modelo, layout_colunas, convertidos, convertidosHeaders) {
    document.querySelector("#div_arquivo_convertido").className = 'd-flex'

    const div_container = document.querySelector("#div_arquivo_convertido")
    div_container.textContent = ''

    if (Object.keys(layout_colunas).length > 0) {
        const div_row = document.createElement("div")
        div_row.className = "overflow-auto d-flex gap-1 h-1/4 pb-3"

        layout_colunas.forEach(([chave, valor]) => {
            const div_col = document.createElement("div")
            div_col.className = "col-3 d-flex flex-column align-items-stretch"
            div_col.id = "id_selects_convertidos_" + valor['id']

            // Coluna: input
            const input_layout_coluna = document.createElement('input')
            input_layout_coluna.disabled = true
            input_layout_coluna.id = "input_layout_coluna_" + valor['id']
            input_layout_coluna.value = valor['nome_exibicao']
            input_layout_coluna.className = "form-control form-control-sm mb-1"
            div_col.appendChild(input_layout_coluna)

            // Select + span
            const div_input_grupo = document.createElement("div")
            div_input_grupo.className = "input-group input-group-sm mb-1"

            const caminho_absol_arquivo_convertido = document.createElement("select")
            caminho_absol_arquivo_convertido.id = "caminho_absol_arquivo_convertido_" + valor['id']
            caminho_absol_arquivo_convertido.className = "select2 form-control form-control-sm"
            caminho_absol_arquivo_convertido.onchange = function () {
                atualizaColunas(this)
                carregaColunaLayout(this, valor['id'])
                salvaVinculacaoColunaConvetidoComLayoutColunas(this)
            }

            const optionSelec = document.createElement('option');
            optionSelec.textContent = 'selecione ...';
            caminho_absol_arquivo_convertido.appendChild(optionSelec);

            Object.entries(convertidosHeaders).forEach(([chave, valorHeader]) => {
                const option = document.createElement('option');
                option.value = valorHeader;
                option.textContent = valorHeader;
                caminho_absol_arquivo_convertido.appendChild(option);
            });

            const div_input_grupo_append = document.createElement("div")
            div_input_grupo_append.className = "input-group-append"

            const span_input_grupo_append = document.createElement("span")
            span_input_grupo_append.className = "input-group-text"
            span_input_grupo_append.innerHTML = "<i class='bi bi-eraser'></i>"
            span_input_grupo_append.onclick = function () {
                const ol_value = caminho_absol_arquivo_convertido.value
                const oldOnChange = caminho_absol_arquivo_convertido.onchange;
                caminho_absol_arquivo_convertido.onchange = null;
                caminho_absol_arquivo_convertido.value = ''
                $(caminho_absol_arquivo_convertido).val('').trigger('change.select2');
                removeColunaModelo(caminho_absol_arquivo_convertido, ol_value)
                caminho_absol_arquivo_convertido.onchange = oldOnChange;
                atualizaColunas(caminho_absol_arquivo_convertido)
                carregaColunaLayout(caminho_absol_arquivo_convertido, valor['id'])
            }
            div_input_grupo_append.appendChild(span_input_grupo_append)
            div_input_grupo.appendChild(caminho_absol_arquivo_convertido)
            div_input_grupo.appendChild(div_input_grupo_append)
            div_col.appendChild(div_input_grupo)

            $(caminho_absol_arquivo_convertido).select2({
                theme: "bootstrap-5",
                placeholder: "Selecione ...",
            });

            // Tabela de valores

            const tblValores = document.createElement("table")
            tblValores.className = "table table-sm mb-0"
            tblValores.id = "tbl_valores_convertidos_" + valor['id']

            const tbodyValores = document.createElement("tbody")
            tblValores.appendChild(tbodyValores)
            div_col.appendChild(tblValores)

            // Se já houver valor selecionado, carrega os valores
            setTimeout(() => {
                if (caminho_absol_arquivo_convertido.value) {
                    carregaColunaLayout(caminho_absol_arquivo_convertido, valor['id'])
                }
            }, 0)

            div_row.appendChild(div_col)
        })
        div_container.appendChild(div_row)
    } else {
        alert('Nenhuma coluna cadastrada em /layout/colunas')
    }
}

function atualizaColunas(select) {
    const allSelects = document.querySelectorAll('select[id^="caminho_absol_arquivo_convertido_"]');

    const valoresSelecionados = Array.from(allSelects)
        .filter(sel => sel.value !== '')
        .map(sel => sel.value);

    allSelects.forEach(sel => {
        Array.from(sel.options).forEach(opt => {
            opt.disabled = false;
        });

        valoresSelecionados.forEach(valor => {
            if (sel.value !== valor) {
                const opt = Array.from(sel.options).find(o => o.value === valor);
                if (opt) {
                    opt.disabled = true;
                }
            }
        });

        // Carregar os valores do array convertidos na tabela de cada coluna
        const id_layout_coluna = sel.id.replace('caminho_absol_arquivo_convertido_', '');
        const partes = sel.value.split('.');
        let items = [];

        if (!sel.value) {
            // Limpa a tabela se nada selecionado
            var tblValores = document.querySelector(`#tbl_valores_convertidos_${id_layout_coluna}`);
            if (tblValores) {
                var tbodyValores = tblValores.querySelector('tbody');
                tbodyValores.textContent = '';
            }
            return;
        }

        if (modelo.descr_tipo_arquivo == "xml") {
            items = convertidos.flatMap(obj => {
                let valor = obj;
                for (const parte of partes) {
                    if (Array.isArray(valor)) {
                        valor = valor.flatMap(item => item[parte]);
                    } else {
                        valor = valor?.[parte];
                    }
                }
                return Array.isArray(valor) ? valor : [valor];
            });
        } else if (modelo.descr_tipo_arquivo == "json") {
            items = convertidos.flatMap(obj => {
                let valor = obj;
                for (const parte of partes) {
                    if (Array.isArray(valor)) {
                        valor = valor.flatMap(item => item[parte]);
                    } else {
                        valor = valor?.[parte];
                    }
                }
                return Array.isArray(valor) ? valor : [valor];
            });
        } else {
            const index = convertidosHeaders.indexOf(sel.value);
            if (index === -1) {
                items = [];
            } else {
                items = convertidos.slice(1).map(row => {
                    return row[index];
                });
            }
        }

        var tblValores = document.querySelector(`#tbl_valores_convertidos_${id_layout_coluna}`);
        if (tblValores) {
            var tbodyValores = tblValores.querySelector('tbody');
            tbodyValores.textContent = '';
            items.forEach((item) => {
                item = normalizaValor(item);
                if (item) {
                    var el_tr = document.createElement('tr');
                    var el_td = document.createElement('td');
                    el_td.innerText = item;
                    el_tr.appendChild(el_td);
                    tbodyValores.appendChild(el_tr);
                }
            });
        }
    });
}

function carregaColunaLayout(select, id_layout_coluna) {
    const partes = select.value.split('.');
    var items = [];

    if (!select.value) { // Limpa a tabela se nada selecionado
        var tblValores = document.querySelector(`#tbl_valores_convertidos_${id_layout_coluna}`);
        if (tblValores) {
            var tbodyValores = tblValores.querySelector('tbody');
            tbodyValores.textContent = '';
        }
        return;
    }

    if (modelo.descr_tipo_arquivo == "xml") {
        items = convertidos.flatMap(obj => {
            let valor = obj;
            for (const parte of partes) {
                if (Array.isArray(valor)) {
                    valor = valor.flatMap(item => item[parte]);
                } else {
                    valor = valor?.[parte];
                }
            }
            return Array.isArray(valor) ? valor : [valor];
        });
    } else if (modelo.descr_tipo_arquivo == "json") {
        items = convertidos.flatMap(obj => {
            let valor = obj;
            for (const parte of partes) {
                if (Array.isArray(valor)) {
                    valor = valor.flatMap(item => item[parte]);
                } else {
                    valor = valor?.[parte];
                }
            }
            return Array.isArray(valor) ? valor : [valor];
        });
    } else {
        const index = convertidosHeaders.indexOf(select.value);
        if (index === -1) {
            console.error(`Coluna '${select.value}' não encontrada em convertidosHeaders.`);
        } else {
            items = convertidos.slice(1).map(row => {
                return row[index];
            });
        }
    }

    // Atualiza a tabela de valores correspondente
    var tblValores = document.querySelector(`#tbl_valores_convertidos_${id_layout_coluna}`);
    if (tblValores) {
        var tbodyValores = tblValores.querySelector('tbody');
        tbodyValores.textContent = '';
        items.forEach((item) => {
            item = normalizaValor(item);
            if (item) {
                var el_tr = document.createElement('tr');
                var el_td = document.createElement('td');
                el_td.innerText = item;
                el_tr.appendChild(el_td);
                tbodyValores.appendChild(el_tr);
            }
        });
    }
}

function normalizaValor(value) {
    if (value === null) {
        return 'null';
    }

    if (value === '') {
        return 'null';
    }

    if (typeof value === 'boolean') {
        return value.toString();
    }

    if (Array.isArray(value)) {
        return 'false';
    }

    if (typeof value === 'object') {
        return 'false';
    }

    if (typeof value === undefined || typeof value === 'undefined') {
        return 'undefined';
    }

    return value;
}

async function salvaVinculacaoColunaConvetidoComLayoutColunas(select) {
    const descricao_coluna = select.value;
    const id_layout_coluna = select.id.replace("caminho_absol_arquivo_convertido_", '');
    // const id_modelo = modelo.id_modelo
    const id_concorrente = modelo.id_concorrente

    const dataJsom = {}

    dataJsom.descricao_coluna = descricao_coluna
    dataJsom.id_layout_coluna = id_layout_coluna
    dataJsom.id_modelo = document.querySelector("#modelo_id").value
    dataJsom.id_concorrente = id_concorrente

    var resultado = await method_post('/conversao/salvaVinculacaoConvertidoLayout', dataJsom)
    if (resultado.status == true) {
        if (resultado.data.msg !== '')
            alert(resultado.data.msg)
    }
}

async function removeColunaModelo(select, ol_value) {
    const dataJsom = {}

    dataJsom.descricao_coluna = ol_value
    dataJsom.id_layout_coluna = select.id.replace("caminho_absol_arquivo_convertido_", '')
    dataJsom.id_modelo = document.querySelector("#modelo_id").value
    dataJsom.id_concorrente = modelo.id_concorrente

    var resultado = await method_post('/conversao/removeVinculacaoConvertidoLayout', dataJsom)
    if (resultado.status == true) {
        if (resultado.data.msg !== '')
            alert(resultado.data.msg)
    }
}