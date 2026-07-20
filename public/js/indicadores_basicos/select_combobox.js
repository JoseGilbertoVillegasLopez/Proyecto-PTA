document.addEventListener('turbo:load', inicializarIndicadoresBasicosNewCombobox);
document.addEventListener('turbo:frame-load', inicializarIndicadoresBasicosNewCombobox);
document.addEventListener('turbo:load', inicializarIndicadoresBasicosEditCombobox);
document.addEventListener('turbo:frame-load', inicializarIndicadoresBasicosEditCombobox);

function inicializarIndicadoresBasicosNewCombobox() {
    const contenedor = document.querySelector('.indicadores-basicos-new-card');

    if (!contenedor || contenedor.dataset.comboboxInit === 'true') {
        return;
    }

    contenedor.dataset.comboboxInit = 'true';

    contenedor
        .querySelectorAll('select[id$="_grupo"]')
        .forEach((select) => crearComboboxDesdeSelect(select));
}

function inicializarIndicadoresBasicosEditCombobox() {
    const contenedor = document.querySelector('.indicadores-basicos-edit-card');

    if (!contenedor || contenedor.dataset.comboboxInit === 'true') {
        return;
    }

    contenedor.dataset.comboboxInit = 'true';

    contenedor
        .querySelectorAll('select[id$="_grupo"]')
        .forEach((select) => crearComboboxDesdeSelect(select));

    contenedor
        .querySelectorAll('select[id$="_activo"]')
        .forEach((select) => crearToggleDesdeSelect(select));
}

/**
 * Convierte un <select> ya renderizado por Symfony en un combobox con buscador,
 * sin tocar el name/id original: el select se oculta pero sigue siendo el que
 * se envía al servidor. Patrón calcado de public/js/personal/select_combobox.js.
 */
function crearComboboxDesdeSelect(select) {
    if (!select || select.dataset.comboboxListo === 'true') {
        return;
    }

    select.dataset.comboboxListo = 'true';

    const wrapper = document.createElement('div');
    wrapper.className = 'indicadores-basicos-select-combobox';
    select.insertAdjacentElement('beforebegin', wrapper);
    wrapper.appendChild(select);
    select.hidden = true;

    const input = document.createElement('input');
    input.type = 'text';
    input.className = `${select.className} indicadores-basicos-select-combobox__input`.trim();
    input.autocomplete = 'off';
    input.placeholder = obtenerPlaceholder(select);
    wrapper.appendChild(input);

    const lista = document.createElement('ul');
    lista.className = 'indicadores-basicos-select-combobox__list';
    lista.hidden = true;
    wrapper.appendChild(lista);

    function obtenerOpciones() {
        return Array.from(select.options)
            .filter((opcion) => opcion.value !== '')
            .map((opcion) => ({ value: opcion.value, label: opcion.text }));
    }

    function normalizar(texto) {
        return texto
            .toLowerCase()
            .normalize('NFD')
            .replace(/\p{Diacritic}/gu, '');
    }

    function sincronizarTexto() {
        const seleccionada = select.options[select.selectedIndex];
        input.value = seleccionada && seleccionada.value !== '' ? seleccionada.text : '';
    }

    function posicionarLista() {
        const rect = input.getBoundingClientRect();
        const espacioAbajo = window.innerHeight - rect.bottom;
        const abrirArriba = espacioAbajo < 240 && rect.top > espacioAbajo;

        lista.style.left = `${rect.left}px`;
        lista.style.width = `${rect.width}px`;

        if (abrirArriba) {
            lista.style.top = 'auto';
            lista.style.bottom = `${window.innerHeight - rect.top}px`;
        } else {
            lista.style.bottom = 'auto';
            lista.style.top = `${rect.bottom}px`;
        }
    }

    function cerrarLista() {
        lista.hidden = true;
    }

    function renderizarLista(filtro) {
        const texto = normalizar(filtro || '');
        const todas = obtenerOpciones();
        const filtradas = texto === ''
            ? todas.slice(0, 30)
            : todas.filter((opcion) => normalizar(opcion.label).includes(texto)).slice(0, 30);

        lista.innerHTML = '';

        if (filtradas.length === 0) {
            const vacio = document.createElement('li');
            vacio.className = 'indicadores-basicos-select-combobox__empty';
            vacio.textContent = 'Sin resultados';
            lista.appendChild(vacio);
        } else {
            filtradas.forEach((opcion) => {
                const item = document.createElement('li');
                item.className = 'indicadores-basicos-select-combobox__item';
                item.textContent = opcion.label;
                item.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    select.value = opcion.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    cerrarLista();
                    input.blur();
                });
                lista.appendChild(item);
            });
        }

        posicionarLista();
        lista.hidden = false;
    }

    input.addEventListener('focus', () => renderizarLista(''));
    input.addEventListener('input', () => renderizarLista(input.value));
    input.addEventListener('blur', () => setTimeout(cerrarLista, 200));
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            cerrarLista();
            input.blur();
        }
    });

    select.addEventListener('change', sincronizarTexto);

    function onScrollOResize() {
        if (!lista.hidden) {
            posicionarLista();
        }
    }

    document.addEventListener('scroll', onScrollOResize, true);
    window.addEventListener('resize', onScrollOResize);

    sincronizarTexto();
}

function obtenerPlaceholder(select) {
    const primera = select.options[0];
    return primera && primera.value === '' ? primera.text : 'Buscar...';
}

/**
 * Convierte un <select> boolean (Activo/Inactivo) en un toggle de 2 botones.
 * El select original queda oculto pero sigue recibiendo el value seleccionado.
 */
function crearToggleDesdeSelect(select) {
    if (!select || select.dataset.toggleListo === 'true') {
        return;
    }

    select.dataset.toggleListo = 'true';

    const wrapper = document.createElement('div');
    wrapper.className = 'indicadores-basicos-activo-toggle';
    select.insertAdjacentElement('beforebegin', wrapper);
    wrapper.appendChild(select);
    select.hidden = true;

    Array.from(select.options).forEach((opcion) => {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'indicadores-basicos-activo-toggle__btn';

        if (opcion.selected) {
            boton.classList.add('indicadores-basicos-activo-toggle__btn--active');
        }

        boton.textContent = opcion.text;

        boton.addEventListener('click', () => {
            select.value = opcion.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));

            wrapper.querySelectorAll('.indicadores-basicos-activo-toggle__btn').forEach((otro) => {
                otro.classList.toggle('indicadores-basicos-activo-toggle__btn--active', otro === boton);
            });
        });

        wrapper.appendChild(boton);
    });
}
