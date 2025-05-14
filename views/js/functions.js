$(document).ready(function () {

    let params = new URLSearchParams(window.location.search);
    let param = params.get('pagina');

    if (param !== null && param !== '') {
        param = param.replace('_', '/')
        navigate(param + '.php')
    }

    setTimeout(function () {
        window.scrollTo(0, 0);
        $("#overlay").css("display", "none");
    }, 500);
});


async function logout() {
    const response = await method_get('./app/Auth/logout.php')

    if (response.status == true) {
        location.reload()
    } else {
        return;
    }
}

function navigate(arquivo) {
    var dir_padrao = './views/'
    fetch(dir_padrao + arquivo)
        .then(response => {
            if (!response.ok) throw new Error('Erro ao carregar conteúdo');
            return response.text();
        })
        .then(html => {
            const url = new URL(window.location);
            arquivo = arquivo.replace('/', '_')
            arquivo = arquivo.replace('.php', '')
            url.searchParams.set('pagina', arquivo);
            history.pushState({ pagina: arquivo }, '', url);
            document.getElementById('conteudo').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('conteudo').innerHTML = '<p>Erro ao carregar conteúdo.</p>';
            console.error(error);
        });
}
