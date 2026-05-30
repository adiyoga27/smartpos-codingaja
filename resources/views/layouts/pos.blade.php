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
<body class="h-full bg-slate-50 overflow-hidden"
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

    <div id="toast-container" class="toast-container"></div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        function showToast(message, type = 'success') {
            let container = document.getElementById('toast-container');
            if (!container) return;
            let colors = { success: 'bg-emerald-500', error: 'bg-red-500', warning: 'bg-amber-500', info: 'bg-blue-500' };
            let icons = { success: 'bi-check-circle-fill', error: 'bi-exclamation-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
            let toast = document.createElement('div');
            toast.className = `${colors[type] || colors.info} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 mb-2 animate-slide-in`;
            toast.innerHTML = `<i class="bi ${icons[type] || icons.info}"></i><span class="text-sm">${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 3000);
        }
    </script>
    @livewireScripts
    @stack('scripts')
</body>
</html>
