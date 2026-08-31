function manualExpandFromHash() {
    const page = document.querySelector('[data-page="manual-modulo"]');
    if (!page) {
        return;
    }

    const hash = window.location.hash;
    if (!hash) {
        return;
    }

    const item = document.querySelector(hash);
    if (!item) {
        return;
    }

    const panel = item.querySelector(".accordion-collapse");
    const toggle = item.querySelector(".accordion-button");
    if (!panel || typeof bootstrap === "undefined") {
        return;
    }

    if (!panel.classList.contains("show")) {
        new bootstrap.Collapse(panel, { show: true });
    }
    if (toggle) {
        toggle.classList.remove("collapsed");
    }

    item.scrollIntoView({ behavior: "smooth", block: "start" });
}

function initManualModulo() {
    const page = document.querySelector('[data-page="manual-modulo"]');
    if (!page || page.dataset.manualReady === "true") {
        return;
    }
    page.dataset.manualReady = "true";

    manualExpandFromHash();
}

document.addEventListener("DOMContentLoaded", initManualModulo);
document.addEventListener("turbo:load", initManualModulo);
document.addEventListener("turbo:render", initManualModulo);
window.addEventListener("hashchange", manualExpandFromHash);
