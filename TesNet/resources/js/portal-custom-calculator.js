(function () {
    var root = document.getElementById('custom-data-calculator');
    if (!root) {
        return;
    }

    var config = {};
    try {
        config = JSON.parse(root.getAttribute('data-config') || '{}');
    } catch (e) {
        return;
    }

    var minAmount = parseFloat(config.minAmount) || 1;
    var maxAmount = parseFloat(config.maxAmount) || 100;
    var step = parseFloat(config.step) || 0.5;
    var points = Array.isArray(config.points) ? config.points : [];

    points = points
        .map(function (p) {
            return {
                price: parseFloat(p.price),
                gb: parseFloat(p.gb),
                name: p.name || '',
            };
        })
        .filter(function (p) {
            return !isNaN(p.price) && !isNaN(p.gb);
        })
        .sort(function (a, b) {
            return a.price - b.price;
        });

    points = points.filter(function (p, index, list) {
        if (index === 0) {
            return true;
        }
        return p.gb >= list[index - 1].gb - 0.0001;
    });

    var amountInput = root.querySelector('[data-custom-amount]');
    var amountRange = root.querySelector('[data-custom-range]');
    var gbOut = root.querySelector('[data-custom-gb]');
    var bytesOut = root.querySelector('[data-custom-bytes]');
    var payBtn = root.querySelector('[data-custom-pay]');
    var formAmount = root.querySelector('[data-custom-form-amount]');

    function formatMoney(n) {
        return (Math.round(n * 100) / 100).toFixed(2);
    }

    function formatGb(n) {
        return (Math.round(n * 100) / 100).toFixed(2);
    }

    function formatBytes(n) {
        return Number(n).toLocaleString('en-GH');
    }

    function resolveBounds(amount) {
        if (points.length < 2) {
            return null;
        }

        var first = points[0];
        var second = points[1];
        var last = points[points.length - 1];
        var secondLast = points[points.length - 2];

        if (amount < first.price - 0.000001) {
            return {
                lower: { price: 0, gb: 0, name: '' },
                upper: first,
                mode: 'below',
            };
        }

        if (amount > last.price + 0.000001) {
            return { lower: secondLast, upper: last, mode: 'above' };
        }

        var lower = null;
        var upper = null;

        for (var j = 0; j < points.length; j++) {
            if (points[j].price <= amount + 0.000001) {
                lower = points[j];
            }
            if (points[j].price >= amount - 0.000001) {
                upper = points[j];
                break;
            }
        }

        if (!lower || !upper || upper.price < lower.price) {
            return null;
        }

        return { lower: lower, upper: upper, mode: 'between' };
    }

    function interpolateGb(amount) {
        var bounds = resolveBounds(amount);
        if (!bounds) {
            return null;
        }

        var lower = bounds.lower;
        var upper = bounds.upper;
        var gb;

        if (Math.abs(upper.price - lower.price) < 0.000001) {
            gb = lower.gb;
        } else {
            var t = (amount - lower.price) / (upper.price - lower.price);
            gb = lower.gb + t * (upper.gb - lower.gb);
        }

        gb = Math.round(gb * 100) / 100;

        return Math.max(0.01, gb);
    }

    function recalculate() {
        var amount = parseFloat(amountInput.value);
        if (isNaN(amount)) {
            amount = minAmount;
        }
        amount = Math.max(minAmount, Math.min(maxAmount, amount));
        amountInput.value = formatMoney(amount);
        if (amountRange) {
            amountRange.value = amount;
        }

        var gbRaw = interpolateGb(amount);

        if (gbRaw === null) {
            if (payBtn) {
                payBtn.disabled = true;
            }
            return;
        }

        var gb = gbRaw;
        var bytes = Math.round(gb * 1024 * 1024 * 1024);

        if (gbOut) {
            gbOut.textContent = formatGb(gb);
        }
        if (bytesOut) {
            bytesOut.textContent = formatBytes(bytes);
        }

        if (payBtn) {
            payBtn.disabled = false;
        }
        if (formAmount) {
            formAmount.value = formatMoney(amount);
        }
    }

    if (amountRange) {
        amountRange.min = minAmount;
        amountRange.max = maxAmount;
        amountRange.step = step;
        amountRange.addEventListener('input', function () {
            amountInput.value = formatMoney(parseFloat(amountRange.value));
            recalculate();
        });
    }

    amountInput.addEventListener('input', recalculate);
    amountInput.addEventListener('change', recalculate);

    if (!amountInput.value) {
        amountInput.value = formatMoney(minAmount);
    }
    recalculate();
})();
