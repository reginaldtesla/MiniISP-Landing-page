@php
    $idleMinutes = max(1, (int) config('tesnet.admin_idle_logout_minutes', 5));
@endphp
<form id="admin-idle-logout" method="POST" action="{{ route('admin.logout') }}" class="hidden" aria-hidden="true">
    @csrf
</form>
<script>
(function () {
    var idleMs = {{ $idleMinutes }} * 60 * 1000;
    var timer = null;

    function logout() {
        var form = document.getElementById('admin-idle-logout');
        if (form) {
            form.submit();
            return;
        }
        window.location.href = @json(route('admin.login'));
    }

    function resetIdleTimer() {
        if (timer !== null) {
            clearTimeout(timer);
        }
        timer = setTimeout(logout, idleMs);
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (eventName) {
        document.addEventListener(eventName, resetIdleTimer, { passive: true });
    });

    resetIdleTimer();
})();
</script>
