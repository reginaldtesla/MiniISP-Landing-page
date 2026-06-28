(function () {
    function setView(view) {
        var isLogin = view === 'login';
        document.querySelectorAll('.view-tab[data-portal-view]').forEach(function (btn) {
            var active = btn.getAttribute('data-portal-view') === view;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        var loginPanel = document.getElementById('view-login');
        var buyPanel = document.getElementById('view-buy');
        if (loginPanel) {
            loginPanel.classList.toggle('is-active', isLogin);
            if (isLogin) loginPanel.removeAttribute('hidden');
            else loginPanel.setAttribute('hidden', '');
        }
        if (buyPanel) {
            buyPanel.classList.toggle('is-active', !isLogin);
            if (!isLogin) buyPanel.removeAttribute('hidden');
            else buyPanel.setAttribute('hidden', '');
        }
    }

    function initViewTabs() {
        document.querySelectorAll('[data-portal-view]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setView(btn.getAttribute('data-portal-view'));
            });
        });
        var params = new URLSearchParams(window.location.search);
        if (params.get('view') === 'buy' || params.get('pkg')) {
            setView('buy');
        } else {
            setView('login');
        }
    }

    function initPackageRows() {
        document.querySelectorAll('.pkg-row').forEach(function (row) {
            row.addEventListener('click', function () {
                document.querySelectorAll('.pkg-row').forEach(function (r) {
                    r.classList.remove('selected');
                    r.setAttribute('aria-pressed', 'false');
                });
                row.classList.add('selected');
                row.setAttribute('aria-pressed', 'true');
            });
        });
    }

    function init() {
        initViewTabs();
        initPackageRows();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.portalSetView = setView;
})();
