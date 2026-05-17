<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'TemanLari') }}</title>
    <link rel="icon" type="image/svg+xml" href="https://api.iconify.design/mdi/run-fast.svg?color=%230E7A4C">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="bg-surface text-ink antialiased">
    @inertia
</body>
</html>
