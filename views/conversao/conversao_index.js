var convertidos = []
var modelo = []
var convertidosHeaders = []
var layout_colunas = []
var modelos_colunas = []

var id_layout = ""
var id_tipo_arquivo = ""


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

        document.querySelector("#btn_processa_arquivo").className = 'col mt-2'
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


            id_layout = modelo.id_layout
            id_tipo_arquivo = modelo.id_tipo_arquivo

            layout_colunas = Object.entries(resultado.data.layout_colunas)
            modelo = resultado.data.modelo

            if (modelo.status !== 'P') {
                existeArquivoProcessado(modelo.id_modelo, modelo.id_concorrente)
            }

            // console.log(modelo)

            convertidosHeaders = Object.values(resultado.data.arquivo_convertido[0])
            if (modelo.descr_tipo_arquivo == "xml" || modelo.descr_tipo_arquivo == "json" || modelo.descr_tipo_arquivo == "xlsx") {
                convertidos = Object.values(resultado.data.arquivo_convertido[1])
            } else {
                convertidos = Object.values(resultado.data.arquivo_convertido)
            }

            modelos_colunas = Object.entries(resultado.data.modelos_colunas)

            montaSelectsParaAssociacaoColunas(modelo, layout_colunas, convertidos, convertidosHeaders)

            setTimeout(() => {
                modelos_colunas.forEach((el) => {
                    const el_select = document.querySelector("#select_layout_coluna_" + el[1].id_layout_coluna);
                    if (!el_select) return;

                    const valor = el[1].descricao_coluna;

                    const oldOnChange = el_select.onchange;
                    el_select.onchange = null;

                    $(el_select).val(valor).trigger('change.select2');

                    const optionToDisable = el_select.querySelector(`option[value="${valor}"]`);
                    if (optionToDisable) {
                        optionToDisable.disabled = true;
                    }
                    atualizaColunas()
                    document.querySelector("#load").style.display = 'none'

                    el_select.onchange = oldOnChange;
                });
            }, 500)
        }, 1000)
    }
});

function existeArquivoProcessado(id_modelo, id_concorrente) {
    // console.log('arquivoProcessado ', id_modelo, id_concorrente)
    let div_btn_processados_arquivo = document.querySelector("#div_btn_processados_arquivo")
    div_btn_processados_arquivo.className = 'card col-md-6 col-sm-12 d-flex justify-content-center align-items-center p-2'
}

