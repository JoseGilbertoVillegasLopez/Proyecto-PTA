function initProcesoClaveIndexSearch() {

    const page = document.querySelector('[data-page="proceso-clave-index"]');
    if (!page) return;

    const input = page.querySelector('#proceso-clave-search');
    const tbody = page.querySelector('.proceso-clave-index-table tbody');

    if (!input || !tbody) return;

    if (input.dataset.searchInit === '1') return;
    input.dataset.searchInit = '1';

    input.addEventListener('input', () => {

        const q = input.value.trim().toLowerCase();
        const rows = Array.from(tbody.querySelectorAll('tr'));

        if (q.length === 0) {
            rows.forEach(tr => tr.style.display = '');
            return;
        }

        rows.forEach(tr => {

            const nombre = tr.querySelector('.proceso-clave-index-row-name')?.textContent.toLowerCase() ?? '';
            const pei = tr.children[1]?.textContent.toLowerCase() ?? '';
            const paig = tr.children[2]?.textContent.toLowerCase() ?? '';
            const meta = tr.children[3]?.textContent.toLowerCase() ?? '';

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