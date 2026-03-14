// assets/js/indicadores_basicos_index_search.js

function normalizarTexto(texto) {
    return (texto || '')
        .normalize('NFD')                   // separa letras y acentos
        .replace(/[\u0300-\u036f]/g, '')    // elimina acentos
        .toLowerCase()
        .replace(/\s+/g, ' ')               // colapsa espacios múltiples
        .trim();
}

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
        const q = normalizarTexto(input.value);
        const rows = Array.from(tbody.querySelectorAll('tr'));

        // 🔎 Mostrar todo si está vacío
        if (q.length === 0) {
            rows.forEach(tr => tr.style.display = '');
            return;
        }

        rows.forEach(tr => {
            const nombreCrudo = tr.querySelector('.indicadores-basicos-nombre')?.textContent ?? '';
            const nombre = normalizarTexto(nombreCrudo);

            const coincide = nombre.includes(q);

            tr.style.display = coincide ? '' : 'none';
        });
    });
}

// 🔑 Turbo lifecycle seguro
document.addEventListener('turbo:load', initIndicadoresBasicosIndexSearch);
document.addEventListener('turbo:frame-load', initIndicadoresBasicosIndexSearch);
document.addEventListener('turbo:render', initIndicadoresBasicosIndexSearch);