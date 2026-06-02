document.addEventListener(
    'turbo:load',
    inicializarPersonalEditNombramiento
);

document.addEventListener(
    'turbo:frame-load',
    inicializarPersonalEditNombramiento
);

document.addEventListener(
    'turbo:load',
    inicializarPersonalNewNombramiento
);

document.addEventListener(
    'turbo:frame-load',
    inicializarPersonalNewNombramiento
);

function inicializarPersonalEditNombramiento() {
    const personalEditContainer = document.querySelector(
        '.personal-edit-card'
    );

    if (
        !personalEditContainer ||
        personalEditContainer.dataset.personalEditInitialized === 'true'
    ) {
        return;
    }

    const personalEditForm = personalEditContainer.querySelector(
        '.personal-edit-form'
    );
    const personalEditToggleBtn = personalEditContainer.querySelector(
        '.personal-edit-toggle-upload-btn'
    );
    const personalEditUploadWrapper = personalEditContainer.querySelector(
        '.personal-edit-upload-form'
    );
    const personalEditPdfInput = personalEditContainer.querySelector(
        '.personal-edit-upload-input'
    );
    const personalEditTipoSelect = personalEditContainer.querySelector(
        '.personal-edit-upload-select'
    );
    const personalEditSaveBtn = personalEditContainer.querySelector(
        '.personal-edit-save-btn'
    );
    const personalEditPdfClientError = personalEditContainer.querySelector(
        '[data-error-for="pdf"]'
    );
    const personalEditTipoClientError = personalEditContainer.querySelector(
        '[data-error-for="tipo"]'
    );
    const personalEditValidationModal = document.querySelector(
        '.personal-edit-nombramiento-validation-modal'
    );
    const personalEditValidationModalMessage = document.querySelector(
        '.personal-edit-nombramiento-validation-modal-message'
    );
    const personalEditValidationModalCloseBtn = document.querySelector(
        '.personal-edit-nombramiento-validation-modal-close-btn'
    );
    const personalEditPdfPreviewButtons = personalEditContainer.querySelectorAll(
        '.personal-nombramiento-preview-btn'
    );
    const personalEditPdfPreviewModal = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal'
    );
    const personalEditPdfPreviewModalFrame = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal-frame'
    );
    const personalEditPdfPreviewModalTitle = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal-title'
    );
    const personalEditPdfPreviewModalTipo = document.querySelector(
        '[data-pdf-preview-tipo]'
    );
    const personalEditPdfPreviewModalFecha = document.querySelector(
        '[data-pdf-preview-fecha]'
    );
    const personalEditPdfPreviewModalCloseBtn = document.querySelector(
        '.personal-nombramiento-pdf-preview-modal-close-btn'
    );
    let personalEditFieldToFocus = null;

    if (
        !personalEditForm ||
        !personalEditToggleBtn ||
        !personalEditUploadWrapper ||
        !personalEditPdfInput ||
        !personalEditTipoSelect
    ) {
        return;
    }

    personalEditContainer.dataset.personalEditInitialized = 'true';

    function personalEditShowFieldError(errorElement, message) {
        if (!errorElement) {
            return;
        }

        errorElement.textContent = message;
        errorElement.hidden = false;
        errorElement.classList.remove(
            'personal-edit-nombramiento-client-error-hidden'
        );
    }

    function personalEditHideFieldError(errorElement) {
        if (!errorElement) {
            return;
        }

        errorElement.hidden = true;
        errorElement.classList.add(
            'personal-edit-nombramiento-client-error-hidden'
        );
    }

    function personalEditShowNombramientoModal(message) {
        if (
            !personalEditValidationModal ||
            !personalEditValidationModalMessage
        ) {
            return;
        }

        personalEditValidationModalMessage.textContent = message;
        personalEditValidationModal.hidden = false;
        personalEditValidationModal.classList.remove(
            'personal-edit-nombramiento-validation-modal-hidden'
        );

        personalEditValidationModalCloseBtn?.focus();
    }

    function personalEditCloseNombramientoModal() {
        if (!personalEditValidationModal) {
            return;
        }

        personalEditValidationModal.hidden = true;
        personalEditValidationModal.classList.add(
            'personal-edit-nombramiento-validation-modal-hidden'
        );

        personalEditFieldToFocus?.focus();
    }

    function personalEditResetNombramientoFields() {
        personalEditPdfInput.value = '';
        personalEditTipoSelect.selectedIndex = 0;
    }

    function personalEditShowValidationError(
        errorElement,
        message,
        fieldToFocus
    ) {
        personalEditFieldToFocus = fieldToFocus;
        personalEditResetNombramientoFields();
        personalEditHideFieldError(personalEditPdfClientError);
        personalEditHideFieldError(personalEditTipoClientError);
        personalEditShowFieldError(errorElement, message);
        personalEditUploadWrapper.classList.remove(
            'personal-edit-upload-form-hidden'
        );
        personalEditShowNombramientoModal(message);
        personalEditSaveBtn?.removeAttribute('disabled');
    }

    function personalEditClosePdfPreviewModal() {
        if (!personalEditPdfPreviewModal) {
            return;
        }

        personalEditPdfPreviewModal.hidden = true;
        personalEditPdfPreviewModal.classList.add(
            'personal-nombramiento-pdf-preview-modal-hidden'
        );

        if (personalEditPdfPreviewModalFrame) {
            personalEditPdfPreviewModalFrame.removeAttribute('src');
        }
    }

    function personalEditOpenPdfPreviewModal(button) {
        if (
            !personalEditPdfPreviewModal ||
            !personalEditPdfPreviewModalFrame ||
            !personalEditPdfPreviewModalTitle ||
            !personalEditPdfPreviewModalTipo ||
            !personalEditPdfPreviewModalFecha
        ) {
            return;
        }

        const pdfUrl = button.dataset.pdfUrl;

        if (!pdfUrl) {
            return;
        }

        personalEditPdfPreviewModalTitle.textContent =
            button.dataset.pdfName || 'Vista previa del dictamen';
        personalEditPdfPreviewModalTipo.textContent =
            button.dataset.pdfTipo || '';
        personalEditPdfPreviewModalFecha.textContent =
            button.dataset.pdfFecha || '';
        personalEditPdfPreviewModalFrame.src =
            `${pdfUrl}#toolbar=0&navpanes=0&scrollbar=1&view=FitH`;

        personalEditPdfPreviewModal.hidden = false;
        personalEditPdfPreviewModal.classList.remove(
            'personal-nombramiento-pdf-preview-modal-hidden'
        );
        personalEditPdfPreviewModalCloseBtn?.focus();
    }

    personalEditToggleBtn.addEventListener('click', () => {
        personalEditUploadWrapper.classList.toggle(
            'personal-edit-upload-form-hidden'
        );
    });

    personalEditValidationModalCloseBtn?.addEventListener(
        'click',
        personalEditCloseNombramientoModal
    );

    personalEditValidationModal?.addEventListener('click', (event) => {
        if (event.target === personalEditValidationModal) {
            personalEditCloseNombramientoModal();
        }
    });

    personalEditValidationModal?.addEventListener('keydown', (event) => {
        if (
            event.key === 'Escape' &&
            personalEditValidationModal?.hidden === false
        ) {
            personalEditCloseNombramientoModal();
        }
    });

    personalEditPdfPreviewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            personalEditOpenPdfPreviewModal(button);
        });
    });

    personalEditPdfPreviewModalCloseBtn?.addEventListener(
        'click',
        personalEditClosePdfPreviewModal
    );

    personalEditPdfPreviewModal?.addEventListener('click', (event) => {
        if (event.target === personalEditPdfPreviewModal) {
            personalEditClosePdfPreviewModal();
        }
    });

    personalEditPdfPreviewModal?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            personalEditClosePdfPreviewModal();
        }
    });

    personalEditForm.addEventListener('submit', (event) => {
        const personalEditExistePdf =
            personalEditPdfInput.files.length > 0;
        const personalEditExisteTipo =
            personalEditTipoSelect.value !== '';

        if (personalEditExistePdf && !personalEditExisteTipo) {
            event.preventDefault();
            event.stopImmediatePropagation();
            personalEditShowValidationError(
                personalEditTipoClientError,
                'Debes elegir un tipo de nombramiento.',
                personalEditTipoSelect
            );
            return;
        }

        if (!personalEditExistePdf && personalEditExisteTipo) {
            event.preventDefault();
            event.stopImmediatePropagation();
            personalEditShowValidationError(
                personalEditPdfClientError,
                'Debes agregar un archivo PDF.',
                personalEditPdfInput
            );
            return;
        }

        if (personalEditSaveBtn) {
            personalEditSaveBtn.disabled = true;
        }
    }, true);

    personalEditForm.addEventListener('turbo:submit-end', () => {
        personalEditSaveBtn?.removeAttribute('disabled');
    });

    personalEditPdfInput.addEventListener('change', () => {
        if (personalEditPdfInput.files.length > 0) {
            personalEditHideFieldError(personalEditPdfClientError);
        }
    });

    personalEditTipoSelect.addEventListener('change', () => {
        if (personalEditTipoSelect.value !== '') {
            personalEditHideFieldError(personalEditTipoClientError);
        }
    });

    const personalEditPdfServerError = personalEditContainer.querySelector(
        '.personal-edit-nombramiento-pdf-validation-group .personal-edit-error li'
    );
    const personalEditTipoServerError = personalEditContainer.querySelector(
        '.personal-edit-nombramiento-tipo-validation-group .personal-edit-error li'
    );

    if (personalEditPdfServerError) {
        personalEditShowValidationError(
            personalEditPdfClientError,
            personalEditPdfServerError.textContent.trim(),
            personalEditPdfInput
        );
        return;
    }

    if (personalEditTipoServerError) {
        personalEditShowValidationError(
            personalEditTipoClientError,
            personalEditTipoServerError.textContent.trim(),
            personalEditTipoSelect
        );
    }
}

