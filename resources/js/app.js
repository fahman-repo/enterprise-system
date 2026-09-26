import './bootstrap';
import './theme';
import './dropdown';
import './sidebar';

if (document.querySelector('[data-org-chart]')) {
    import('./org-chart');
}

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();