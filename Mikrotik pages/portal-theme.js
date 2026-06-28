(function () {
    var KEY = 'hp-landing-theme';

    function getTheme() {
        return localStorage.getItem(KEY) === 'light' ? 'light' : 'dark';
    }

    function setTheme(theme) {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        localStorage.setItem(KEY, theme);
    }

    function init() {
        setTheme(getTheme());
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setTheme(getTheme() === 'dark' ? 'light' : 'dark');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
