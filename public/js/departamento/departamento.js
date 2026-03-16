(function () {
    "use strict";

    const SELECTOR_SOURCE =
        '.departamento-new-page-card [data-departamento-new-edit-page-indicadores-tags-selector-source="true"], ' +
        '.departamento-edit-page-card [data-departamento-new-edit-page-indicadores-tags-selector-source="true"]';

    const ROOT_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-root";

    const CONTROL_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-control";

    const TAGS_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-tags";

    const TAG_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-tag";

    const TAG_REMOVE_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-tag-remove";

    const SEARCH_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-search";

    const DROPDOWN_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-dropdown";

    const OPTION_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-option";

    const OPTION_EMPTY_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-option-empty";

    const ACTIONS_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-actions";

    const ACTION_BUTTON_CLASS =
        "departamento-new-edit-page-indicadores-tags-selector-action-button";

    function normalizeDepartamentoNewEditPageIndicadoresSearchText(text) {
        return (text || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLowerCase()
            .trim();
    }

    function initDepartamentoNewEditPageIndicadoresTagsSelector(context) {
        const scope = context || document;
        const sourceSelects = scope.querySelectorAll(SELECTOR_SOURCE);

        sourceSelects.forEach((sourceSelect) => {
            if (!(sourceSelect instanceof HTMLSelectElement)) {
                return;
            }

            if (
                sourceSelect.dataset
                    .departamentoNewEditPageIndicadoresTagsSelectorInitialized ===
                "true"
            ) {
                syncDepartamentoNewEditPageIndicadoresTagsSelectorFromSelect(
                    sourceSelect,
                );
                return;
            }

            buildDepartamentoNewEditPageIndicadoresTagsSelector(sourceSelect);
            sourceSelect.dataset.departamentoNewEditPageIndicadoresTagsSelectorInitialized =
                "true";
        });
    }

    function buildDepartamentoNewEditPageIndicadoresTagsSelector(sourceSelect) {
        sourceSelect.classList.add(
            "departamento-new-edit-page-indicadores-tags-selector-source-hidden",
        );

        const root = document.createElement("div");
        root.className = ROOT_CLASS;
        root.setAttribute(
            "data-departamento-new-edit-page-indicadores-tags-selector-root",
            "true",
        );

        const control = document.createElement("div");
        control.className = CONTROL_CLASS;

        const tagsContainer = document.createElement("div");
        tagsContainer.className = TAGS_CLASS;

        const searchInput = document.createElement("input");
        searchInput.type = "text";
        searchInput.className = SEARCH_CLASS;
        searchInput.placeholder = "Buscar y agregar indicador básico...";
        searchInput.autocomplete = "off";

        const dropdown = document.createElement("div");
        dropdown.className = DROPDOWN_CLASS;
        dropdown.hidden = true;

        control.appendChild(tagsContainer);
        control.appendChild(searchInput);

        root.appendChild(control);
        root.appendChild(dropdown);

        sourceSelect.insertAdjacentElement("afterend", root);

        const state = {
            sourceSelect,
            root,
            control,
            tagsContainer,
            searchInput,
            dropdown,
            highlightedIndex: -1,
        };

        sourceSelect._departamentoNewEditPageIndicadoresTagsSelectorState =
            state;

        renderDepartamentoNewEditPageIndicadoresTagsSelector(state);

        control.addEventListener("click", function () {
            searchInput.focus();
            openDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state);
        });

        searchInput.addEventListener("focus", function () {
            openDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state);
        });

        searchInput.addEventListener("input", function () {
            state.highlightedIndex = -1;
            renderDepartamentoNewEditPageIndicadoresDropdown(state);
            openDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state);
        });

        searchInput.addEventListener("keydown", function (event) {
            handleDepartamentoNewEditPageIndicadoresTagsSelectorKeydown(
                event,
                state,
            );
        });

        document.addEventListener("click", function (event) {
            if (!root.contains(event.target)) {
                closeDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(
                    state,
                );
            }
        });

        sourceSelect.addEventListener("change", function () {
            renderDepartamentoNewEditPageIndicadoresTagsSelector(state);
        });
    }

    function handleDepartamentoNewEditPageIndicadoresTagsSelectorKeydown(
        event,
        state,
    ) {
        const options =
            getDepartamentoNewEditPageIndicadoresFilteredOptions(state);

        if (event.key === "ArrowDown") {
            event.preventDefault();

            if (!options.length) {
                return;
            }

            state.highlightedIndex =
                state.highlightedIndex < options.length - 1
                    ? state.highlightedIndex + 1
                    : 0;

            renderDepartamentoNewEditPageIndicadoresDropdown(state);
            openDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state);
            return;
        }

        if (event.key === "ArrowUp") {
            event.preventDefault();

            if (!options.length) {
                return;
            }

            state.highlightedIndex =
                state.highlightedIndex > 0
                    ? state.highlightedIndex - 1
                    : options.length - 1;

            renderDepartamentoNewEditPageIndicadoresDropdown(state);
            openDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state);
            return;
        }

        if (event.key === "Enter") {
            if (state.dropdown.hidden) {
                return;
            }

            event.preventDefault();

            if (!options.length) {
                return;
            }

            const option =
                state.highlightedIndex >= 0
                    ? options[state.highlightedIndex]
                    : options[0];

            selectDepartamentoNewEditPageIndicadoresOption(state, option.value);
            return;
        }

        if (event.key === "Escape") {
            closeDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state);
            return;
        }

        if (
            event.key === "Backspace" &&
            state.searchInput.value.trim() === ""
        ) {
            const selectedOptions =
                getDepartamentoNewEditPageIndicadoresSelectedOptions(
                    state.sourceSelect,
                );

            if (selectedOptions.length) {
                const lastSelected =
                    selectedOptions[selectedOptions.length - 1];
                lastSelected.selected = false;
                state.sourceSelect.dispatchEvent(
                    new Event("change", { bubbles: true }),
                );
            }
        }
    }

    function renderDepartamentoNewEditPageIndicadoresTagsSelector(state) {
        renderDepartamentoNewEditPageIndicadoresTags(state);
        renderDepartamentoNewEditPageIndicadoresDropdown(state);
    }

    function renderDepartamentoNewEditPageIndicadoresTags(state) {
        state.tagsContainer.innerHTML = "";

        const selectedOptions =
            getDepartamentoNewEditPageIndicadoresSelectedOptions(
                state.sourceSelect,
            );

        selectedOptions.forEach((option) => {
            const tag = document.createElement("span");
            tag.className = TAG_CLASS;

            const tagText = document.createElement("span");
            tagText.textContent = option.text;

            const removeButton = document.createElement("button");
            removeButton.type = "button";
            removeButton.className = TAG_REMOVE_CLASS;
            removeButton.setAttribute("aria-label", "Quitar indicador");
            removeButton.innerHTML = "&times;";

            removeButton.addEventListener("click", function (event) {
                event.stopPropagation();
                option.selected = false;
                state.sourceSelect.dispatchEvent(
                    new Event("change", { bubbles: true }),
                );
                state.searchInput.focus();
                openDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(
                    state,
                );
            });

            tag.appendChild(tagText);
            tag.appendChild(removeButton);

            state.tagsContainer.appendChild(tag);
        });
    }

    function renderDepartamentoNewEditPageIndicadoresDropdown(state) {
        state.dropdown.innerHTML = "";

        const actionsContainer = document.createElement("div");
        actionsContainer.className = ACTIONS_CLASS;

        const selectAllButton = document.createElement("button");
        selectAllButton.type = "button";
        selectAllButton.className = ACTION_BUTTON_CLASS;
        selectAllButton.textContent = "Seleccionar todos";

        selectAllButton.addEventListener("click", function () {
            selectAllDepartamentoNewEditPageIndicadoresOptions(state);
        });

        const clearAllButton = document.createElement("button");
        clearAllButton.type = "button";
        clearAllButton.className = ACTION_BUTTON_CLASS;
        clearAllButton.textContent = "Limpiar selección";

        clearAllButton.addEventListener("click", function () {
            clearAllDepartamentoNewEditPageIndicadoresOptions(state);
        });

        actionsContainer.appendChild(selectAllButton);
        actionsContainer.appendChild(clearAllButton);
        state.dropdown.appendChild(actionsContainer);

        const filteredOptions =
            getDepartamentoNewEditPageIndicadoresFilteredOptions(state);

        if (!filteredOptions.length) {
            const empty = document.createElement("div");
            empty.className = OPTION_EMPTY_CLASS;
            empty.textContent = "No se encontraron indicadores disponibles.";
            state.dropdown.appendChild(empty);
            return;
        }

        filteredOptions.forEach((option, index) => {
            const optionElement = document.createElement("button");
            optionElement.type = "button";
            optionElement.className = OPTION_CLASS;
            optionElement.textContent = option.text;

            if (index === state.highlightedIndex) {
                optionElement.classList.add(
                    "departamento-new-edit-page-indicadores-tags-selector-option--highlighted",
                );
            }

            optionElement.addEventListener("click", function () {
                selectDepartamentoNewEditPageIndicadoresOption(
                    state,
                    option.value,
                );
            });

            state.dropdown.appendChild(optionElement);
        });
    }

    function selectAllDepartamentoNewEditPageIndicadoresOptions(state) {
        Array.from(state.sourceSelect.options).forEach(function (option) {
            option.selected = true;
        });

        state.searchInput.value = "";
        state.highlightedIndex = -1;

        state.sourceSelect.dispatchEvent(
            new Event("change", { bubbles: true }),
        );
        closeDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state);
    }

    function clearAllDepartamentoNewEditPageIndicadoresOptions(state) {
        Array.from(state.sourceSelect.options).forEach(function (option) {
            option.selected = false;
        });

        state.searchInput.value = "";
        state.highlightedIndex = -1;

        state.sourceSelect.dispatchEvent(
            new Event("change", { bubbles: true }),
        );
        openDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state);
        state.searchInput.focus();
    }

    function selectDepartamentoNewEditPageIndicadoresOption(state, value) {
        const option = Array.from(state.sourceSelect.options).find(
            function (item) {
                return item.value === value;
            },
        );

        if (!option) {
            return;
        }

        option.selected = true;
        state.searchInput.value = "";
        state.highlightedIndex = -1;

        state.sourceSelect.dispatchEvent(
            new Event("change", { bubbles: true }),
        );

        openDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state);
        state.searchInput.focus();
    }

    function getDepartamentoNewEditPageIndicadoresSelectedOptions(
        sourceSelect,
    ) {
        return Array.from(sourceSelect.options).filter(function (option) {
            return option.selected;
        });
    }

    function getDepartamentoNewEditPageIndicadoresFilteredOptions(state) {
        const query = normalizeDepartamentoNewEditPageIndicadoresSearchText(
            state.searchInput.value,
        );

        return Array.from(state.sourceSelect.options).filter(function (option) {
            if (option.selected) {
                return false;
            }

            if (query === "") {
                return true;
            }

            const optionTextNormalized =
                normalizeDepartamentoNewEditPageIndicadoresSearchText(
                    option.text,
                );

            return optionTextNormalized.includes(query);
        });
    }

    function openDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(state) {
        state.dropdown.hidden = false;
        renderDepartamentoNewEditPageIndicadoresDropdown(state);
    }

    function closeDepartamentoNewEditPageIndicadoresTagsSelectorDropdown(
        state,
    ) {
        state.dropdown.hidden = true;
        state.highlightedIndex = -1;
    }

    function syncDepartamentoNewEditPageIndicadoresTagsSelectorFromSelect(
        sourceSelect,
    ) {
        const state =
            sourceSelect._departamentoNewEditPageIndicadoresTagsSelectorState;

        if (!state) {
            return;
        }

        renderDepartamentoNewEditPageIndicadoresTagsSelector(state);
    }

    document.addEventListener("DOMContentLoaded", function () {
        initDepartamentoNewEditPageIndicadoresTagsSelector(document);
    });

    document.addEventListener("turbo:load", function () {
        initDepartamentoNewEditPageIndicadoresTagsSelector(document);
    });

    document.addEventListener("turbo:frame-load", function (event) {
        const frame = event.target;

        if (!(frame instanceof HTMLElement)) {
            return;
        }

        initDepartamentoNewEditPageIndicadoresTagsSelector(frame);
    });
})();
