// assets/js/personal_index_search.js

function normalizarTexto(texto) {
    return (texto || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

function initPersonalIndexSearch() {

    // 🔒 AISLAMIENTO TOTAL
    const page = document.querySelector('[data-page="personal-index"]');
    if (!page) return;

    const input = page.querySelector('#personal-search');
    const tbody = page.querySelector('.personal-index-table tbody');

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
            const nombreCrudo = tr.querySelector('.personal-nombre')?.textContent ?? '';
            const nombre = normalizarTexto(nombreCrudo);

            tr.style.display = nombre.includes(q) ? '' : 'none';
        });
    });
}

// 🔑 Turbo lifecycle seguro
document.addEventListener('turbo:load', initPersonalIndexSearch);
document.addEventListener('turbo:frame-load', initPersonalIndexSearch);
document.addEventListener('turbo:render', initPersonalIndexSearch);