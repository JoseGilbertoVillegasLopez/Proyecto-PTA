function initProcesoClaveIndexSearch() {

    const page = document.querySelector('[data-page="proceso-clave-index"]');
    if (!page) return;

    const input = page.querySelector('#proceso-clave-search');
    const tbody = page.querySelector('.proceso-clave-index__table tbody');

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

document.addEventListener('turbo:load', initProcesoClaveIndexSearch);
document.addEventListener('turbo:frame-load', initProcesoClaveIndexSearch);
document.addEventListener('turbo:render', initProcesoClaveIndexSearch);