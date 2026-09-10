(function () {
    'use strict';

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

    function fetchCatalog(payBase, siteSlug) {
        var url = payBase.replace(/\/$/, '') + '/packages.php';
        if (siteSlug) {
            url += '?site=' + encodeURIComponent(siteSlug);
        }
        return fetch(url, { credentials: 'omit', cache: 'no-store' }).then(function (res) {
            if (!res.ok) {
                throw new Error('Catalog unavailable');
            }
            return res.json();
        });
    }

    function buildPkgSlugMap(packages) {
        var map = {};
        packages.forEach(function (pkg) {
            if (pkg.in_stock) {
                map[pkg.name] = pkg.slug;
            }
        });
        return map;
    }

    function packageSpeed(pkg) {
        if (pkg.speed_mbps) {
            return Number(pkg.speed_mbps);
        }
        var match = String(pkg.name || '').match(/^(\d+)\s*Mbps/i);
        return match ? Number(match[1]) : 0;
    }

    var DURATION_TABS = [
        { id: '24h', label: '24 hr' },
        { id: '3d', label: '3 days' },
        { id: '7d', label: '1 week' },
        { id: '30d', label: '1 month' }
    ];

    function durationId(pkg) {
        var slug = String(pkg.slug || '').toLowerCase();
        var name = String(pkg.name || '').toLowerCase();
        if (/-24h(?:-|$)/.test(slug) || /\b24\s*hours?\b/.test(name)) {
            return '24h';
        }
        if (/-3d(?:-|$)/.test(slug) || /\b3\s*days?\b/.test(name)) {
            return '3d';
        }
        if (/-weekly(?:-|$)/.test(slug) || /\b7\s*days?\b/.test(name) || /\b1\s*weeks?\b/.test(name)) {
            return '7d';
        }
        if (/-monthly(?:-|$)/.test(slug) || /\b30\s*days?\b/.test(name) || /\b1\s*months?\b/.test(name)) {
            return '30d';
        }
        return '';
    }

    function speedLabel(pkg) {
        var speed = packageSpeed(pkg);
        return speed ? (speed + ' Mbps') : (pkg.name || '');
    }

    function groupByDuration(packages) {
        var groups = { '24h': [], '3d': [], '7d': [], '30d': [] };
        packages.forEach(function (pkg) {
            var id = durationId(pkg);
            if (groups[id]) {
                groups[id].push(pkg);
            }
        });
        Object.keys(groups).forEach(function (id) {
            groups[id].sort(function (a, b) {
                return packageSpeed(a) - packageSpeed(b);
            });
        });
        return groups;
    }

    function renderPkgRow(pkg, title) {
        var featured = pkg.featured ? ' pkg-row--featured' : '';
        var duration = durationId(pkg);
        return (
            '<button type="button" class="pkg-row' + featured + '" data-package="' + escapeHtml(pkg.name) + '" data-slug="' + escapeHtml(pkg.slug) + '" data-buy-url="' + escapeHtml(pkg.buy_url) + '" data-duration="' + escapeHtml(duration) + '" aria-pressed="false">' +
                '<span class="pkg-row-info">' +
                    '<span class="pkg-row-name">' + escapeHtml(title || speedLabel(pkg) || pkg.name) + '</span>' +
                    '<span class="pkg-row-meta">' + escapeHtml(pkg.data_label) + '</span>' +
                '</span>' +
                '<span class="pkg-row-price">' + escapeHtml(formatGhs(pkg.price_ghs)) + '</span>' +
            '</button>'
        );
    }

    function renderAllDurationPackages(packages) {
        if (!packages.length) {
            return '<p class="login-hint buy-empty-msg">No packages for this duration right now.</p>';
        }
        return (
            '<div class="pkg-list" data-kind="unlimited">' +
                packages.map(function (pkg) {
                    return renderPkgRow(pkg, speedLabel(pkg));
                }).join('') +
            '</div>'
        );
    }

    function renderDurationTabs(activeId) {
        return DURATION_TABS.map(function (tab) {
            var active = tab.id === activeId;
            return (
                '<button type="button" class="pricing-tab' + (active ? ' is-active' : '') +
                    '" role="tab" id="tab-' + tab.id +
                    '" aria-selected="' + (active ? 'true' : 'false') +
                    '" aria-controls="packagesPanel" data-pricing-tab="' + tab.id + '">' +
                    escapeHtml(tab.label) +
                '</button>'
            );
        }).join('');
    }

    function syncPkgSlugMap(packages) {
        var map = buildPkgSlugMap(packages);
        if (!window.PKG_SLUG || typeof window.PKG_SLUG !== 'object') {
            window.PKG_SLUG = map;
            return;
        }
        Object.keys(window.PKG_SLUG).forEach(function (key) {
            delete window.PKG_SLUG[key];
        });
        Object.assign(window.PKG_SLUG, map);
    }

    function startCheckout(row) {
        var slug = row.getAttribute('data-slug');
        var name = row.getAttribute('data-package');
        var buyUrl = row.getAttribute('data-buy-url');
        if (!slug || !buyUrl) {
            return;
        }

        var panel = document.getElementById('packagesPanel');
        var statusEl = document.getElementById('paymentStatus');

        document.querySelectorAll('.pkg-row').forEach(function (r) {
            r.classList.remove('selected', 'loading');
            r.setAttribute('aria-pressed', 'false');
        });
        row.classList.add('selected', 'loading');
        row.setAttribute('aria-pressed', 'true');
        if (panel) {
            panel.classList.add('is-paying');
        }
        if (statusEl) {
            statusEl.hidden = false;
            statusEl.classList.add('visible');
            statusEl.innerHTML = '<span class="spinner-inline"></span>Opening Paystack for <strong>' +
                escapeHtml(name) + '</strong>… Please wait.';
        }

        window.location.href = buyUrl;
    }

    function bindPackageRows(panel) {
        if (!panel || panel.getAttribute('data-pkg-bound') === '1') {
            return;
        }
        panel.setAttribute('data-pkg-bound', '1');
        panel.addEventListener('click', function (event) {
            var row = event.target.closest('.pkg-row');
            if (row && panel.contains(row)) {
                startCheckout(row);
            }
        });
    }

    function firstStockedDuration(groups) {
        var i;
        for (i = 0; i < DURATION_TABS.length; i += 1) {
            if (groups[DURATION_TABS[i].id] && groups[DURATION_TABS[i].id].length > 0) {
                return DURATION_TABS[i].id;
            }
        }
        return DURATION_TABS[0].id;
    }

    function mountPortal(options) {
        var payBase = options.payBase || 'https://pay.tesnet.xyz';
        var siteSlug = options.siteSlug || '';
        var panel = document.getElementById('packagesPanel');
        var tabsWrap = document.querySelector('.pricing-tabs-wrap');
        var statusEl = document.getElementById('paymentStatus');
        var durationGroups = { '24h': [], '3d': [], '7d': [], '30d': [] };
        var extraHtml = '';
        var activeDuration = '24h';

        if (!panel) {
            return;
        }

        function showDuration(id) {
            activeDuration = id;
            if (typeof window.portalSetDuration === 'function') {
                window.portalSetDuration(id);
                return;
            }
            if (tabsWrap) {
                var buttons = tabsWrap.querySelectorAll('[data-pricing-tab]');
                var i;
                for (i = 0; i < buttons.length; i += 1) {
                    var on = buttons[i].getAttribute('data-pricing-tab') === id;
                    if (on) {
                        buttons[i].classList.add('is-active');
                        buttons[i].setAttribute('aria-selected', 'true');
                    } else {
                        buttons[i].classList.remove('is-active');
                        buttons[i].setAttribute('aria-selected', 'false');
                    }
                }
            }
            var rows = panel.querySelectorAll('.pkg-row');
            var j;
            for (j = 0; j < rows.length; j += 1) {
                var rowDur = rows[j].getAttribute('data-duration');
                if (rowDur === id) {
                    rows[j].classList.remove('is-duration-hidden');
                } else if (rowDur) {
                    rows[j].classList.add('is-duration-hidden');
                }
            }
        }

        panel.innerHTML = '<p class="login-hint buy-empty-msg">Loading packages\u2026</p>';

        fetchCatalog(payBase, siteSlug)
            .then(function (data) {
                syncPkgSlugMap(data.packages || []);
                var packages = data.packages || [];
                var unlimitedPkgs = packages.filter(function (p) {
                    return p.kind === 'unlimited' || durationId(p) !== '';
                });
                var dataPkgs = packages.filter(function (p) {
                    return p.kind === 'data' && durationId(p) === '';
                });
                var timePkgs = packages.filter(function (p) {
                    return p.kind === 'time' && durationId(p) === '';
                });
                var unlimitedInStock = unlimitedPkgs.filter(function (p) { return p.in_stock; });
                var dataInStock = dataPkgs.filter(function (p) { return p.in_stock; });
                var timeInStock = timePkgs.filter(function (p) { return p.in_stock; });

                if (!data.has_any_stock) {
                    panel.innerHTML =
                        '<p class="login-hint buy-empty-msg">New packages are being updated. Check back soon or contact support at 0538850222.</p>';
                    if (tabsWrap) {
                        tabsWrap.hidden = true;
                    }
                    return;
                }

                extraHtml = '';
                if (unlimitedInStock.length === 0) {
                    if (dataInStock.length > 0) {
                        extraHtml += '<p class="pkg-group-label">Data bundles</p>';
                        extraHtml += '<div class="pkg-list" data-kind="data">';
                        dataInStock.forEach(function (pkg) {
                            extraHtml += renderPkgRow(pkg);
                        });
                        extraHtml += '</div>';
                    }
                    if (timeInStock.length > 0) {
                        extraHtml += '<p class="pkg-group-label">Time passes</p>';
                        extraHtml += '<div class="pkg-list" data-kind="time">';
                        timeInStock.forEach(function (pkg) {
                            extraHtml += renderPkgRow(pkg);
                        });
                        extraHtml += '</div>';
                    }
                }

                durationGroups = groupByDuration(unlimitedInStock);
                activeDuration = firstStockedDuration(durationGroups);

                if (unlimitedInStock.length > 0) {
                    var allDurationPkgs = [];
                    var d;
                    for (d = 0; d < DURATION_TABS.length; d += 1) {
                        allDurationPkgs = allDurationPkgs.concat(durationGroups[DURATION_TABS[d].id] || []);
                    }
                    panel.innerHTML = renderAllDurationPackages(allDurationPkgs) + extraHtml;
                    if (tabsWrap) {
                        var tabsEl = tabsWrap.querySelector('.pricing-tabs');
                        if (tabsEl) {
                            tabsEl.setAttribute('aria-label', 'Package duration');
                        }
                        tabsWrap.hidden = false;
                    }
                    showDuration(activeDuration);
                } else {
                    if (tabsWrap) {
                        tabsWrap.hidden = true;
                    }
                    panel.innerHTML = extraHtml ||
                        '<p class="login-hint buy-empty-msg">New packages are being updated. Check back soon or contact support at 0538850222.</p>';
                }

                var tapHint = document.getElementById('buyTapHint');
                if (tapHint) {
                    tapHint.hidden = false;
                }

                bindPackageRows(panel);

                if (statusEl) {
                    statusEl.hidden = true;
                    statusEl.classList.remove('visible');
                    statusEl.textContent = '';
                }
            })
            .catch(function () {
                panel.innerHTML =
                    '<p class="login-hint buy-empty-msg">Could not load packages. Join JJKA Wi\u2011Fi and try again, or call 0538850222.</p>';
            });
    }

    window.TesnetPortalCatalog = {
        mount: mountPortal,
        fetch: fetchCatalog,
        formatGhs: formatGhs,
        startCheckout: startCheckout
    };
})();
