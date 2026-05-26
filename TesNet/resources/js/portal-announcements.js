(function () {
    var storageKey = 'tesnet-dismissed-announcements';

    function dismissedIds() {
        try {
            var raw = localStorage.getItem(storageKey);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function dismissId(id) {
        var ids = dismissedIds();
        if (ids.indexOf(id) === -1) {
            ids.push(id);
            localStorage.setItem(storageKey, JSON.stringify(ids));
        }
    }

    function closeModal(modal) {
        var id = parseInt(modal.getAttribute('data-announcement-id'), 10);
        if (!isNaN(id)) {
            dismissId(id);
        }
        modal.remove();
        document.body.classList.remove('overflow-hidden');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('announcement-modal');
        if (!modal) {
            return;
        }

        var id = parseInt(modal.getAttribute('data-announcement-id'), 10);
        if (!isNaN(id) && dismissedIds().indexOf(id) !== -1) {
            modal.remove();
            return;
        }

        document.body.classList.add('overflow-hidden');

        modal.querySelectorAll('[data-announcement-dismiss], [data-announcement-got-it]').forEach(function (el) {
            el.addEventListener('click', function () {
                closeModal(modal);
            });
        });

        document.addEventListener('keydown', function onKey(e) {
            if (e.key === 'Escape' && document.getElementById('announcement-modal')) {
                closeModal(modal);
                document.removeEventListener('keydown', onKey);
            }
        });
    });
})();
