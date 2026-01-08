var convertidos = []
var modelo = []
var convertidosHeaders = []
var layout_colunas = []
var modelos_colunas = []
var id_arquivo = ""
var id_layout = ""
var id_concorrente = ""
var id_tipo_arquivo = ""
var modelo_id = ""

$(document).ready(async function () {
    load(true);
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
    // exibe_btn_e_input_arquivo(false)

    if (modelo.id_concorrente) {
        id_concorrente = modelo.id_concorrente

        document.querySelector("#btn_processa_arquivo").className = 'd-flex col mt-2'

        dispararEventoEmSelect(document.querySelector("#concorrente_id"), modelo.id_concorrente)

        setTimeout(() => {
            dispararEventoEmSelect(document.querySelector("#modelo_id"), modelo.id_modelo)
        }, 400)

        setTimeout(async () => {
            var resultado = await method_post('/conversao/EditVinculacaoArquivo', {
                id_modelo: modelo.id_modelo,
                id_layout: modelo.id_layout,
                id_concorrente: modelo.id_concorrente,
                id_tipo_arquivo: modelo.id_tipo_arquivo,
                id_arquivo: id_arquivo
            })

            id_layout = modelo.id_layout
            id_tipo_arquivo = modelo.id_tipo_arquivo

            prepara_variaveis_e_campos(resultado)
        }, 700)

        load(false);
        exibe_btn_duplica_modelo(false)
        exibe_btn_e_input_arquivo(false)
    } else {
        load(false)
        exibe_btn_duplica_modelo(true)
        exibe_btn_e_input_arquivo(true)
    }
    load(false)
});

function load(value) {
    // let stack = new Error().stack;
    // console.log(stack)

    const container = document.querySelector('#bodyConversao');
    const elements = container.querySelectorAll(' select, textarea, button');

    if (value == true) {
        elements.forEach(el => el.disabled = true);
        document.querySelector('#load').className = 'd-flex loader'
    } else {
        setTimeout(() => {
            elements.forEach(el => el.disabled = false);
            document.querySelector('#load').className = 'd-none'
        }, 1000)
    }
}

