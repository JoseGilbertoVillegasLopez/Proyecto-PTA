// assets/js/puesto_index_search.js

function normalizarTexto(texto) {
    return (texto || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

function initPuestoIndexSearch() {

    // 🔒 AISLAMIENTO TOTAL
    const page = document.querySelector('[data-page="puesto-index"]');
    if (!page) return;

    const input = page.querySelector('#puesto-search');
    const tbody = page.querySelector('.puesto-index-page-table tbody');

    if (!input || !tbody) return;

    // 🧠 Evita doble inicialización (Turbo)
    if (input.dataset.searchInit === '1') return;
    input.dataset.searchInit = '1';

    input.addEventListener('input', () => {
        const q = normalizarTexto(input.value);
        const rows = Array.from(tbody.querySelectorAll('tr'));

        if (q.length === 0) {
            rows.forEach(tr => tr.style.display = '');
            return;
        }

        rows.forEach(tr => {
            const nombreCrudo = tr.querySelector('.puesto-index-page-name')?.textContent ?? '';
            const nombre = normalizarTexto(nombreCrudo);

            tr.style.display = nombre.includes(q) ? '' : 'none';
        });
    });
}

// 🔑 Turbo lifecycle seguro
document.addEventListener('turbo:load', initPuestoIndexSearch);
document.addEventListener('turbo:frame-load', initPuestoIndexSearch);
document.addEventListener('turbo:render', initPuestoIndexSearch);