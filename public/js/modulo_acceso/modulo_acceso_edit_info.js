function initModuloAccesoEditInfo() {
    const card = document.querySelector('[data-ma-view="edit"]');

    if (!card || card.dataset.maEditInfoReady === "true") {
        return;
    }

    card.dataset.maEditInfoReady = "true";

    const campoUrl = card.dataset.maCampoUrl;
    const campoToken = card.dataset.maCampoToken;

    card.querySelectorAll('[data-ma-field]').forEach((row) => {
        const campo = row.dataset.maField;
        const display = row.querySelector('[data-ma-field-display]');
        const input = row.querySelector('[data-ma-field-input]');
        const toggleBtn = row.querySelector('[data-ma-field-toggle]');
        const icon = toggleBtn.querySelector('i');

        let originalValue = input.value;

        function enterEditMode() {
            originalValue = input.value;
            display.hidden = true;
            input.hidden = false;
            input.focus();
            input.select();
            icon.className = 'bi bi-check-lg';
            toggleBtn.title = 'Guardar';
            row.dataset.maEditing = 'true';
        }

        function exitEditMode() {
            display.hidden = false;
            input.hidden = true;
            icon.className = 'bi bi-pencil-fill';
            toggleBtn.title = campo === 'label' ? 'Editar nombre' : 'Editar descripción';
            row.dataset.maEditing = 'false';
        }

        function showError(message) {
            row.querySelector('.ma-edit-field-error')?.remove();
            const error = document.createElement('span');
            error.className = 'ma-edit-field-error';
            error.textContent = message;
            row.appendChild(error);
            setTimeout(() => error.remove(), 3500);
        }

        function save() {
            const valor = input.value.trim();

            if (campo === 'label' && valor === '') {
                showError('El nombre no puede estar vacío.');
                input.focus();
                return;
            }

            toggleBtn.disabled = true;

            fetch(campoUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ campo, valor, _token: campoToken }),
            })
                .then((r) => r.json())
                .then((data) => {
                    toggleBtn.disabled = false;

                    if (!data.ok) {
                        showError(data.error || 'No se pudo guardar.');
                        return;
                    }

                    input.value = data.valor || '';
                    display.textContent = data.valor || (campo === 'descripcion' ? 'Sin descripción' : '');
                    exitEditMode();
                })
                .catch(() => {
                    toggleBtn.disabled = false;
                    showError('Error de conexión.');
                });
        }

        toggleBtn.addEventListener('click', () => {
            if (row.dataset.maEditing === 'true') {
                save();
            } else {
                enterEditMode();
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                input.value = originalValue;
                exitEditMode();
            } else if (event.key === 'Enter' && input.tagName === 'INPUT') {
                event.preventDefault();
                save();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', initModuloAccesoEditInfo);
document.addEventListener('turbo:load', initModuloAccesoEditInfo);
document.addEventListener('turbo:render', initModuloAccesoEditInfo);
