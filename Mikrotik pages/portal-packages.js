(function () {
    var DURATION_IDS = { '24h': true, '3d': true, '7d': true, '30d': true };
    var applying = false;

    function rowDuration(row) {
        var tagged = row.getAttribute('data-duration');
        if (tagged) {
            return tagged;
        }
        var name = String(row.getAttribute('data-package') || '') + ' ' + String(row.textContent || '');
        name = name.toLowerCase();
        if (name.indexOf('24 hour') !== -1 || name.indexOf('24h') !== -1) {
            return '24h';
        }
        if (name.indexOf('3 day') !== -1) {
            return '3d';
        }
        if (name.indexOf('7 day') !== -1 || name.indexOf('week') !== -1) {
            return '7d';
        }
        if (name.indexOf('30 day') !== -1 || name.indexOf('month') !== -1) {
            return '30d';
        }
        return '';
    }

    function findTabButton(el) {
        while (el && el.nodeType === 1) {
            if (el.getAttribute && el.getAttribute('data-pricing-tab')) {
                return el;
            }
            el = el.parentNode;
        }
        return null;
    }

    function setDurationTab(id) {
        if (!DURATION_IDS[id]) {
            return false;
        }

        applying = true;
        var tabs = document.querySelectorAll('[data-pricing-tab]');
        var i;
        for (i = 0; i < tabs.length; i += 1) {
            var on = tabs[i].getAttribute('data-pricing-tab') === id;
            if (on) {
                tabs[i].classList.add('is-active');
                tabs[i].setAttribute('aria-selected', 'true');
            } else {
                tabs[i].classList.remove('is-active');
                tabs[i].setAttribute('aria-selected', 'false');
            }
        }

        var panel = document.getElementById('packagesPanel');
        var shown = 0;
        if (panel) {
            var rows = panel.querySelectorAll('.pkg-row');
            var j;
            for (j = 0; j < rows.length; j += 1) {
                var match = rowDuration(rows[j]) === id;
                if (match) {
                    rows[j].classList.remove('is-duration-hidden');
                    shown += 1;
                } else {
                    rows[j].classList.add('is-duration-hidden');
                }
            }

            var empty = document.getElementById('durationEmptyMsg');
            if (!empty) {
                empty = document.createElement('p');
                empty.id = 'durationEmptyMsg';
                empty.className = 'login-hint buy-empty-msg';
                empty.textContent = 'No packages for this duration right now.';
                panel.appendChild(empty);
            }
            empty.hidden = shown > 0 || rows.length === 0;
        }

        var tabsWrap = document.querySelector('.pricing-tabs-wrap');
        if (tabsWrap) {
            tabsWrap.hidden = false;
        }

        applying = false;
        return true;
    }

    function activeDuration() {
        var active = document.querySelector('.pricing-tabs .pricing-tab.is-active[data-pricing-tab]');
        if (!active) {
            active = document.querySelector('[data-pricing-tab].is-active');
        }
        var id = active ? active.getAttribute('data-pricing-tab') : '24h';
        return DURATION_IDS[id] ? id : '24h';
    }

    function init() {
        document.addEventListener('click', function (event) {
            var btn = findTabButton(event.target);
            if (!btn) {
                return;
            }
            var id = btn.getAttribute('data-pricing-tab');
            if (!DURATION_IDS[id]) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            setDurationTab(id);
        }, true);

        var panel = document.getElementById('packagesPanel');
        if (panel && typeof MutationObserver === 'function') {
            var obs = new MutationObserver(function () {
                if (applying) {
                    return;
                }
                setDurationTab(activeDuration());
            });
            obs.observe(panel, { childList: true, subtree: true });
        }

        setDurationTab(activeDuration());
    }

    window.portalSetDuration = setDurationTab;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