function existeArquivoProcessado() {
    document.querySelector("#arquivo").className = 'd-none'
    let div_btn_processados_arquivo = document.querySelector("#div_btn_processados_arquivo")
    div_btn_processados_arquivo.className = 'card col-md-6 col-sm-12 d-flex justify-content-center align-items-center p-2 '
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
    id_layout = this.value;
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

document.querySelector("#arquivo").addEventListener('change', async function (e) {
    e.preventDefault()
    load(true)
    modelo_id = document.querySelector("#modelo_id").value
    var arquivo = document.querySelector("#arquivo")

    lerArquivo(arquivo, modelo_id)

    load(false)
})

async function lerArquivo(arquivo, modelo_id) {
    // var id_concorrente = document.querySelector("#concorrente_id").value
    // id_layout = document.querySelector("#layout_id").value
    load(true)
    if (!arquivo.files[0]) {
        alert('Por favor, selecione um arquivo.');
        return;
    }

    const formData = new FormData();
    formData.append('arquivo', arquivo.files[0]);
    formData.append('modelo_id', modelo_id);
    var resultado = await method_post('/arquivo/lerArquivo', formData)
    if (resultado.status == true) {
        prepara_variaveis_e_campos(resultado)
    } else {
        console.log(resultado)
        alert(resultado)
    }
    load(false)
}

function prepara_variaveis_e_campos(resultado) {
    load(true)
    modelo = resultado.data.modelo

    if (modelo.status == 'P') {
        exibe_btn_e_input_arquivo(false)
        existeArquivoProcessado()
    }

    layout_colunas = Object.entries(resultado.data.layout_colunas)
    convertidosHeaders = Object.values(resultado.data.arquivo_convertido[0])
    if (modelo.descr_tipo_arquivo == "xml" || modelo.descr_tipo_arquivo == "json" || modelo.descr_tipo_arquivo == "xlsx") {
        convertidos = Object.values(resultado.data.arquivo_convertido[1])
    } else {
        convertidos = Object.values(resultado.data.arquivo_convertido)
    }

    modelos_colunas = Object.entries(resultado.data.modelos_colunas)
    montaSelectsParaAssociacaoColunas(modelo, layout_colunas, convertidos, convertidosHeaders)

    modelos_colunas.forEach((el) => {
        const el_select = document.querySelector("#select_layout_coluna_" + el[1].id_layout_coluna);
        if (!el_select) return;

        const valor = el[1].descricao_coluna;

        const oldOnChange = el_select.onchange;
        el_select.onchange = null;

        $(el_select).val(valor).trigger('change.select2');

        el_select.onchange = oldOnChange;
    });
    atualizaColunas()
    load(false)
}

document.querySelector("#modelo_id").addEventListener("change", function (e) {
    e.preventDefault()
    load(true)
    const selectedOption = this.options[this.selectedIndex];
    document.querySelector("#tipoArquivoInputGroup").textContent = selectedOption.getAttribute('descr_tipo_arquivo')

    let tipo = selectedOption.getAttribute('descr_tipo_arquivo')
    var accepts = tipo == 'xlsx' ? '.xlsx, .xls' : '.' + tipo
    document.querySelector("#arquivo").setAttribute('accept', accepts)

    var arquivo = document.querySelector("#arquivo")
    if (arquivo.files[0]) {
        load(false)
        lerArquivo(arquivo, this.value)
    }

    exibe_btn_duplica_modelo(true)
    exibe_btn_e_input_arquivo(true)
    load(false)
})

function exibe_btn_e_input_arquivo(action) {
    classNameDivUpload = 'd-none'
    if (action == true && id_arquivo == "")
        classNameDivUpload = 'd-flex col-12'

    document.querySelector("#div_upload_arquivo").className = classNameDivUpload
}

function exibe_btn_duplica_modelo(action) {
    classNameDivUpload = 'd-none'
    if (action == true)
        classNameDivUpload = 'd-flex order-2'

    document.querySelector("#div-duplica-modelo").className = classNameDivUpload
}

var class_input_form_control = ' form-control '
document.querySelector("#concorrente_id").addEventListener('change', async function (e) {
    e.preventDefault()
    load(true)
    document.querySelector("#tipoArquivoInputGroup").textContent = '?'
    exibe_btn_e_input_arquivo(false)
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
    load(false)
})

document.querySelector("#id_form_modelo").addEventListener("submit", async function (e) {
    e.preventDefault()
    load(true)
    var value_input_nome = document.querySelector("#nome_modelo_modal").value
    var value_input_layout_id = document.querySelector("#layout_id").value
    var value_id_concorrente = document.querySelector("#concorrente_id").value
    id_tipo_arquivo = document.querySelector("#id_tipo_arquivo").value
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

    id_layout = value_input_layout_id

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
            document.querySelector("#tipoArquivoInputGroup").textContent = modelo.descr_tipo_arquivo;
            el_modelo.appendChild(option);

            el_modelo.value = modelo.id_modelo;

            el_modelo.disabled = true
        }
        exibe_btn_e_input_arquivo(true)
        $('#modal_novo_modelo').modal('hide')
        load(false)
    } catch (error) {
        exibe_btn_e_input_arquivo(false)
        load(false)
        alert(error)
    }
})

document.querySelector("#id_form_upload_arquivo").addEventListener('submit', async function (e) {
    e.preventDefault()
    load(true)
    exibe_btn_e_input_arquivo(false)
    exibe_btn_duplica_modelo(false)
    document.querySelector("#btn_processa_arquivo").className = 'd-flex col mt-2'

    modelo_id = document.querySelector("#modelo_id").value
    var arquivo = document.querySelector("#arquivo")

    if (!arquivo.files[0]) {
        alert('Por favor, selecione um arquivo.');
        return;
    }

    const formData = new FormData();
    formData.append('arquivo', arquivo.files[0]);
    formData.append('modelo_id', modelo_id);

    const resultado = await method_post('/conversao/uploadArquivo', formData)

    id_arquivo = resultado.data.id_arquivo

    window.location.href = '/conversao/index?id_modelo=' + modelo_id + '&id_arquivo=' + id_arquivo

    load(false)
})

async function startProgress() {
    const interval = setInterval(async () => {
        let r = await axios.get(`/status/processo`);
        let resultado = JSON.parse(r.data);
        let p = JSON.parse(resultado.progress);

        $("#barra")
            .css("width", p + "%")
            .attr("aria-valuenow", p)
            .text(p + "%");

        document.getElementById("msg-progresso").innerText = resultado.message;

        if (p >= 100) {
            $("#barra").removeClass("progress-bar-animated progress-bar-striped");
            document.getElementById("progress").className = "progress d-none";
            document.getElementById("msg-progresso").innerText = '';
            clearInterval(interval);
        }
    }, 2500);
}