function dispararEventoEmSelect(el_select, value_id) {
    el_select.value = value_id;
    const evento = new Event('change', { bubbles: true });
    el_select.dispatchEvent(evento);
}


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

            // window.location.href = "/conversao/index?id_modelo=" + modelo.id_modelo;


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

    if (convertidosHeaders.length > 0) {
        const div_row = document.createElement("div")
        div_row.className = "overflow-auto d-flex gap-1 h-1/4 pb-3"

        // Mapeia vínculos já existentes para marcar o select
        let vinculos = {};
        if (Array.isArray(modelos_colunas)) {
            modelos_colunas.forEach(([_, v]) => {
                if (v.descricao_coluna && v.id_layout_coluna) {
                    vinculos[v.descricao_coluna] = v.id_layout_coluna;
                }
            });
        }

        convertidosHeaders.forEach((header, idx) => {

            const div_col = document.createElement("div")
            div_col.className = "col-3 d-flex flex-column align-items-stretch"
            div_col.id = "id_selects_convertidos_header_" + idx

            // Input: convertidosHeaders
            const input_convertido = document.createElement('input')
            input_convertido.disabled = true
            input_convertido.id = "input_convertido_header_" + idx
            input_convertido.value = header
            input_convertido.className = "form-control form-control-sm mb-1"
            div_col.appendChild(input_convertido)

            // Select: layout_colunas
            const div_input_grupo = document.createElement("div")
            div_input_grupo.className = "input-group input-group-sm mb-1"

            const select_layout_coluna = document.createElement("select")
            select_layout_coluna.id = "select_layout_coluna_" + idx
            select_layout_coluna.className = "select2 form-control form-control-sm"
            select_layout_coluna.onchange = function () {
                salvaVinculacaoColunaConvetidoComLayoutColunas(this)
                atualizaColunas();
            }

            const optionSelec = document.createElement('option');
            optionSelec.textContent = 'selecione ...';
            select_layout_coluna.appendChild(optionSelec);

            layout_colunas.forEach(([chave, valor]) => {
                const option = document.createElement('option');
                option.value = valor['id'];
                option.textContent = valor['nome_exibicao'];
                select_layout_coluna.appendChild(option);
            });

            // Marcar o select se já houver vínculo
            if (vinculos[header]) {
                select_layout_coluna.value = vinculos[header];
            }

            const div_input_grupo_append = document.createElement("div")
            div_input_grupo_append.className = "input-group-append d-flex"

            const span_input_grupo_append = document.createElement("span")
            span_input_grupo_append.className = "input-group-text"
            span_input_grupo_append.style.borderRadius = "0"
            span_input_grupo_append.style.cursor = "pointer"
            span_input_grupo_append.title = "Limpar"
            span_input_grupo_append.innerHTML = "<i class='bi bi-eraser'></i>"
            span_input_grupo_append.onclick = function () {
                const ol_value = select_layout_coluna.value
                const oldOnChange = select_layout_coluna.onchange;
                select_layout_coluna.onchange = null;
                select_layout_coluna.value = ''
                $(select_layout_coluna).val('').trigger('change.select2');
                removeColunaModelo(select_layout_coluna, ol_value)
                atualizaColunas();
                select_layout_coluna.onchange = oldOnChange;
            }
            div_input_grupo_append.appendChild(span_input_grupo_append)

            const span_botao_depara = document.createElement("span")
            span_botao_depara.className = "input-group-text"
            span_botao_depara.style.borderRadius = "0 4px 4px 0"
            span_botao_depara.style.cursor = "pointer"
            span_botao_depara.title = "Configurar De/Para"
            span_botao_depara.innerHTML = "<i class='bi bi-journals'></i>"
            span_botao_depara.onclick = function () {
                window.location.href = '/conversao/depara?id_layout_coluna=' + select_layout_coluna.value + '&id_modelo=' + modelo.id_modelo;
            }

            div_input_grupo_append.appendChild(span_botao_depara);
            div_input_grupo.appendChild(select_layout_coluna)
            div_input_grupo.appendChild(div_input_grupo_append)
            div_col.appendChild(div_input_grupo)

            $(select_layout_coluna).select2({
                theme: "bootstrap-5",
                placeholder: "Selecione ...",
            });

            const tblValores = document.createElement("table")
            tblValores.className = "table table-sm mb-0"
            tblValores.id = "tbl_valores_convertidos_header_" + idx

            const tbodyValores = document.createElement("tbody")
            tblValores.appendChild(tbodyValores)

            const base = Array.isArray(convertidos) ? convertidos : [convertidos];
            const items = base.flatMap(row => getValue(row, header));

            let count = 0;
            for (let i = 0; i < items.length; i++) {
                if (count === 10) {
                    i += 5; // pula 5
                    count = 0;
                    if (i >= items.length) break;
                }
                let item = normalizaValor(items[i]);
                if (item) {
                    let el_tr = document.createElement('tr');
                    let el_td = document.createElement('td');
                    el_td.innerText = item;

                    el_td.style.maxWidth = "150px";
                    el_td.style.whiteSpace = "nowrap";
                    el_td.style.overflow = "hidden";
                    el_td.style.textOverflow = "ellipsis";
                    el_td.style.cursor = "pointer";

                    el_td.onclick = function () {
                        let box = document.createElement('div');
                        box.innerText = item;
                        box.style.position = "fixed";
                        box.style.top = "50%";
                        box.style.left = "50%";
                        box.style.transform = "translate(-50%, -50%)";
                        box.style.padding = "20px";
                        box.style.background = "white";
                        box.style.border = "1px solid #ccc";
                        box.style.boxShadow = "0 4px 8px rgba(0,0,0,0.2)";
                        box.style.zIndex = "9999";

                        let closeBtn = document.createElement('button');
                        closeBtn.innerText = "Fechar";
                        closeBtn.style.marginTop = "10px";
                        closeBtn.onclick = function () {
                            document.body.removeChild(box);
                        };

                        box.appendChild(document.createElement("br"));
                        box.appendChild(closeBtn);

                        document.body.appendChild(box);
                    };

                    el_tr.appendChild(el_td);
                    tbodyValores.appendChild(el_tr);
                    count++;
                }
            }
            div_col.appendChild(tblValores)

            div_row.appendChild(div_col)
        })
        div_container.appendChild(div_row)
    } else {
        alert('Nenhum header encontrado em convertidosHeaders')
    }
    document.querySelector("#load").style.display = 'none'
}

