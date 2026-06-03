document.addEventListener('turbo:load', inicializarHistorialNombramientos);
document.addEventListener('turbo:frame-load', inicializarHistorialNombramientos);
document.addEventListener('DOMContentLoaded', inicializarHistorialNombramientos);

function inicializarHistorialNombramientos() {
    const container = document.querySelector('.nombramiento-historial');

    if (
        !container
        || container.dataset.nombramientoHistorialInitialized === 'true'
    ) {
        return;
    }

    const form = container.querySelector('.nombramiento-historial__filters');
    const departamentoSelect = container.querySelector(
        '#nombramiento-departamento'
    );
    const puestoSelect = container.querySelector('#nombramiento-puesto');
    const pdfPreviewButtons = container.querySelectorAll(
        '.nombramiento-historial__pdf-preview-btn'
    );
    const pdfPreviewModal = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal'
    );
    const pdfPreviewModalFrame = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal-frame'
    );
    const pdfPreviewModalTitle = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal-title'
    );
    const pdfPreviewModalTipo = document.querySelector(
        '[data-pdf-preview-tipo]'
    );
    const pdfPreviewModalFecha = document.querySelector(
        '[data-pdf-preview-fecha]'
    );
    const pdfPreviewModalCloseBtn = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal-close-btn'
    );

    if (!form || !departamentoSelect || !puestoSelect) {
        return;
    }

    container.dataset.nombramientoHistorialInitialized = 'true';

    let puestosPorDepartamento = {};

    try {
        puestosPorDepartamento = JSON.parse(
            form.dataset.puestosPorDepartamento || '{}'
        );
    } catch (error) {
        console.error(
            'No fue posible cargar los puestos por departamento.',
            error
        );
    }

    function actualizarPuestos(departamentoId, puestoSeleccionado = '') {
        const puestos = puestosPorDepartamento[departamentoId] || [];

        puestoSelect.replaceChildren();
        puestoSelect.append(new Option(
            puestos.length > 0
                ? 'Todos los puestos'
                : 'No hay puestos disponibles',
            ''
        ));

        puestos.forEach((puesto) => {
            puestoSelect.append(new Option(puesto.nombre, puesto.id));
        });

        puestoSelect.disabled = puestos.length === 0;
        puestoSelect.value = String(puestoSeleccionado);
    }

    actualizarPuestos(departamentoSelect.value, puestoSelect.value);

    departamentoSelect.addEventListener('change', () => {
        actualizarPuestos(departamentoSelect.value);
    });

    form.addEventListener('submit', () => {
        if (puestoSelect.options.length > 1) {
            puestoSelect.disabled = false;
        }
    });

    function closePdfPreviewModal() {
        if (!pdfPreviewModal) {
            return;
        }

        pdfPreviewModal.hidden = true;
        pdfPreviewModal.classList.add(
            'personal-nombramiento-pdf-preview-modal-hidden'
        );
        pdfPreviewModalFrame?.removeAttribute('src');
    }

    function openPdfPreviewModal(button) {
        if (
            !pdfPreviewModal
            || !pdfPreviewModalFrame
            || !pdfPreviewModalTitle
            || !pdfPreviewModalTipo
            || !pdfPreviewModalFecha
        ) {
            return;
        }

        pdfPreviewModalTitle.textContent =
            button.dataset.pdfName || 'Vista previa del dictamen';
        pdfPreviewModalTipo.textContent = button.dataset.pdfTipo || '';
        pdfPreviewModalFecha.textContent = button.dataset.pdfFecha || '';
        pdfPreviewModalFrame.src =
            `${button.dataset.pdfUrl}#toolbar=0&navpanes=0&scrollbar=1&view=FitH`;

        pdfPreviewModal.hidden = false;
        pdfPreviewModal.classList.remove(
            'personal-nombramiento-pdf-preview-modal-hidden'
        );
        pdfPreviewModalCloseBtn?.focus();
    }

    pdfPreviewButtons.forEach((button) => {
        button.addEventListener('click', () => openPdfPreviewModal(button));
    });

    pdfPreviewModalCloseBtn?.addEventListener('click', closePdfPreviewModal);

    pdfPreviewModal?.addEventListener('click', (event) => {
        if (event.target === pdfPreviewModal) {
            closePdfPreviewModal();
        }
    });

    pdfPreviewModal?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePdfPreviewModal();
        }
    });
}
