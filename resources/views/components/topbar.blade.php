<header class="sticky top-0 z-30 bg-white/80 backdrop-blur-lg border-b border-slate-200/60" x-data="{ userMenuOpen: false, notifyOpen: false }">
    <div class="flex items-center justify-between h-16 px-4 lg:px-6">
        <!-- Left: Toggle + Breadcrumb -->
        <div class="flex items-center gap-3">
            <button @click="$store.sidebar.toggle()" class="btn btn-secondary btn-sm !p-2 lg:hidden">
                <i class="bi bi-list text-lg"></i>
            </button>
            <nav class="breadcrumb hidden sm:flex">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-1">
                    <i class="bi bi-house-door-fill text-xs"></i>
                    <span>Beranda</span>
                </a>
                @yield('breadcrumb')
            </nav>
        </div>

        <!-- Right: Notifications + User -->
        <div class="flex items-center gap-2">
            <!-- Notification Bell -->
            @php $lowStockCount = \App\Models\Product::lowStock()->count(); @endphp
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="relative btn btn-secondary btn-sm !p-2.5">
                    <i class="bi bi-bell text-lg"></i>
                    @if($lowStockCount > 0)
                    <span class="absolute -top-1 -right-1 w-5 h-5 flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full shadow-sm">
                        {{ $lowStockCount }}
                    </span>
                    @endif
                </button>
                <div x-show="open" @click.outside="open = false" x-transition.opacity
                     class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-lg border border-slate-100 overflow-hidden z-50">
                    <div class="px-4 py-3 border-b border-slate-100 font-semibold text-sm text-slate-800">Notifikasi</div>
                    <div class="p-4 text-sm text-slate-500">
                        @if($lowStockCount > 0)
                        <div class="flex items-center gap-3 p-2 rounded-lg bg-amber-50 text-amber-700">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <span>{{ $lowStockCount }} produk stok menipis</span>
                        </div>
                        @else
                        <div class="text-center py-4">
                            <i class="bi bi-bell-slash text-2xl text-slate-300"></i>
                            <p class="mt-2">Tidak ada notifikasi</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                        class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition-colors">
                    <img src="{{ auth()->user()->photo ? asset('storage/'.auth()->user()->photo) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=2563EB&color=fff' }}"
                         class="w-8 h-8 rounded-full object-cover ring-2 ring-slate-100" alt="Avatar">
                    <span class="hidden sm:block text-sm font-medium text-slate-700">{{ auth()->user()->name }}</span>
                    <i class="bi bi-chevron-down text-xs text-slate-400 hidden sm:block"></i>
                </button>
                <div x-show="open" @click.outside="open = false" x-transition.opacity
                     class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-100 overflow-hidden z-50">
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="bi bi-person"></i> Profil
                    </a>
                    <hr class="border-slate-100">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors w-full">
                            <i class="bi bi-box-arrow-left"></i> Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
