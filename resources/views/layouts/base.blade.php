<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $title ?? config('app.name', 'Marketian Mind')) - Online Marketing Education</title>

    <meta name="description" content="@yield('meta_description', 'Marketian Mind — Learn practical digital marketing for business owners, startup founders, and entrepreneurs.')">

    <!-- Fonts & Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-full flex flex-col">
    {{ $slot ?? '' }}
    @yield('content')

    @stack('scripts')
</body>
</html>
