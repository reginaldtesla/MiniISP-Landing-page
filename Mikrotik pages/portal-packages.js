(function () {
    var pricingCopy = {
        data: {
            title: 'Data bundles',
            subtitle: 'Volume-based plans for browsing, streaming, and downloads.'
        },
        time: {
            title: 'Time passes',
            subtitle: 'Unlimited access — browse freely for the duration you choose.'
        }
    };

    function setPricingTab(tab) {
        var key = tab === 'time' ? 'time' : 'data';
        document.querySelectorAll('[data-pricing-tab]').forEach(function (btn) {
            var active = btn.getAttribute('data-pricing-tab') === key;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('.pricing-panel').forEach(function (panel) {
            var active = panel.id === 'panel-' + key;
            panel.classList.toggle('is-active', active);
            if (active) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', '');
            }
        });
        var copy = pricingCopy[key];
        var titleEl = document.getElementById('pricingTitle');
        var subtitleEl = document.getElementById('pricingSubtitle');
        if (titleEl) titleEl.textContent = copy.title;
        if (subtitleEl) subtitleEl.textContent = copy.subtitle;
    }

    function init() {
        document.querySelectorAll('[data-pricing-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setPricingTab(btn.getAttribute('data-pricing-tab'));
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
