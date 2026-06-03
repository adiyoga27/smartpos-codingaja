<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'SmartPOS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full bg-slate-50"
    x-data
    @if(session('success')) data-toast-success="{{ session('success') }}" @endif
    @if(session('error')) data-toast-error="{{ session('error') }}" @endif
    @if(session('warning')) data-toast-warning="{{ session('warning') }}" @endif
>
    <!-- Alpine store for sidebar -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('sidebar', {
                open: window.innerWidth >= 1024,
                toggle() { this.open = !this.open },
                close() { this.open = false },
            });
        });
    </script>

    <div class="flex h-full">
        @include('components.sidebar')
        <div class="flex-1 flex flex-col min-w-0 lg:ml-64 transition-all duration-300">
            @include('components.topbar')
            <main class="flex-1 p-4 lg:p-6 overflow-y-auto">
                @yield('content')
            </main>
            <footer class="bg-white border-t border-slate-200/60 px-6 py-3 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} {{ optional(\App\Models\CompanySetting::first())->name ?? config('app.name') }} &mdash; v1.0.0
            </footer>
        </div>
    </div>

    <!-- Loader Overlay -->
    <div id="loader-overlay" class="loader-overlay">
        <div class="flex flex-col items-center gap-3">
            <div class="loader-spinner"></div>
            <span class="text-sm font-medium text-primary-600">Mohon tunggu...</span>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @livewireScripts
    @stack('scripts')
</body>
</html>
