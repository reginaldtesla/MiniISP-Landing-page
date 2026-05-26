<script>
(function () {
    var key = 'tesnet-theme';
    var stored = localStorage.getItem(key);
    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (stored === 'dark' || (!stored && prefersDark)) {
        document.documentElement.classList.add('dark');
    } else if (stored === 'light') {
        document.documentElement.classList.remove('dark');
    }
})();
</script>
