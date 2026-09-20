const sidebar = () => document.querySelector('[data-sidebar]');

const overlay = () => document.querySelector('[data-sidebar-overlay]');

const setSidebarOpen = (open) => {
    const element = sidebar();

    if (! element) {
        return;
    }

    element.setAttribute('data-open', open ? 'true' : 'false');
    overlay()?.classList.toggle('hidden', ! open);
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        setSidebarOpen(sidebar()?.getAttribute('data-open') !== 'true');

        return;
    }

    if (event.target.closest('[data-sidebar-overlay]')) {
        setSidebarOpen(false);
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setSidebarOpen(false);
    }
});