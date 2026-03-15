// assets/js/departamento_index_search.js

function normalizarTexto(texto) {
    return (texto || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

function initDepartamentoIndexSearch() {

    // 🔒 AISLAMIENTO TOTAL
    const page = document.querySelector('[data-page="departamento-index"]');
    if (!page) return;

    const input = page.querySelector('#departamento-search');
    const tbody = page.querySelector('.departamento-index-page-table tbody');

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
            const nombreCrudo = tr.querySelector('.departamento-index-page-name')?.textContent ?? '';
            const nombre = normalizarTexto(nombreCrudo);

            tr.style.display = nombre.includes(q) ? '' : 'none';
        });
    });
}

// 🔑 Turbo lifecycle seguro
document.addEventListener('turbo:load', initDepartamentoIndexSearch);
document.addEventListener('turbo:frame-load', initDepartamentoIndexSearch);
document.addEventListener('turbo:render', initDepartamentoIndexSearch);