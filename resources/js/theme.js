const getStoredTheme = () => {
    try {
        return localStorage.getItem('theme');
    } catch (error) {
        return null;
    }
};

const setStoredTheme = (theme) => {
    try {
        localStorage.setItem('theme', theme);
    } catch (error) {
        // Storage may be unavailable (private mode, disabled cookies).
    }
};

const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

const applyTheme = (theme) => {
    const isDark = theme === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
};

const currentTheme = () => (document.documentElement.classList.contains('dark') ? 'dark' : 'light');

applyTheme(getStoredTheme() ?? (prefersDark() ? 'dark' : 'light'));

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-theme-toggle]');

    if (! toggle) {
        return;
    }

    const next = currentTheme() === 'dark' ? 'light' : 'dark';

    setStoredTheme(next);
    applyTheme(next);
});

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    if (getStoredTheme()) {
        return;
    }

    applyTheme(event.matches ? 'dark' : 'light');
});