const getValue = (row, header) => {
    if (row == null) return [];

    const parts = Array.isArray(header)
        ? header
        : String(header).split('.').filter(Boolean);

    if (parts.length === 0) {
        return Array.isArray(row) ? row : [row];
    }

    const [first, ...rest] = parts;

    if (Array.isArray(row)) {
        return row.flatMap(r => getValue(r, parts));
    }

    // Caso não seja objeto válido

    if (typeof row !== 'object') {
        return [];
    }

    const next = row[first];
    if (next === undefined) {
        return getValue(row, rest);
    }

    // const next = row?.[first];
    return getValue(next, rest);
};

function atualizaColunas() {
    const allSelects = document.querySelectorAll('select[id^="select_layout_coluna_"]');

    const valoresSelecionados = Array.from(allSelects)
        .map(sel => sel.value)
        .filter(value => value !== '');

    allSelects.forEach(currentSelect => {
        Array.from(currentSelect.options).forEach(option => {
            option.disabled = false;
        });

        valoresSelecionados.forEach(valor => {
            if (currentSelect.value !== valor && valor !== '') {
                const optionToDisable = Array.from(currentSelect.options).find(opt => opt.value === valor);
                if (optionToDisable) {
                    optionToDisable.disabled = true;
                }
            }
        });

        $(currentSelect).select2('destroy');
        $(currentSelect).select2({
            theme: "bootstrap-5",
            placeholder: "Selecione ...",
            width: '100%',
        });
    });
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

    const id_layout_coluna = select.value
    const id_ref_col = select.id.replace("select_layout_coluna_", '')

    var descricao_coluna = document.querySelector("#input_convertido_header_" + id_ref_col).value;

    const dataJsom = {}

    dataJsom.descricao_coluna = descricao_coluna;
    dataJsom.id_layout_coluna = id_layout_coluna
    dataJsom.id_modelo = document.querySelector("#modelo_id").value
    dataJsom.id_concorrente = modelo.id_concorrente

    var resultado = await method_post('/conversao/salvaVinculacaoConvertidoLayout', dataJsom)
    // if (resultado.status == true) {
    //     if (resultado.data.msg !== '')
    //         alert(resultado.data.msg)
    // }
}

async function removeColunaModelo(select, ol_value) {
    const id_layout_coluna = select.value
    const id_ref_col = select.id.replace("select_layout_coluna_", '')
    var descricao_coluna = document.querySelector("#input_convertido_header_" + id_ref_col).value;

    const dataJsom = {}

    dataJsom.descricao_coluna = descricao_coluna
    dataJsom.id_layout_coluna = ol_value
    dataJsom.id_modelo = document.querySelector("#modelo_id").value
    dataJsom.id_concorrente = modelo.id_concorrente

    var resultado = await method_post('/conversao/removeVinculacaoConvertidoLayout', dataJsom)
    // if (resultado.status == true) {
    //     if (resultado.data.msg !== '')
    //         alert(resultado.data.msg)
    // }
}


async function processaArquivo() {
    document.querySelector("#load").style.display = 'block'

    var modelo_id = document.querySelector("#modelo_id").value
    var id_concorrente = document.querySelector("#concorrente_id").value

    axios({
        method: 'post',
        url: '/modelo/processaArquivo',
        data: {
            id_modelo: modelo_id,
            id_concorrente: id_concorrente,
            id_layout: id_layout,
            id_tipo_arquivo: id_tipo_arquivo
        },
        // responseType: 'blob' // <- isso é essencial
    }).then(response => {
        // const url = window.URL.createObjectURL(new Blob([response.data]));
        // const link = document.createElement('a');
        // link.href = url;
        // link.setAttribute('download', 'imoveis.xlsx');
        // document.body.appendChild(link);
        // link.click();
        // link.remove();
    }).catch(error => {
        console.error('Erro ao gerar Excel:', error);
    });

    document.querySelector("#load").style.display = 'none'
}