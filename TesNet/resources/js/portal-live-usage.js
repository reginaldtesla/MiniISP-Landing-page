(function () {
    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    function applyPayload(data) {
        var panel = document.getElementById('live-usage-panel');
        if (!panel || !data) {
            return;
        }

        var session = data.session || null;
        var plan = data.plan || {};
        var connected = !!data.connected;
        var onActiveSession = session && session.on_active_session;
        var dot = document.getElementById('live-usage-dot');
        var status = document.getElementById('live-usage-status');
        var hint = document.getElementById('live-usage-hint');
        var remainRow = document.getElementById('live-remain-row');
        var barWrap = document.getElementById('live-usage-bar-wrap');

        if (dot) {
            if (onActiveSession || connected) {
                dot.className = 'w-2 h-2 rounded-full bg-secondary dark:bg-secondary-fixed-dim animate-pulse';
            } else if (session && data.source === 'mikrotik') {
                dot.className = 'w-2 h-2 rounded-full bg-primary/70 dark:bg-primary-fixed-dim/70';
            } else {
                dot.className = 'w-2 h-2 rounded-full bg-outline-variant dark:bg-outline';
            }
        }

        if (status) {
            if (session && data.source === 'mikrotik') {
                status.textContent = onActiveSession ? 'Live · router' : 'Router · idle';
            } else if (session && data.source === 'radacct') {
                status.textContent = 'Live · accounting';
            } else if (!data.api_enabled) {
                status.textContent = 'API off';
            } else {
                status.textContent = 'Offline';
            }
        }

        if (session) {
            setText('live-bytes-in', session.bytes_in_nice || '0 B');
            setText('live-bytes-out', session.bytes_out_nice || '0 B');
            setText('live-uptime', onActiveSession ? (session.uptime_label || '—') : '—');

            if (remainRow && session.remain_nice) {
                remainRow.classList.remove('hidden');
                setText('live-remain', session.remain_nice);
            } else if (remainRow) {
                remainRow.classList.add('hidden');
            }

            if (barWrap && session.percent_used !== null && session.percent_used !== undefined) {
                barWrap.classList.remove('hidden');
                setText('live-usage-percent', session.percent_used + '%');
                var bar = document.getElementById('live-usage-bar');
                if (bar) {
                    bar.style.width = Math.min(100, session.percent_used) + '%';
                }
            } else if (barWrap) {
                barWrap.classList.add('hidden');
            }
        } else {
            setText('live-bytes-in', '—');
            setText('live-bytes-out', '—');
            setText('live-uptime', '—');
            if (remainRow) {
                remainRow.classList.add('hidden');
            }
            if (barWrap) {
                barWrap.classList.add('hidden');
            }
        }

        if (hint) {
            if (data.router_message) {
                hint.textContent = data.router_message;
                hint.classList.remove('hidden');
            } else if (onActiveSession || connected) {
                hint.classList.add('hidden');
            } else {
                hint.textContent = 'Readings come from the MikroTik router (API). Connect to campus Wi‑Fi, tap Connect to Internet, then this panel updates.';
                hint.classList.remove('hidden');
            }
        }

        var ring = document.getElementById('data-ring-progress');
        var remaining = document.getElementById('data-remaining-display');

        if (plan.is_unlimited) {
            if (remaining) {
                remaining.textContent = '∞';
            }
            if (ring) {
                ring.setAttribute('stroke-dashoffset', '0');
            }
            return;
        }

        if (plan.has_active_plan && remaining && plan.data_remaining_gb !== null && plan.data_remaining_gb !== undefined) {
            remaining.textContent = String(plan.data_remaining_gb);
        }

        if (ring && plan.chart_stroke_offset !== undefined) {
            ring.setAttribute('stroke-dashoffset', String(plan.chart_stroke_offset));
        }

        if (plan.total_plan_gb !== null && plan.total_plan_gb !== undefined) {
            setText('plan-total-gb', plan.is_unlimited ? 'Unlimited' : plan.total_plan_gb + ' GB');
        }
    }

    function fetchLive(url) {
        return fetch(url, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.getElementById('live-usage-panel');
        if (!panel) {
            return;
        }

        var url = panel.getAttribute('data-live-usage-url');
        var pollSeconds = parseInt(panel.getAttribute('data-poll-seconds') || '15', 10);
        if (!url || pollSeconds < 5) {
            return;
        }

        var initialEl = document.getElementById('live-usage-initial');
        if (initialEl && initialEl.textContent) {
            try {
                applyPayload(JSON.parse(initialEl.textContent));
            } catch (e) {
                /* ignore malformed bootstrap */
            }
        }

        function poll() {
            fetchLive(url)
                .then(applyPayload)
                .catch(function () {
                    var status = document.getElementById('live-usage-status');
                    if (status) {
                        status.textContent = 'Update failed';
                    }
                });
        }

        poll();
        setInterval(poll, pollSeconds * 1000);
    });
})();