function montaSelectsParaAssociacaoColunas(modelo, layout_colunas, convertidos, convertidosHeaders) {
    load(true)
    document.querySelector("#div_arquivo_convertido").className = 'overflow-auto d-flex gap-1 pb-3 div-tabela'

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
                window.location.href = '/conversao/depara?id_layout_coluna=' + select_layout_coluna.value + '&id_modelo=' + modelo.id_modelo + "&id_arquivo=" + id_arquivo;
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

            for (let i = 0; i < items.length;) {
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
                        box.style.zIndex = "10001";
                        box.style.cursor = "default";

                        let overlay = document.createElement('div');
                        overlay.style.position = "fixed";
                        overlay.style.top = "0";
                        overlay.style.left = "0";
                        overlay.style.width = "100vw";
                        overlay.style.height = "100vh";
                        overlay.style.backgroundColor = "rgba(0,0,0,0.3)";
                        overlay.style.zIndex = "10000";

                        overlay.addEventListener('click', function () {
                            document.body.removeChild(box);
                            document.body.removeChild(overlay);
                        });

                        box.addEventListener('click', function (event) {
                            event.stopPropagation();
                        });

                        document.body.appendChild(overlay);
                        document.body.appendChild(box);
                    };

                    el_tr.appendChild(el_td);
                    tbodyValores.appendChild(el_tr);

                    count++;
                }

                // Incremento controlado manualmente
                if (count === 2) {
                    i += 8; // pula 10
                    count = 0;
                } else {
                    i++; // segue normalmente
                }
            }
            div_col.appendChild(tblValores)

            div_row.appendChild(div_col)
        })
        div_container.appendChild(div_row)
        load(false)
    } else {
        load(false)
        alert('Nenhum header encontrado em convertidosHeaders')
    }
    load(false)
    // document.querySelector("#load").style.display = 'none'
}

function showPopup(text) {
    const box = document.createElement('div');
    Object.assign(box.style, {
        position: "fixed",
        top: "50%",
        left: "50%",
        transform: "translate(-50%, -50%)",
        padding: "20px",
        background: "white",
        border: "1px solid #ccc",
        boxShadow: "0 4px 8px rgba(0,0,0,0.2)",
        zIndex: "10001",
        cursor: "default"
    });
    box.innerText = text;

    const overlay = document.createElement('div');
    Object.assign(overlay.style, {
        position: "fixed",
        top: 0,
        left: 0,
        width: "100vw",
        height: "100vh",
        backgroundColor: "rgba(0,0,0,0.3)",
        zIndex: "10000"
    });

    overlay.onclick = () => {
        document.body.removeChild(box);
        document.body.removeChild(overlay);
    };

    document.body.append(overlay, box);
}

function getValue(row, header) {
    if (row == null) return [];
    const parts = Array.isArray(header) ? header : String(header).split('/').filter(Boolean);
    if (!parts.length) return Array.isArray(row) ? row : [row];

    const [first, ...rest] = parts;
    if (Array.isArray(row)) return row.flatMap(r => getValue(r, parts));

    if (typeof row !== 'object') return [];
    const next = row[first];
    return next === undefined ? getValue(row, rest) : getValue(next, rest);
}

function atualizaColunas() {
    load(true)
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
    load(false)
}

function normalizaValor(value) {
    if (value == null || value === '') return 'null';
    if (typeof value === 'boolean') return String(value);
    if (Array.isArray(value)) return '[]';
    if (typeof value === 'object') return '{}';
    if (typeof value === 'undefined') return 'undefined';
    return value;
}

async function salvaVinculacaoColunaConvetidoComLayoutColunas(select) {
    load(true)
    const id_layout_coluna = select.value
    const id_ref_col = select.id.replace("select_layout_coluna_", '')

    var descricao_coluna = document.querySelector("#input_convertido_header_" + id_ref_col).value;

    const dataJsom = {}
    dataJsom.descricao_coluna = descricao_coluna;
    dataJsom.id_layout_coluna = id_layout_coluna
    dataJsom.id_modelo = document.querySelector("#modelo_id").value
    dataJsom.id_concorrente = modelo.id_concorrente

    await method_post('/conversao/salvaVinculacaoConvertidoLayout', dataJsom)

    atualizaTabelaComDePara(select);
    load(false)
}


async function atualizaTabelaComDePara(select) {
    load(true);

    const idRef = select.id.replace("select_layout_coluna_", '');
    const tabela = document.querySelector(`#tbl_valores_convertidos_header_${idRef}`);
    const valores = Array.from(tabela?.querySelectorAll('tbody td') || [])
        .map(td => td.innerText)
        .filter(Boolean);

    const dataJson = {
        id_layout_coluna: select.value,
        id_modelo: document.querySelector('#modelo_id').value,
        descricao_coluna: document.querySelector(`#input_convertido_header_${idRef}`).value,
        valores
    };

    const resp = await method_post('/conversao/atualizaColunaDePara', dataJson);
    if (resp?.status && Array.isArray(resp.data?.valores)) {
        const tds = tabela.querySelectorAll('tbody td');
        const novos = resp.data.valores;
        for (let i = 0; i < Math.min(tds.length, novos.length); i++) {
            tds[i].innerText = novos[i];
        }
    }

    load(false);
}

