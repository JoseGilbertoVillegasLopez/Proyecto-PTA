function normalizarTexto(texto) {
    return (texto || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();
}

function initProcesoClaveIndexSearch() {

    const page = document.querySelector('[data-page="proceso-clave-index"]');
    if (!page) return;

    const input = page.querySelector('#proceso-clave-search');
    const tbody = page.querySelector('.proceso-clave-index-table tbody');

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

            const nombre = normalizarTexto(tr.querySelector('.proceso-clave-index-row-name')?.textContent ?? '');
            const pei = normalizarTexto(tr.children[1]?.textContent ?? '');
            const paig = normalizarTexto(tr.children[2]?.textContent ?? '');
            const meta = normalizarTexto(tr.children[3]?.textContent ?? '');

            const coincide =
                nombre.includes(q) ||
                pei.includes(q) ||
                paig.includes(q) ||
                meta.includes(q);

            tr.style.display = coincide ? '' : 'none';

        });

    });
}

document.addEventListener('turbo:load', initProcesoClaveIndexSearch);
document.addEventListener('turbo:frame-load', initProcesoClaveIndexSearch);
document.addEventListener('turbo:render', initProcesoClaveIndexSearch);