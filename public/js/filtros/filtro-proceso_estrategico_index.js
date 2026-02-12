function initProcesoEstrategicoIndexSearch() {

    const page = document.querySelector('[data-page="proceso-estrategico-index"]');
    if (!page) return;

    const input = page.querySelector('#proceso-estrategico-search');
    const tbody = page.querySelector('.proceso-estrategico-index__table tbody');

    if (!input || !tbody) return;

    if (input.dataset.searchInit === '1') return;
    input.dataset.searchInit = '1';

    input.addEventListener('input', () => {

        const q = input.value.trim().toLowerCase();
        const rows = Array.from(tbody.querySelectorAll('tr'));

        if (q.length < 2) {
            rows.forEach(tr => tr.style.display = '');
            return;
        }

        rows.forEach(tr => {

            const texto = tr.textContent.toLowerCase();

            tr.style.display = texto.includes(q) ? '' : 'none';

        });

    });
}

document.addEventListener('turbo:load', initProcesoEstrategicoIndexSearch);
document.addEventListener('turbo:frame-load', initProcesoEstrategicoIndexSearch);
document.addEventListener('turbo:render', initProcesoEstrategicoIndexSearch);