function inicializarPersonalNewNombramiento() {
    const personalNewContainer = document.querySelector(
        '.personal-new-card'
    );

    if (
        !personalNewContainer ||
        personalNewContainer.dataset.personalNewInitialized === 'true'
    ) {
        return;
    }

    const personalNewForm = personalNewContainer.querySelector(
        '.personal-new-form'
    );
    const personalNewPdfInput = personalNewContainer.querySelector(
        '.personal-new-input-file'
    );
    const personalNewTipoSelect = personalNewContainer.querySelector(
        '.personal-new-nombramiento-tipo-validation-group .personal-new-select'
    );
    const personalNewSubmitBtn = personalNewContainer.querySelector(
        '.personal-new-submit-btn'
    );
    const personalNewPdfClientError = personalNewContainer.querySelector(
        '[data-new-error-for="pdf"]'
    );
    const personalNewTipoClientError = personalNewContainer.querySelector(
        '[data-new-error-for="tipo"]'
    );
    const personalNewValidationModal = document.querySelector(
        '.personal-new-nombramiento-validation-modal'
    );
    const personalNewValidationModalMessage = document.querySelector(
        '.personal-new-nombramiento-validation-modal-message'
    );
    const personalNewValidationModalCloseBtn = document.querySelector(
        '.personal-new-nombramiento-validation-modal-close-btn'
    );
    let personalNewFieldToFocus = null;

    if (
        !personalNewForm ||
        !personalNewPdfInput ||
        !personalNewTipoSelect
    ) {
        return;
    }

    personalNewContainer.dataset.personalNewInitialized = 'true';

    function personalNewShowFieldError(errorElement, message) {
        if (!errorElement) {
            return;
        }

        errorElement.textContent = message;
        errorElement.hidden = false;
        errorElement.classList.remove(
            'personal-new-nombramiento-client-error-hidden'
        );
    }

    function personalNewHideFieldError(errorElement) {
        if (!errorElement) {
            return;
        }

        errorElement.hidden = true;
        errorElement.classList.add(
            'personal-new-nombramiento-client-error-hidden'
        );
    }

    function personalNewShowNombramientoModal(message) {
        if (
            !personalNewValidationModal ||
            !personalNewValidationModalMessage
        ) {
            return;
        }

        personalNewValidationModalMessage.textContent = message;
        personalNewValidationModal.hidden = false;
        personalNewValidationModal.classList.remove(
            'personal-new-nombramiento-validation-modal-hidden'
        );
        personalNewValidationModalCloseBtn?.focus();
    }

    function personalNewCloseNombramientoModal() {
        if (!personalNewValidationModal) {
            return;
        }

        personalNewValidationModal.hidden = true;
        personalNewValidationModal.classList.add(
            'personal-new-nombramiento-validation-modal-hidden'
        );
        personalNewFieldToFocus?.focus();
    }

    function personalNewResetNombramientoFields() {
        personalNewPdfInput.value = '';
        personalNewTipoSelect.selectedIndex = 0;
    }

    function personalNewShowValidationError(
        errorElement,
        message,
        fieldToFocus
    ) {
        personalNewFieldToFocus = fieldToFocus;
        personalNewResetNombramientoFields();
        personalNewHideFieldError(personalNewPdfClientError);
        personalNewHideFieldError(personalNewTipoClientError);
        personalNewShowFieldError(errorElement, message);
        personalNewShowNombramientoModal(message);
        personalNewSubmitBtn?.removeAttribute('disabled');
    }

    personalNewValidationModalCloseBtn?.addEventListener(
        'click',
        personalNewCloseNombramientoModal
    );

    personalNewValidationModal?.addEventListener('click', (event) => {
        if (event.target === personalNewValidationModal) {
            personalNewCloseNombramientoModal();
        }
    });

    personalNewValidationModal?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            personalNewCloseNombramientoModal();
        }
    });

    personalNewForm.addEventListener('submit', (event) => {
        const personalNewExistePdf =
            personalNewPdfInput.files.length > 0;
        const personalNewExisteTipo =
            personalNewTipoSelect.value !== '';

        if (personalNewExistePdf && !personalNewExisteTipo) {
            event.preventDefault();
            event.stopImmediatePropagation();
            personalNewShowValidationError(
                personalNewTipoClientError,
                'Debes elegir un tipo de nombramiento.',
                personalNewTipoSelect
            );
            return;
        }

        if (!personalNewExistePdf && personalNewExisteTipo) {
            event.preventDefault();
            event.stopImmediatePropagation();
            personalNewShowValidationError(
                personalNewPdfClientError,
                'Debes agregar un archivo PDF.',
                personalNewPdfInput
            );
            return;
        }

        if (personalNewSubmitBtn) {
            personalNewSubmitBtn.disabled = true;
        }
    }, true);

    personalNewForm.addEventListener('turbo:submit-end', () => {
        personalNewSubmitBtn?.removeAttribute('disabled');
    });

    personalNewPdfInput.addEventListener('change', () => {
        if (personalNewPdfInput.files.length > 0) {
            personalNewHideFieldError(personalNewPdfClientError);
        }
    });

    personalNewTipoSelect.addEventListener('change', () => {
        if (personalNewTipoSelect.value !== '') {
            personalNewHideFieldError(personalNewTipoClientError);
        }
    });

    const personalNewPdfServerError = personalNewContainer.querySelector(
        '.personal-new-nombramiento-pdf-validation-group .personal-new-error li'
    );
    const personalNewTipoServerError = personalNewContainer.querySelector(
        '.personal-new-nombramiento-tipo-validation-group .personal-new-error li'
    );

    if (personalNewPdfServerError) {
        personalNewShowValidationError(
            personalNewPdfClientError,
            personalNewPdfServerError.textContent.trim(),
            personalNewPdfInput
        );
        return;
    }

    if (personalNewTipoServerError) {
        personalNewShowValidationError(
            personalNewTipoClientError,
            personalNewTipoServerError.textContent.trim(),
            personalNewTipoSelect
        );
    }
}
