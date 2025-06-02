function montaSelect(ref_id_select, ref_id_data, ref_value_data, attr, data) {
    const el_selec = document.querySelector(ref_id_select);

    el_selec.innerHTML = '';


    const optionSelecione = document.createElement('option')
    optionSelecione.textContent = 'Selecione ...'
    optionSelecione.value = 0
    el_selec.appendChild(optionSelecione)

    // console.log(typeof data)
    data.forEach(element => {
        // console.log(element)
        const option = document.createElement('option');

        if (attr !== '')
            option.setAttribute(attr, element[attr]);

        option.value = ref_id_data !== '' ? element[ref_id_data] : element;

        option.textContent = attr !== '' ? element[ref_value_data] : element;
        el_selec.appendChild(option);
    });


    if (el_selec.options.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Nenhum item encontrado';
        el_selec.appendChild(option);
    }
}