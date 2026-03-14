// assets/js/indicadores_basicos_index_search.js

function initIndicadoresBasicosIndexSearch() {

    // 🔒 AISLAMIENTO TOTAL
    const page = document.querySelector('[data-page="indicadores-basicos-index"]');
    if (!page) return;

    const input = page.querySelector('#indicadores-basicos-search');
    const tbody = page.querySelector('.indicadores-basicos-index-table tbody');

    if (!input || !tbody) return;

    // 🧠 Evita doble inicialización con Turbo
    if (input.dataset.searchInit === '1') return;
    input.dataset.searchInit = '1';

    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // 🔎 Mostrar todo si está vacío
        if (q.length === 0) {
            rows.forEach(tr => tr.style.display = '');
            return;
        }

        rows.forEach(tr => {
            const nombre = tr.querySelector('.indicadores-basicos-nombre')?.textContent.toLowerCase() ?? '';
            const formula = tr.querySelector('.indicadores-basicos-formula')?.textContent.toLowerCase() ?? '';
            const observaciones = tr.querySelector('.indicadores-basicos-observaciones')?.textContent.toLowerCase() ?? '';

            const coincide =
                nombre.includes(q) ||
                formula.includes(q) ||
                observaciones.includes(q);

            tr.style.display = coincide ? '' : 'none';
        });
    });
}

// 🔑 Turbo lifecycle seguro
document.addEventListener('turbo:load', initIndicadoresBasicosIndexSearch);
document.addEventListener('turbo:frame-load', initIndicadoresBasicosIndexSearch);
document.addEventListener('turbo:render', initIndicadoresBasicosIndexSearch);