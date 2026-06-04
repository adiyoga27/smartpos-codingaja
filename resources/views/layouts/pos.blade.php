<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS') - {{ config('app.name', 'SmartPOS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-50"
    x-data
    @if(session('success')) data-toast-success="{{ session('success') }}" @endif
    @if(session('error')) data-toast-error="{{ session('error') }}" @endif
    @if(session('warning')) data-toast-warning="{{ session('warning') }}" @endif
>
    <main class="h-full">
        @yield('content')
    </main>

    <div id="loader-overlay" class="loader-overlay">
        <div class="flex flex-col items-center gap-3">
            <div class="loader-spinner"></div>
            <span class="text-sm font-medium text-primary-600">Mohon tunggu...</span>
        </div>
    </div>

    <div id="toast-container" class="fixed bottom-6 left-6 z-50 flex flex-col gap-2 toast-container max-w-xs w-fit"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @livewireScripts
    @stack('scripts')
</body>
</html>
