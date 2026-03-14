function normalizarTexto(texto) {
    return (texto || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

function initProcesoEstrategicoIndexSearch() {

    const page = document.querySelector('[data-page="proceso-estrategico-index"]');
    if (!page) return;

    const input = page.querySelector('#proceso-estrategico-search');
    const tbody = page.querySelector('.proceso-estrategico-index-table tbody');

    if (!input || !tbody) return;

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

            const nombre = normalizarTexto(tr.querySelector('.proceso-estrategico-index-row-name')?.textContent ?? '');

            tr.style.display = nombre.includes(q) ? '' : 'none';

        });

    });
}

document.addEventListener('turbo:load', initProcesoEstrategicoIndexSearch);
document.addEventListener('turbo:frame-load', initProcesoEstrategicoIndexSearch);
document.addEventListener('turbo:render', initProcesoEstrategicoIndexSearch);