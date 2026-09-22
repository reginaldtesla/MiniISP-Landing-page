(function () {
    'use strict';

    function fillStage(stage, ads, payBase) {
        stage.innerHTML = '';
        ads.forEach(function (ad, i) {
            var src = payBase + '/' + String(ad.image || '').replace(/^\//, '');
            var node;
            if (ad.href) {
                node = document.createElement('a');
                node.href = ad.href;
                node.target = '_blank';
                node.rel = 'noopener';
            } else {
                node = document.createElement('div');
            }
            node.className = 'login-ad' + (i === 0 ? ' is-active' : '');
            var img = document.createElement('img');
            img.src = src;
            img.alt = ad.alt || 'Advertisement';
            node.appendChild(img);
            stage.appendChild(node);
        });
        stage.hidden = false;
    }

    function mountAds(options) {
        var payBase = (options.payBase || '').replace(/\/$/, '');
        var siteSlug = options.siteSlug || '';
        var stages = document.querySelectorAll('.js-ad-stage');
        var fallback = document.getElementById('loginAdFallback');
        var brand = document.querySelector('.login-brand');
        var strips = document.querySelectorAll('.phone-ad-strip');
        if (!payBase || (!stages.length && !strips.length)) {
            return;
        }

        var url = payBase + '/ads.php';
        if (siteSlug) {
            url += '?site=' + encodeURIComponent(siteSlug);
        }

        fetch(url, { credentials: 'omit', cache: 'no-store' })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('ads unavailable');
                }
                return res.json();
            })
            .then(function (data) {
                var ads = (data && data.ads) ? data.ads : [];
                var placeholder = data.placeholder || {};
                if (fallback) {
                    var labelEl = fallback.querySelector('.login-brand-label');
                    var titleEl = fallback.querySelector('.login-brand-title');
                    var pitchEl = fallback.querySelector('.login-brand-pitch');
                    if (labelEl && placeholder.label) labelEl.textContent = placeholder.label;
                    if (titleEl && placeholder.title) titleEl.textContent = placeholder.title;
                    if (pitchEl && placeholder.pitch) pitchEl.textContent = placeholder.pitch;
                }
                var copies = document.querySelectorAll('.phone-ad-copy');
                var c;
                for (c = 0; c < copies.length; c += 1) {
                    if (placeholder.title) copies[c].textContent = placeholder.title;
                }
                if (!ads.length) {
                    return;
                }

                stages.forEach(function (stage) {
                    fillStage(stage, ads, payBase);
                });

                if (brand) {
                    brand.classList.add('has-ads');
                    brand.removeAttribute('aria-hidden');
                }
                strips.forEach(function (strip) {
                    strip.classList.add('has-ads');
                });

                if (ads.length < 2) {
                    return;
                }
                setInterval(function () {
                    stages.forEach(function (stage) {
                        var frames = stage.querySelectorAll('.login-ad');
                        if (frames.length < 2) return;
                        var current = 0;
                        var f;
                        for (f = 0; f < frames.length; f += 1) {
                            if (frames[f].classList.contains('is-active')) current = f;
                        }
                        frames[current].classList.remove('is-active');
                        frames[(current + 1) % frames.length].classList.add('is-active');
                    });
                }, 5000);
            })
            .catch(function () {
                /* keep the placeholder */
            });
    }

    window.TesnetPortalAds = { mount: mountAds };
})();
