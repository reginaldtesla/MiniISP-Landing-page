<script>
(function () {
    var key = 'tesnet-theme';
    function applyTheme(dark) {
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem(key, dark ? 'dark' : 'light');
    }
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyTheme(!document.documentElement.classList.contains('dark'));
        });
    });
})();
</script>
