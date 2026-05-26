(function () {
    const storageKey = 'tesnet-theme';

    function applyTheme(isDark) {
        document.documentElement.classList.toggle('dark', isDark);
        localStorage.setItem(storageKey, isDark ? 'dark' : 'light');
        syncThemeSwitches();
    }

    function syncThemeSwitches() {
        const isDark = document.documentElement.classList.contains('dark');

        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-checked', isDark ? 'true' : 'false');
            button.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        });
    }

    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            applyTheme(!document.documentElement.classList.contains('dark'));
        });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncThemeSwitches);
    } else {
        syncThemeSwitches();
    }
})();
