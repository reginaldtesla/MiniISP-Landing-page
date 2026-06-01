(function () {
    function formatRemaining(ms) {
        if (ms <= 0) {
            return { text: 'Expired', expired: true };
        }

        var days = Math.floor(ms / 86400000);
        var hours = Math.floor((ms % 86400000) / 3600000);
        var mins = Math.floor((ms % 3600000) / 60000);
        var secs = Math.floor((ms % 60000) / 1000);

        if (days > 0) {
            return { text: days + 'd ' + hours + 'h ' + mins + 'm left', expired: false };
        }

        if (hours > 0) {
            return { text: hours + 'h ' + mins + 'm ' + secs + 's left', expired: false };
        }

        return { text: mins + 'm ' + secs + 's left', expired: false };
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('plan-countdown');
        if (!root) {
            return;
        }

        var valueEl = document.getElementById('plan-countdown-value');
        var expiresAt = new Date(root.getAttribute('data-expires-at'));
        if (isNaN(expiresAt.getTime())) {
            return;
        }

        var reloaded = false;

        function tick() {
            var result = formatRemaining(expiresAt.getTime() - Date.now());

            if (valueEl) {
                valueEl.textContent = result.text;
            }

            if (result.expired) {
                root.classList.add('text-error', 'dark:text-red-400');

                if (!reloaded) {
                    reloaded = true;
                    setTimeout(function () {
                        window.location.reload();
                    }, 4000);
                }

                return;
            }

            root.classList.remove('text-error', 'dark:text-red-400');
        }

        tick();
        setInterval(tick, 1000);
    });
})();
