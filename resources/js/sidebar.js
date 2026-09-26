const sidebar = () => document.querySelector('[data-sidebar]');

const overlay = () => document.querySelector('[data-sidebar-overlay]');

const desktopViewport = window.matchMedia('(min-width: 1024px)');

const COLLAPSED_STORAGE_KEY = 'sidebar-collapsed';

const setSidebarOpen = (open) => {
    const element = sidebar();

    if (! element) {
        return;
    }

    element.setAttribute('data-open', open ? 'true' : 'false');
    overlay()?.classList.toggle('hidden', ! open);
};

const setSidebarCollapsed = (collapsed) => {
    document.documentElement.setAttribute('data-sidebar-collapsed', collapsed ? 'true' : 'false');

    try {
        localStorage.setItem(COLLAPSED_STORAGE_KEY, collapsed ? 'true' : 'false');
    } catch (error) {
        // Ignore storage access errors; the toggle still works for this page view.
    }
};

const toggleSidebar = () => {
    if (desktopViewport.matches) {
        setSidebarCollapsed(document.documentElement.getAttribute('data-sidebar-collapsed') !== 'true');

        return;
    }

    setSidebarOpen(sidebar()?.getAttribute('data-open') !== 'true');
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-sidebar-toggle]')) {
        toggleSidebar();

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
