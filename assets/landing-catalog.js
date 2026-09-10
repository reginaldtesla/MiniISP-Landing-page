(function () {
    'use strict';

    var PAY_BASE = 'https://pay.tesnet.xyz';

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatGhs(priceGhs) {
        var n = parseFloat(priceGhs);
        if (Number.isNaN(n)) {
            return priceGhs;
        }
        if (Math.abs(n - Math.round(n)) < 0.001) {
            return 'GH\u20b5' + Math.round(n);
        }
        return 'GH\u20b5' + n.toFixed(2);
    }

    function payUrl(path) {
        return PAY_BASE.replace(/\/$/, '') + path;
    }

    function fetchCatalog() {
        return fetch(payUrl('/packages.php'), { credentials: 'omit', cache: 'no-store' }).then(function (res) {
            if (!res.ok) {
                throw new Error('Catalog unavailable');
            }
            return res.json();
        });
    }

    function setStatus(message, isError) {
        var statusEl = document.getElementById('buyStatus');
        if (!statusEl) {
            return;
        }
        if (!message) {
            statusEl.hidden = true;
            statusEl.textContent = '';
            return;
        }
        statusEl.hidden = false;
        statusEl.textContent = message;
        statusEl.classList.toggle('text-red-600', !!isError);
        statusEl.classList.toggle('dark:text-red-400', !!isError);
    }

    function packageSpeed(pkg) {
        if (pkg.speed_mbps) {
            return Number(pkg.speed_mbps);
        }
        var match = String(pkg.name || '').match(/^(\d+)\s*Mbps/i);
        return match ? Number(match[1]) : 0;
    }

    function packageDuration(pkg) {
        var parts = String(pkg.name || '').split('\u00b7');
        if (parts.length > 1) {
            return parts.slice(1).join('\u00b7').trim();
        }
        return pkg.data_label || pkg.name;
    }

    function groupBySpeed(packages) {
        var groups = {};
        var order = [];
        packages.forEach(function (pkg) {
            var speed = packageSpeed(pkg);
            var key = String(speed);
            if (!groups[key]) {
                groups[key] = [];
                order.push(speed);
            }
            groups[key].push(pkg);
        });
        order.sort(function (a, b) { return a - b; });
        return order.map(function (speed) {
            return {
                speed: speed,
                label: speed + ' Mbps',
                packages: groups[String(speed)]
            };
        });
    }

    function renderCard(pkg, title) {
        var inStock = !!pkg.in_stock;
        var stockNote = inStock && pkg.available
            ? '<p class="mt-2 text-xs font-medium text-primary">' + escapeHtml(String(pkg.available)) + ' codes left</p>'
            : '';
        var cardClass = 'flex flex-col rounded-2xl border p-6 text-left';
        cardClass += inStock
            ? ' border-primary/20 bg-white dark:border-teal-800 dark:bg-surface-dark'
            : ' border-dashed border-gray-300 bg-gray-50 opacity-90 dark:border-teal-900 dark:bg-teal-950/40';

        var action = inStock
            ? '<button type="button" class="btn-primary mt-6 inline-flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-bold text-white" data-buy-slug="' +
                escapeHtml(pkg.slug) + '">Buy a code</button>'
            : '<span class="mt-6 inline-flex w-full items-center justify-center rounded-xl border border-gray-300 px-4 py-3 text-sm font-semibold text-text-muted dark:border-teal-800">Sold out</span>';

        return (
            '<article class="' + cardClass + '">' +
                '<div class="flex items-start justify-between gap-3">' +
                    '<h3 class="font-display text-lg font-bold text-text-main">' + escapeHtml(title || pkg.name) + '</h3>' +
                    (inStock
                        ? '<span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary">In stock</span>'
                        : '<span class="rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-bold text-text-muted dark:bg-teal-900">Soon</span>') +
                '</div>' +
                '<p class="mt-2 font-display text-3xl font-extrabold text-primary">' + escapeHtml(formatGhs(pkg.price_ghs)) + '</p>' +
                '<p class="mt-1 text-sm text-text-muted">' + escapeHtml(pkg.data_label) + '</p>' +
                stockNote +
                action +
            '</article>'
        );
    }

    function fillGrid(sectionEl, gridEl, packages) {
        if (!sectionEl || !gridEl) {
            return;
        }
        if (!packages.length) {
            sectionEl.hidden = true;
            gridEl.innerHTML = '';
            return;
        }
        sectionEl.hidden = false;
        gridEl.innerHTML = packages.map(function (pkg) { return renderCard(pkg); }).join('');
    }

    function renderSpeedGroups(container, packages) {
        if (!container) {
            return;
        }
        if (!packages.length) {
            container.innerHTML = '';
            container.hidden = true;
            return;
        }
        container.hidden = false;
        container.innerHTML = groupBySpeed(packages).map(function (group, index) {
            return (
                '<section class="' + (index === 0 ? 'mt-10' : 'mt-12') + '">' +
                    '<h2 class="text-xs font-bold uppercase tracking-[0.14em] text-primary">' +
                        escapeHtml(group.label) +
                    '</h2>' +
                    '<div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">' +
                        group.packages.map(function (pkg) {
                            return renderCard(pkg, packageDuration(pkg));
                        }).join('') +
                    '</div>' +
                '</section>'
            );
        }).join('');
    }

    function renderCatalog(data) {
        var emptyEl = document.getElementById('buyEmpty');
        var contentEl = document.getElementById('buyContent');
        var subtitleEl = document.getElementById('buySubtitle');
        var unlimitedEl = document.getElementById('buyUnlimited');
        var dataGrid = document.getElementById('buyDataGrid');
        var timeGrid = document.getElementById('buyTimeGrid');
        var dataSection = document.getElementById('buyDataSection');
        var timeSection = document.getElementById('buyTimeSection');
        var packages = data.packages || [];
        var unlimitedPkgs = packages.filter(function (p) { return p.kind === 'unlimited'; });
        var dataPkgs = packages.filter(function (p) { return p.kind === 'data'; });
        var timePkgs = packages.filter(function (p) { return p.kind === 'time'; });

        if (!packages.length) {
            if (emptyEl) emptyEl.hidden = false;
            if (contentEl) contentEl.hidden = true;
            return;
        }

        if (emptyEl) emptyEl.hidden = true;
        if (contentEl) contentEl.hidden = false;

        if (subtitleEl) {
            subtitleEl.textContent = data.has_any_stock
                ? 'Pay with MoMo or card. We only sell a code if that plan still has stock.'
                : 'Plans are listed, but no codes are in stock right now. Check back soon.';
        }

        renderSpeedGroups(unlimitedEl, unlimitedPkgs);
        fillGrid(dataSection, dataGrid, dataPkgs);
        fillGrid(timeSection, timeGrid, timePkgs);
    }

    function startCheckout(slug, button) {
        if (!slug) {
            return;
        }
        var original = button ? button.textContent : '';
        if (button) {
            button.disabled = true;
            button.textContent = 'Checking stock…';
        }
        setStatus('Checking TesNet Pay for an available code…');

        fetchCatalog()
            .then(function (data) {
                renderCatalog(data);
                var fresh = (data.packages || []).filter(function (pkg) {
                    return pkg.slug === slug;
                })[0];
                if (!fresh || !fresh.in_stock || !fresh.buy_url) {
                    setStatus('That plan has no codes left. Pick another package.', true);
                    return;
                }
                setStatus('Opening payment for ' + fresh.name + '…');
                window.location.href = fresh.buy_url;
            })
            .catch(function () {
                setStatus('Could not reach the pay server. Try again in a moment.', true);
                if (button) {
                    button.disabled = false;
                    button.textContent = original || 'Buy a code';
                }
            });
    }

    function bindBuys() {
        var contentEl = document.getElementById('buyContent');
        if (!contentEl || contentEl.getAttribute('data-buy-bound') === '1') {
            return;
        }
        contentEl.setAttribute('data-buy-bound', '1');
        contentEl.addEventListener('click', function (event) {
            var button = event.target.closest('[data-buy-slug]');
            if (button) {
                event.preventDefault();
                startCheckout(button.getAttribute('data-buy-slug'), button);
                return;
            }
            var link = event.target.closest('a[href*="buy.php?pkg="]');
            if (!link) {
                return;
            }
            var match = String(link.getAttribute('href') || '').match(/[?&]pkg=([^&]+)/);
            if (!match) {
                return;
            }
            event.preventDefault();
            startCheckout(decodeURIComponent(match[1]), link);
        });
    }

    function mount() {
        if (!document.getElementById('buyContent')) {
            return;
        }
        bindBuys();
        fetchCatalog()
            .then(renderCatalog)
            .catch(function () {
                var subtitleEl = document.getElementById('buySubtitle');
                if (subtitleEl) {
                    subtitleEl.textContent = 'Live stock could not load. You can still tap a plan — Pay will sell a code only if one is available.';
                }
            });
    }

    window.TesnetLandingCatalog = { mount: mount };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();
