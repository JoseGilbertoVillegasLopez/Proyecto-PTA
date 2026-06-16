(function () {
    'use strict';

    function bootDeptSelector(context) {
        const container = (context || document).querySelector('[data-ib-dept-group]');
        if (!container) return;
        if (container.dataset.ibDeptInit === '1') return;
        container.dataset.ibDeptInit = '1';

        const select    = container.querySelector('[data-ib-dept-select]');
        const listResp  = container.querySelector('[data-ib-dept-list="resp"]');
        const listAvail = container.querySelector('[data-ib-dept-list="avail"]');
        const countResp  = container.querySelector('[data-ib-dept-count="resp"]');
        const countAvail = container.querySelector('[data-ib-dept-count="avail"]');

        if (!select || !listResp || !listAvail) return;

        function updateCounts() {
            if (countResp)  countResp.textContent  = listResp.querySelectorAll('.ib-dept-card').length;
            if (countAvail) countAvail.textContent = listAvail.querySelectorAll('.ib-dept-card').length;
        }

        function syncOption(id, selected) {
            const opt = select.querySelector(`option[value="${id}"]`);
            if (opt) opt.selected = selected;
        }

        function makeCard(id, nombre, isResp) {
            const card = document.createElement('div');
            card.className    = 'ib-dept-card';
            card.dataset.id   = id;

            const span = document.createElement('span');
            span.className   = 'ib-dept-card-nombre';
            span.textContent = nombre;

            const btn = document.createElement('button');
            btn.type = 'button';

            if (isResp) {
                btn.className = 'ib-dept-card-btn ib-dept-card-btn--remove';
                btn.title     = 'Quitar';
                btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                btn.addEventListener('click', () => moveToAvail(id));
            } else {
                btn.className = 'ib-dept-card-btn ib-dept-card-btn--add';
                btn.title     = 'Agregar';
                btn.innerHTML = '<i class="bi bi-plus-lg"></i>';
                btn.addEventListener('click', () => moveToResp(id, nombre));
            }

            const actions = document.createElement('div');
            actions.className = 'ib-dept-card-actions';
            actions.appendChild(btn);

            card.appendChild(span);
            card.appendChild(actions);
            return card;
        }

        function moveToResp(id, nombre) {
            const existing = listAvail.querySelector(`.ib-dept-card[data-id="${id}"]`);
            if (existing) existing.remove();
            listResp.appendChild(makeCard(id, nombre, true));
            syncOption(id, true);
            updateCounts();
        }

        function moveToAvail(id) {
            const opt    = select.querySelector(`option[value="${id}"]`);
            const nombre = opt ? opt.textContent.trim() : id;
            const existing = listResp.querySelector(`.ib-dept-card[data-id="${id}"]`);
            if (existing) existing.remove();
            listAvail.appendChild(makeCard(id, nombre, false));
            syncOption(id, false);
            updateCounts();
        }

        // Construir UI desde el select oculto
        select.querySelectorAll('option').forEach(opt => {
            if (!opt.value) return;
            if (opt.selected) {
                listResp.appendChild(makeCard(opt.value, opt.textContent.trim(), true));
            } else {
                listAvail.appendChild(makeCard(opt.value, opt.textContent.trim(), false));
            }
        });

        updateCounts();
    }

    document.addEventListener('DOMContentLoaded', () => bootDeptSelector(document));
    document.addEventListener('turbo:load',       () => bootDeptSelector(document));
    document.addEventListener('turbo:frame-load', e => {
        if (e.target.id === 'content') bootDeptSelector(e.target);
    });

})();
