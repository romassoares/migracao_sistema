var counts = [];

$(document).ready(async function () {
    atualizarCounts(counts);
});

document.querySelector('form').addEventListener('submit', function (e) {
    e.preventDefault();
    const formData = new FormData(this);
    axios.post('/vista/store', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }).then(function (response) {
        // console.log(response.data);
        atualizarCounts(response.data.counts);
        alert('Arquivo enviado com sucesso!');
    }).catch(function (error) {
        console.error(error);
        alert('Erro ao enviar o arquivo.');
    });
});


function atualizarCounts(counts) {
    if (!counts) return;

    const listItems = Object.entries(counts)
        .map(([tabela, total]) => {
            let listItem = `<li style="color:black;">${tabela}: ${total}`;

            if (total > 0) {
                listItem += ` <button class="btn-detalhes" data-tabela="${tabela}">Ver Detalhes</button>`;
                listItem += ` <a href="/vista/editarModelo?tabela=${tabela}">Editar Modelo</a>`;
            }

            return listItem + '</li>';
        })
        .join('');

    const html = '<h3>Contagem de Registros Migrados:</h3><ul>' + listItems + '</ul>';

    $('#counts').html(html);

    $('.btn-detalhes').on('click', function () {

        const tabela = $(this).data('tabela');

        const result = method_post('/vista/processarArquivo', { tabela: tabela });

        if (result.data.status) {
            console.log(result.data);
        }
    });
}