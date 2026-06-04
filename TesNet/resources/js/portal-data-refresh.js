(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('portal-data-usage');
        if (!root) {
            return;
        }

        var url = root.getAttribute('data-refresh-url');
        var intervalMs = parseInt(root.getAttribute('data-refresh-interval-ms') || '60000', 10);

        if (!url || intervalMs < 15000) {
            return;
        }

        var remainingEl = document.getElementById('data-remaining-label');
        var usedEl = document.getElementById('data-used-label');
        var totalEl = document.getElementById('data-total-plan-label');
        var percentRemainingEl = document.getElementById('data-percent-remaining');
        var percentUsedEl = document.getElementById('data-percent-used');
        var barEl = document.getElementById('data-usage-bar');
        var planNameEl = document.getElementById('data-plan-name');
        var statusEl = document.getElementById('data-refresh-status');
        var inFlight = false;

        function setStatus(text) {
            if (statusEl) {
                statusEl.textContent = text;
            }
        }

        function apply(payload) {
            if (!payload || typeof payload !== 'object') {
                return;
            }

            if (!payload.has_active_plan) {
                setStatus('Plan ended — updating…');
                window.location.reload();

                return;
            }

            if (planNameEl && payload.package_name) {
                planNameEl.textContent = payload.package_name;
            }

            if (payload.is_unlimited) {
                if (remainingEl) {
                    remainingEl.innerHTML =
                        'Unlimited <span class="font-title-md text-title-md text-on-surface-variant dark:text-outline-variant">data</span>';
                }

                setStatus('Updated ' + (payload.refreshed_at_label || 'just now'));

                return;
            }

            if (remainingEl) {
                remainingEl.innerHTML =
                    (payload.data_remaining_label || '—') +
                    ' <span class="font-title-md text-title-md text-on-surface-variant dark:text-outline-variant">left</span>';
            }

            if (usedEl) {
                usedEl.textContent =
                    (payload.data_used_label || '—') + ' used of ' + (payload.total_plan_label || '—');
            }

            if (totalEl) {
                totalEl.textContent = payload.total_plan_label || '—';
            }

            if (percentRemainingEl) {
                percentRemainingEl.textContent = (payload.percent_remaining ?? 0) + '% remaining';
            }

            if (percentUsedEl) {
                percentUsedEl.textContent = (payload.percent_used ?? 0) + '% used';
            }

            if (barEl) {
                var pct = Math.min(100, Math.max(0, payload.percent_remaining ?? 0));
                barEl.style.width = pct + '%';
                barEl.setAttribute('aria-valuenow', String(pct));
            }

            setStatus('Updated ' + (payload.refreshed_at_label || 'just now'));
        }

        function refresh() {
            if (inFlight || document.hidden) {
                return;
            }

            inFlight = true;

            fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('refresh failed');
                    }

                    return response.json();
                })
                .then(apply)
                .catch(function () {
                    setStatus('Could not refresh — will retry');
                })
                .finally(function () {
                    inFlight = false;
                });
        }

        setInterval(refresh, intervalMs);
    });
})();
