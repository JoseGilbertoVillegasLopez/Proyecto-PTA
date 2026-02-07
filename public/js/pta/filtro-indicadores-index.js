// assets/js/indicadores_basicos_index_search.js

function initIndicadoresBasicosIndexSearch() {

    // 🔒 AISLAMIENTO TOTAL (igual que Personal)
    const page = document.querySelector('[data-page="indicadores-basicos-index"]');
    if (!page) return;

    const input = page.querySelector('#indicadores-basicos-search');
    const tbody = page.querySelector('.indicadores-basicos-index__table tbody');

    if (!input || !tbody) return;

    // 🧠 Evita doble inicialización con Turbo
    if (input.dataset.searchInit === '1') return;
    input.dataset.searchInit = '1';

    input.addEventListener('input', () => {
        const q = input.value.trim().toLowerCase();
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // 🔎 Muestra todo si hay menos de 2 caracteres
        if (q.length < 2) {
            rows.forEach(tr => tr.style.display = '');
            return;
        }

        rows.forEach(tr => {
            // 📌 Primera columna = nombre del indicador
            const nombre = tr.querySelector('td')?.textContent.toLowerCase() ?? '';
            tr.style.display = nombre.includes(q) ? '' : 'none';
        });
    });
}

// 🔑 Turbo lifecycle seguro (MISMO patrón que Personal)
document.addEventListener('turbo:load', initIndicadoresBasicosIndexSearch);
document.addEventListener('turbo:frame-load', initIndicadoresBasicosIndexSearch);
document.addEventListener('turbo:render', initIndicadoresBasicosIndexSearch);
