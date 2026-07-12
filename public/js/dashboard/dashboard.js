function closeDashboardMenu() {
    document.body.classList.remove('pta-dashboard-open');
    const toggleBtn = document.querySelector('[data-dashboard-toggle]');
    if (toggleBtn) {
        toggleBtn.setAttribute('aria-expanded', 'false');
    }
}

document.addEventListener('click', function (event) {
    const toggle = event.target.closest('[data-dashboard-toggle]');
    if (toggle) {
        const isOpen = document.body.classList.toggle('pta-dashboard-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        return;
    }

    const shouldClose = event.target.closest('[data-dashboard-overlay]') || event.target.closest('.pta-dashboard-link');
    if (shouldClose && document.body.classList.contains('pta-dashboard-open')) {
        closeDashboardMenu();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && document.body.classList.contains('pta-dashboard-open')) {
        closeDashboardMenu();
    }
});
