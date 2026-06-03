document.addEventListener(
    'turbo:load',
    inicializarPersonalShowNombramiento
);

document.addEventListener(
    'turbo:frame-load',
    inicializarPersonalShowNombramiento
);

document.addEventListener(
    'DOMContentLoaded',
    inicializarPersonalShowNombramiento
);

function inicializarPersonalShowNombramiento() {
    const personalShowContainer = document.querySelector(
        '.personal-show-card'
    );

    if (
        !personalShowContainer ||
        personalShowContainer.dataset.personalShowInitialized === 'true'
    ) {
        return;
    }

    const personalShowPreviewButtons = personalShowContainer.querySelectorAll(
        '.personal-show-nombramiento-preview-btn'
    );
    const personalShowPreviewModal = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal'
    );
    const personalShowPreviewModalFrame = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal-frame'
    );
    const personalShowPreviewModalTitle = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal-title'
    );
    const personalShowPreviewModalTipo = document.querySelector(
        '[data-pdf-preview-tipo]'
    );
    const personalShowPreviewModalFecha = document.querySelector(
        '[data-pdf-preview-fecha]'
    );
    const personalShowPreviewModalCloseBtn = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal-close-btn'
    );

    if (
        !personalShowPreviewModal ||
        !personalShowPreviewModalFrame ||
        !personalShowPreviewModalTitle ||
        !personalShowPreviewModalTipo ||
        !personalShowPreviewModalFecha
    ) {
        return;
    }

    personalShowContainer.dataset.personalShowInitialized = 'true';

    function personalShowClosePdfPreviewModal() {
        personalShowPreviewModal.hidden = true;
        personalShowPreviewModal.classList.add(
            'personal-nombramiento-pdf-preview-modal-hidden'
        );
        personalShowPreviewModalFrame.removeAttribute('src');
    }

    function personalShowOpenPdfPreviewModal(button) {
        const pdfUrl = button.dataset.pdfUrl;

        if (!pdfUrl) {
            return;
        }

        personalShowPreviewModalTitle.textContent =
            button.dataset.pdfName || 'Vista previa del dictamen';
        personalShowPreviewModalTipo.textContent =
            button.dataset.pdfTipo || '';
        personalShowPreviewModalFecha.textContent =
            button.dataset.pdfFecha || '';
        personalShowPreviewModalFrame.src =
            `${pdfUrl}#toolbar=0&navpanes=0&scrollbar=1&view=FitH`;

        personalShowPreviewModal.hidden = false;
        personalShowPreviewModal.classList.remove(
            'personal-nombramiento-pdf-preview-modal-hidden'
        );
        personalShowPreviewModalCloseBtn?.focus();
    }

    personalShowPreviewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            personalShowOpenPdfPreviewModal(button);
        });
    });

    personalShowPreviewModalCloseBtn?.addEventListener(
        'click',
        personalShowClosePdfPreviewModal
    );

    personalShowPreviewModal.addEventListener('click', (event) => {
        if (event.target === personalShowPreviewModal) {
            personalShowClosePdfPreviewModal();
        }
    });

    personalShowPreviewModal.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            personalShowClosePdfPreviewModal();
        }
    });
}
