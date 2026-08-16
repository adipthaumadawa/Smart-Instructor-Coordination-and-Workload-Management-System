(function(){
    'use strict';
    const body = document.body;

    function closeMenus(except) {
        document.querySelectorAll('.dropdown-menu').forEach(m => {
            if (m !== except) {
                m.hidden = true;
                const b = document.querySelector('[data-menu-button="' + m.id + '"]');
                if (b) b.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.addEventListener('click', e => {
        const menuButton = e.target.closest('[data-menu-button]');
        if (menuButton) {
            e.preventDefault();
            e.stopPropagation();
            const menu = document.getElementById(menuButton.dataset.menuButton);
            const open = menu && menu.hidden;
            closeMenus(menu);
            if (menu) {
                menu.hidden = !open;
                menuButton.setAttribute('aria-expanded', String(open));
            }
            return;
        }
        if (!e.target.closest('.menu-wrap')) closeMenus(null);

        const dismiss = e.target.closest('.btn-close,[data-dismiss="alert"]');
        if (dismiss) {
            const alert = dismiss.closest('.alert');
            if (alert) alert.remove();
        }

        const toggle = e.target.closest('[data-sidebar-toggle]');
        if (toggle) {
            const open = !body.classList.contains('sidebar-open');
            body.classList.toggle('sidebar-open', open);
            document.querySelectorAll('[data-sidebar-toggle]').forEach(b => b.setAttribute('aria-expanded', String(open)));
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeMenus(null);
            body.classList.remove('sidebar-open');
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            document.getElementById('globalSearch')?.focus();
        }
    });

    // Enhanced Global Search for All Dashboards
    const search = document.getElementById('globalSearch');
    if (search) {
        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();

            // 1. Filter table rows across any active table view
            document.querySelectorAll('main table tbody tr, .act-table tbody tr, .admin-table tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = (!q || text.includes(q)) ? '' : 'none';
            });

            // 2. Filter structured list items (schedule rows, leave items, alert items)
            document.querySelectorAll('main .schedule-row, main .leave-row, main .alert-row, main .sched-item, main .leave-item, main .alert-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = (!q || text.includes(q)) ? '' : 'none';
            });

            // 3. Filter standalone dashboard cards or elements lacking tables/lists
            document.querySelectorAll('main .card, main .ui-card, main .d-card').forEach(card => {
                if (card.closest('.topbar, .sidebar')) return;
                if (card.querySelectorAll('table, .schedule-list, .leave-list').length === 0) {
                    const text = card.textContent.toLowerCase();
                    card.style.opacity = (!q || text.includes(q)) ? '1' : '0.3';
                }
            });
        });
    }

    window.confirmDelete = function(message = 'Are you sure you want to continue?') {
        return window.confirm(message);
    };

    setTimeout(() => document.querySelectorAll('.alert.auto-dismiss,.alert-dismissible').forEach(a => a.remove()), 6000);
})();