<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Effepi Master') · Effepi Telefonia</title>
    <meta name="description" content="@yield('description', 'Effepi Telefonia, Master Dealer Optima per il Centro Sud: al fianco dei negozi di prossimità e degli agenti.')">
    @hasSection('noindex')<meta name="robots" content="noindex, nofollow">@endif
    <script>document.documentElement.className+=' js'</script>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/simbolo-effepi.svg') }}">
    <meta name="theme-color" content="#00a9ea">
    <link rel="preload" href="{{ asset('fonts/poppins-600.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body>
@yield('body')
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}" defer></script>
</body>
</html>
