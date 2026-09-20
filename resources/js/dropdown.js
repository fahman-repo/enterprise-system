const closeDropdowns = (except = null) => {
    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        if (dropdown === except) {
            return;
        }

        dropdown.setAttribute('data-open', 'false');
        dropdown.querySelector('[data-dropdown-trigger]')?.setAttribute('aria-expanded', 'false');
        dropdown.querySelector('[data-dropdown-menu]')?.setAttribute('hidden', '');
    });
};

const openDropdown = (dropdown) => {
    dropdown.setAttribute('data-open', 'true');
    dropdown.querySelector('[data-dropdown-trigger]')?.setAttribute('aria-expanded', 'true');
    dropdown.querySelector('[data-dropdown-menu]')?.removeAttribute('hidden');
};

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-dropdown-trigger]');

    if (trigger) {
        const dropdown = trigger.closest('[data-dropdown]');
        const isOpen = dropdown?.getAttribute('data-open') === 'true';

        closeDropdowns(dropdown);

        if (dropdown && ! isOpen) {
            openDropdown(dropdown);
        }

        return;
    }

    if (! event.target.closest('[data-dropdown-menu]')) {
        closeDropdowns();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeDropdowns();
    }
});

closeDropdowns();