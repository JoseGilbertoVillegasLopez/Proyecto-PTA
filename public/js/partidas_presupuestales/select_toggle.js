document.addEventListener('turbo:load', inicializarPartidasPresupuestalesEditToggle);
document.addEventListener('turbo:frame-load', inicializarPartidasPresupuestalesEditToggle);

function inicializarPartidasPresupuestalesEditToggle() {
    const contenedor = document.querySelector('.partidas-presupuestales-edit-card');

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
    wrapper.className = 'partidas-presupuestales-activo-toggle';
    select.insertAdjacentElement('beforebegin', wrapper);
    wrapper.appendChild(select);
    select.hidden = true;

    Array.from(select.options).forEach((opcion) => {
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'partidas-presupuestales-activo-toggle__btn';

        if (opcion.selected) {
            boton.classList.add('partidas-presupuestales-activo-toggle__btn--active');
        }

        boton.textContent = opcion.text;

        boton.addEventListener('click', () => {
            select.value = opcion.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));

            wrapper.querySelectorAll('.partidas-presupuestales-activo-toggle__btn').forEach((otro) => {
                otro.classList.toggle('partidas-presupuestales-activo-toggle__btn--active', otro === boton);
            });
        });

        wrapper.appendChild(boton);
    });
}
