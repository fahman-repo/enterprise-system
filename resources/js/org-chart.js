import { OrgChart } from 'd3-org-chart';

const TYPE_COLORS = {
    root: '#0f172a',
    division: '#2563eb',
    department: '#0891b2',
    unit: '#7c3aed',
    employee: '#16a34a',
};

const MODE_ACTIVE_CLASSES = 'bg-primary text-primary-foreground shadow-xs hover:bg-primary/90';
const MODE_INACTIVE_CLASSES = 'border border-input bg-background shadow-xs hover:bg-accent hover:text-accent-foreground';
const MODE_CLASSES = (MODE_ACTIVE_CLASSES + ' ' + MODE_INACTIVE_CLASSES).split(' ');

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[char]);
}

function initials(name) {
    return String(name ?? '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0].toUpperCase())
        .join('');
}

function nodeHtml(d) {
    const data = d.data;
    const accent = TYPE_COLORS[data.type] ?? TYPE_COLORS.employee;
    const highlighted = data._highlighted || data._upToTheRootHighlighted;
    const border = highlighted ? '2px solid #E27396' : '1px solid #e5e7eb';
    const cardStyle = 'box-sizing:border-box;position:relative;display:flex;flex-direction:column;justify-content:center;gap:6px;'
        + `width:100%;height:100%;padding:12px 14px 12px 16px;background:#fff;border:${border};border-radius:8px;`
        + (data.inactive ? 'opacity:.55;' : '');

    const accentBar = `<div style="position:absolute;top:0;left:0;width:100%;height:4px;border-radius:8px 8px 0 0;background:${accent};"></div>`;

    const avatar = data.type === 'employee'
        ? (data.avatarUrl
            ? `<img src="${escapeHtml(data.avatarUrl)}" alt="" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;">`
            : `<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;`
                + `background:${accent};color:#fff;font-size:11px;font-weight:600;flex-shrink:0;">${escapeHtml(initials(data.name))}</span>`)
        : '';

    const code = data.code
        ? `<span style="display:inline-flex;align-items:center;padding:1px 6px;border:1px solid #e5e7eb;border-radius:4px;`
            + `font-family:monospace;font-size:11px;color:#6b7280;">${escapeHtml(data.code)}</span>`
        : '';

    const badge = data._directSubordinates > 0
        ? `<span style="display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:18px;padding:0 6px;`
            + `border-radius:999px;background:${accent};color:#fff;font-size:10px;font-weight:600;">+${escapeHtml(data._directSubordinates)}</span>`
        : '';

    const subtitle = data.subtitle
        ? `<div style="font-size:11px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(data.subtitle)}</div>`
        : '';

    return `
        <div style="${cardStyle}">
            ${accentBar}
            <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                ${avatar}
                <div style="min-width:0;flex:1;">
                    <div style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(data.name)}</div>
                    ${subtitle}
                </div>
                ${badge}
            </div>
            ${code ? `<div style="display:flex;align-items:center;gap:6px;">${code}</div>` : ''}
        </div>`;
}

function debounce(callback, wait) {
    let timeout;

    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => callback(...args), wait);
    };
}

function init(container) {
    const dataUrl = container.dataset.url;
    const emptyMessage = container.dataset.emptyMessage;
    const errorMessage = container.dataset.errorMessage;
    const emptyEl = document.querySelector('[data-org-chart-empty]');
    const searchEmptyEl = document.querySelector('[data-org-chart-search-empty]');
    const searchForm = document.querySelector('[data-org-chart-search-form]');
    const searchInput = document.querySelector('[data-org-chart-search]');
    const siteSelect = document.querySelector('[data-org-chart-location]');
    const modeButtons = [...document.querySelectorAll('[data-org-chart-mode]')];

    let mode = 'structure';
    let siteId = '';
    let nodes = [];
    let chart = null;

    function showEmpty(message) {
        if (chart) {
            chart.clearHighlighting();
        }

        container.classList.add('hidden');
        emptyEl.textContent = message;
        emptyEl.classList.remove('hidden');
    }

    function showChart() {
        emptyEl.classList.add('hidden');
        container.classList.remove('hidden');
    }

    function render() {
        if (nodes.length <= 1) {
            showEmpty(emptyMessage);

            return;
        }

        showChart();

        if (!chart) {
            chart = new OrgChart()
                .container(container)
                .data(nodes)
                .nodeWidth(() => 240)
                .nodeHeight(() => 110)
                .compact(true)
                .layout('top')
                .initialExpandLevel(2)
                .imageName('organization-chart')
                .svgWidth(container.clientWidth)
                .svgHeight(container.clientHeight)
                .nodeContent(nodeHtml)
                .onNodeClick((node) => {
                    if (node.data.profileUrl) {
                        window.location.href = node.data.profileUrl;
                    }
                })
                .render();
        } else {
            chart.data(nodes).initialExpandLevel(2).render();
        }
    }

    async function load() {
        searchEmptyEl.classList.add('hidden');

        const params = new URLSearchParams({ mode });
        if (siteId) {
            params.set('site_id', siteId);
        }

        try {
            const response = await fetch(`${dataUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();
            nodes = payload.nodes ?? [];
            render();
        } catch (error) {
            showEmpty(errorMessage);
        }
    }

    modeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            mode = button.dataset.orgChartMode;

            modeButtons.forEach((other) => {
                other.classList.remove(...MODE_CLASSES);
                other.classList.add(...(other === button ? MODE_ACTIVE_CLASSES : MODE_INACTIVE_CLASSES).split(' '));
            });

            load();
        });
    });

    siteSelect.addEventListener('change', () => {
        siteId = siteSelect.value;
        load();
    });

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();

        searchEmptyEl.classList.add('hidden');

        const query = searchInput.value.trim().toLowerCase();
        if (query === '') {
            return;
        }

        const match = nodes.find((node) => node.type !== 'root'
            && ((node.name ?? '').toLowerCase().includes(query)
                || (node.code ?? '').toLowerCase().includes(query)));

        if (!match) {
            searchEmptyEl.classList.remove('hidden');

            return;
        }

        chart.clearHighlighting();
        chart.setUpToTheRootHighlighted(match.id);
        chart.setCentered(match.id);
        chart.updateNodesState();
    });

    document.querySelectorAll('[data-org-chart-action]').forEach((button) => {
        button.addEventListener('click', () => {
            if (!chart) {
                return;
            }

            switch (button.dataset.orgChartAction) {
                case 'expandAll':
                    chart.expandAll();
                    break;
                case 'collapseAll':
                    chart.collapseAll();
                    break;
                case 'fit':
                    chart.fit();
                    break;
                case 'fullscreen':
                    chart.fullscreen(container);
                    break;
                case 'export':
                    chart.exportImg({ full: true, scale: 3, backgroundColor: '#ffffff' });
                    break;
            }
        });
    });

    window.addEventListener('resize', debounce(() => {
        if (!chart) {
            return;
        }

        chart.svgWidth(container.clientWidth).svgHeight(container.clientHeight).fit();
    }, 200));

    load();
}

const container = document.querySelector('[data-org-chart]');

if (container) {
    init(container);
}
