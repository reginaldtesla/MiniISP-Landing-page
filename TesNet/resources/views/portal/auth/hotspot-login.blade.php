<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connecting — TesNet</title>
    <meta name="robots" content="noindex,nofollow">
</head>
<body>
    <p style="font-family: system-ui, sans-serif; text-align: center; padding: 2rem;">Connecting you to the hotspot…</p>
    <form id="hotspot-login" method="POST" action="{{ $loginUrl }}">
        <input type="hidden" name="username" value="{{ $username }}">
        <input type="hidden" name="password" value="{{ $password }}">
        <input type="hidden" name="dst" value="{{ $postLoginUrl }}">
    </form>
    <script>
        document.getElementById('hotspot-login').submit();
    </script>
</body>
</html>
