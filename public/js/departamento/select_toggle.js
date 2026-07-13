document.addEventListener('turbo:load', inicializarDepartamentoEditToggle);
document.addEventListener('turbo:frame-load', inicializarDepartamentoEditToggle);

function inicializarDepartamentoEditToggle() {
    const contenedor = document.querySelector('.departamento-edit-page-card');

    if (!contenedor || contenedor.dataset.toggleInit === 'true') {
        return;
    }

    contenedor.dataset.toggleInit = 'true';

    contenedor
        .querySelectorAll('select[id$="_activo"]')
        .forEach((select) => crearToggleDesdeSelect(select));
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
    wrapper.className = 'departamento-activo-toggle';
    select.insertAdjacentElement('beforebegin', wrapper);
    wrapper.appendChild(select);
    select.hidden = true;

    Array.from(select.options).forEach((opcion) => {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'departamento-activo-toggle__btn';

        if (opcion.selected) {
            boton.classList.add('departamento-activo-toggle__btn--active');
        }

        boton.textContent = opcion.text;

        boton.addEventListener('click', () => {
            select.value = opcion.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));

            wrapper.querySelectorAll('.departamento-activo-toggle__btn').forEach((otro) => {
                otro.classList.toggle('departamento-activo-toggle__btn--active', otro === boton);
            });
        });

        wrapper.appendChild(boton);
    });
}
