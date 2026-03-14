function initPartidasPresupuestalesIndexSearch() {

    // 🔒 AISLAMIENTO TOTAL
    const page = document.querySelector('[data-page="partidas-presupuestales-index"]');
    if (!page) return;

    const input = page.querySelector('#partidas-presupuestales-search');
    const tbody = page.querySelector('.partidas-presupuestales-index-table tbody');

    if (!input || !tbody) return;

    // 🧠 Evita doble inicialización (Turbo)
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

            const capitulo = tr.querySelector('.partidas-presupuestales-capitulo')?.textContent.toLowerCase() ?? '';
            const partida = tr.querySelector('.partidas-presupuestales-partida')?.textContent.toLowerCase() ?? '';
            const descripcion = tr.querySelector('.partidas-presupuestales-descripcion')?.textContent.toLowerCase() ?? '';

            const coincide =
                capitulo.includes(q) ||
                partida.includes(q) ||
                descripcion.includes(q);

            tr.style.display = coincide ? '' : 'none';

        });

    });
}

document.addEventListener('turbo:load', initPartidasPresupuestalesIndexSearch);
document.addEventListener('turbo:frame-load', initPartidasPresupuestalesIndexSearch);
document.addEventListener('turbo:render', initPartidasPresupuestalesIndexSearch);