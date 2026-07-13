document.addEventListener('turbo:load', inicializarPtaIndexCombobox);
document.addEventListener('turbo:frame-load', inicializarPtaIndexCombobox);

function inicializarPtaIndexCombobox() {
    const contenedor = document.querySelector('.pta-encabezado-index-card');

    if (!contenedor || contenedor.dataset.comboboxInit === 'true') {
        return;
    }

    contenedor.dataset.comboboxInit = 'true';

    contenedor
        .querySelectorAll('select[name="anio"], select[name="departamento"], select[name="puesto"]')
        .forEach((select) => crearComboboxDesdeSelect(select));
}

/**
 * Reemplaza un <select> nativo por un dropdown propio (botón + lista clicable,
 * sin buscador de texto) para evitar el picker nativo de Android/iOS, que no
 * se puede estilizar. El select original queda oculto pero sigue siendo el
 * que dispara el onchange de filtro ya existente.
 */
function crearComboboxDesdeSelect(select) {
    if (!select || select.dataset.comboboxListo === 'true') {
        return;
    }

    select.dataset.comboboxListo = 'true';

    const wrapper = document.createElement('div');
    wrapper.className = 'pta-select-combobox';
    select.insertAdjacentElement('beforebegin', wrapper);
    wrapper.appendChild(select);
    select.hidden = true;

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'pta-select-combobox__trigger';

    const textoSpan = document.createElement('span');
    textoSpan.className = 'pta-select-combobox__trigger-text';
    trigger.appendChild(textoSpan);

    const flecha = document.createElement('i');
    flecha.className = 'bi bi-chevron-down pta-select-combobox__trigger-arrow';
    trigger.appendChild(flecha);

    wrapper.appendChild(trigger);

    const lista = document.createElement('ul');
    lista.className = 'pta-select-combobox__list';
    lista.hidden = true;
    wrapper.appendChild(lista);

    function sincronizarTexto() {
        const seleccionada = select.options[select.selectedIndex];
        textoSpan.textContent = seleccionada ? seleccionada.text : '';
    }

    function posicionarLista() {
        const rect = trigger.getBoundingClientRect();
        const espacioAbajo = window.innerHeight - rect.bottom;
        const abrirArriba = espacioAbajo < 260 && rect.top > espacioAbajo;

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
        trigger.classList.remove('pta-select-combobox__trigger--open');
        document.removeEventListener('mousedown', onClickFuera);
    }

    function onClickFuera(event) {
        if (!wrapper.contains(event.target)) {
            cerrarLista();
        }
    }

    function renderizarLista() {
        lista.innerHTML = '';

        Array.from(select.options).forEach((opcion) => {
            const item = document.createElement('li');
            item.className = 'pta-select-combobox__item';

            if (opcion.selected) {
                item.classList.add('pta-select-combobox__item--selected');
            }

            item.textContent = opcion.text;
            item.addEventListener('mousedown', (event) => {
                event.preventDefault();
                select.value = opcion.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                cerrarLista();
            });

            lista.appendChild(item);
        });

        posicionarLista();
    }

    function abrirLista() {
        renderizarLista();
        lista.hidden = false;
        trigger.classList.add('pta-select-combobox__trigger--open');
        document.addEventListener('mousedown', onClickFuera);
    }

    trigger.addEventListener('click', () => {
        if (lista.hidden) {
            abrirLista();
        } else {
            cerrarLista();
        }
    });

    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            cerrarLista();
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