async function removeColunaModelo(select, ol_value) {
    load(true)
    const id_layout_coluna = select.value
    const id_ref_col = select.id.replace("select_layout_coluna_", '')
    var descricao_coluna = document.querySelector("#input_convertido_header_" + id_ref_col).value;

    const dataJsom = {}
    dataJsom.descricao_coluna = descricao_coluna
    dataJsom.id_layout_coluna = ol_value
    dataJsom.id_modelo = document.querySelector("#modelo_id").value
    dataJsom.id_concorrente = modelo.id_concorrente

    await method_post('/conversao/removeVinculacaoConvertidoLayout', dataJsom)

    load(false)
}

async function processaArquivo() {
    load(true)
    exibe_btn_e_input_arquivo(false)

    modelo_id = document.querySelector("#modelo_id").value
    id_concorrente = document.querySelector("#concorrente_id").value

    // const urlParams = new URLSearchParams(window.location.search);
    // id_arquivo = urlParams.get('id_arquivo')

    id_arquivo = id_arquivo == "" ? window.location.href.match(/[?&]id_arquivo=(\d+)/)?.[1] : id_arquivo
    axios({
        method: 'post',
        url: '/modelo/processaArquivo',
        data: {
            id_modelo: modelo_id,
            id_concorrente: id_concorrente,
            id_layout: id_layout,
            id_tipo_arquivo: id_tipo_arquivo,
            id_arquivo: id_arquivo,
            acao: 'iniciar_migracao'
        }
    }).then(response => {
        document.getElementById("msg-progresso").innerText = '';
        document.getElementById("progress").className = "progress d-flex";

        $("#barra")
            .css("width", "0%")
            .attr("aria-valuenow", 0)
            .text('');

        if (response.data.status) startProgress();

        existeArquivoProcessado()
        load(false)
    }).catch(error => {
        load(false)
        console.error('Erro ao gerar Excel:', error);
    });


}

function baixarArquivo(tipo) {
    load(true)
    var modelo_id = document.querySelector("#modelo_id").value;

    axios({
        method: 'post',
        url: '/arquivo/downloadArquivo',
        data: {
            id_modelo: modelo_id,
            tipo: tipo
        },
        responseType: 'blob'
    }).then(response => {
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;

        // tenta extrair nome do arquivo do header
        const disposition = response.headers['content-disposition'];
        let fileName = "arquivo.xlsx";
        if (disposition && disposition.indexOf('filename=') !== -1) {
            const matches = disposition.match(/filename="?([^"]+)"?/);
            if (matches != null && matches[1]) fileName = matches[1];
        }

        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();

        window.URL.revokeObjectURL(url);
        load(false)
    }).catch(error => {
        load(false)
        console.error(error);
        alert('Erro ao baixar arquivo');
    });
}

document.querySelector("#modelo_id").addEventListener("change", function (e) {
    e.preventDefault()
    exibe_btn_duplica_modelo(true)
    const btn_duplica_modelo = document.querySelector('#btn-duplica-modelo')

    btn_duplica_modelo.onclick = () => {
        abrirModalDuplicaModelo(this.value)
    }
})

function abrirModalDuplicaModelo(id) {
    const selecionado = document.getElementById('modelo_id')
    const option = selecionado.options[selecionado.selectedIndex];
    const nome_atual_do_modelo = option.textContent;

    document.querySelector('#id-modelo-duplicar').value = id

    document.querySelector('#nome-modelo').value = nome_atual_do_modelo
    $('#exampleModal').modal('show')
}

document.querySelector('#form-duplica-modelo').addEventListener('submit', async function (e) {
    e.preventDefault()
    load(true);
    var dataJson = {}
    dataJson.id_modelo = document.querySelector('#id-modelo-duplicar').value
    dataJson.nome_modelo = document.querySelector('#nome-modelo').value

    try {
        const response = await method_post('/modelo/duplicar', dataJson);

        modelo = response.data.modelo
        modelo_id = modelo.id_modelo

        dispararEventoEmSelect(document.querySelector("#concorrente_id"), modelo.id_concorrente)

        setTimeout(() => {
            dispararEventoEmSelect(document.querySelector("#modelo_id"), modelo.id_modelo)
        }, 500)

        $('#exampleModal').modal('hide')
    } catch (error) {
        console.log('Erro ao duplicar modelo: ', error);
    }
    load(false);
})