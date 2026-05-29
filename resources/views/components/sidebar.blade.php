@php $company = \App\Models\CompanySetting::first(); @endphp
<nav x-data="{ openMenus: {
    @canany(['view_category','view_product','view_supplier','view_customer','view_account'])
    master: {{ request()->routeIs('master.*') ? 'true' : 'false' }},
    @endcanany
    @canany(['view_purchase','view_purchase_return'])
    pembelian: {{ request()->routeIs('transaksi.purchases.*','transaksi.purchase_returns.*') ? 'true' : 'false' }},
    @endcanany
    @canany(['view_sale','view_sale_return'])
    penjualan: {{ request()->routeIs('pos.*','transaksi.sale_returns.*') ? 'true' : 'false' }},
    @endcanany
    @canany(['view_payable','view_receivable'])
    hutangPiutang: {{ request()->routeIs('keuangan.payables.*','keuangan.receivables.*') ? 'true' : 'false' }},
    @endcanany
    @canany(['view_cash_account','view_cash_transaction'])
    kasBank: {{ request()->routeIs('keuangan.cash.*') ? 'true' : 'false' }},
    @endcanany
    @canany(['view_journal','view_ledger','view_balance_sheet','view_income_statement'])
    akuntansi: {{ request()->routeIs('akuntansi.*') ? 'true' : 'false' }},
    @endcanany
    @canany(['view_stock_mutation','view_stock_opname'])
    stok: {{ request()->routeIs('stok.*') ? 'true' : 'false' }},
    @endcanany
    @canany(['view_user','view_role'])
    pengguna: {{ request()->routeIs('users.*','roles.*') ? 'true' : 'false' }},
    @endcanany
} }" x-cloak>
    <!-- Mobile overlay -->
    <div x-show="$store.sidebar.open" x-on:click="$store.sidebar.close()" x-transition.opacity class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden"></div>

    <aside
        :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full'"
        class="fixed top-0 left-0 z-50 w-64 h-screen bg-sidebar transition-transform duration-300 ease-in-out lg:translate-x-0 flex flex-col shadow-xl"
    >
        <!-- Brand -->
        <div class="flex items-center gap-3 px-5 py-4 border-b border-white/5">
            @if($company && $company->logo)
                <img src="{{ asset('storage/'.$company->logo) }}" alt="Logo" class="h-8 w-8 rounded-lg object-cover">
            @else
                <div class="h-8 w-8 rounded-lg bg-primary-500/20 flex items-center justify-center">
                    <i class="bi bi-shop-window text-primary-400 text-lg"></i>
                </div>
            @endif
            <span class="text-lg font-bold text-white tracking-tight">SmartPOS</span>
        </div>

        <!-- Navigation -->
        <div class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('dashboard') ? 'bg-sidebar-active text-white shadow-sm' : 'text-slate-300 hover:bg-sidebar-hover hover:text-white' }}">
                <i class="bi bi-speedometer2 text-lg w-5 text-center"></i>
                <span>Dashboard</span>
            </a>

            <!-- Data Master -->
            @canany(['view_category','view_product','view_supplier','view_customer','view_account'])
            <div>
                <button @click="openMenus.master = !openMenus.master"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-slate-300 hover:bg-sidebar-hover hover:text-white">
                    <i class="bi bi-database text-lg w-5 text-center"></i>
                    <span class="flex-1 text-left">Data Master</span>
                    <i class="bi bi-chevron-down text-xs transition-transform duration-200" :class="openMenus.master && 'rotate-180'"></i>
                </button>
                <div x-show="openMenus.master" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 mt-1 space-y-0.5">
                    @can('view_category')
                    <a href="{{ route('master.categories.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('master.categories.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Kategori</a>
                    @endcan
                    @can('view_product')
                    <a href="{{ route('master.products.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('master.products.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Produk</a>
                    @endcan
                    @can('view_supplier')
                    <a href="{{ route('master.suppliers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('master.suppliers.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Supplier</a>
                    @endcan
                    @can('view_customer')
                    <a href="{{ route('master.customers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('master.customers.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Customer</a>
                    @endcan
                    @can('view_account')
                    <a href="{{ route('master.accounts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('master.accounts.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Akun Biaya</a>
                    @endcan
                    @can('view_payment_method')
                    <a href="{{ route('master.payment_methods.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('master.payment_methods.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Metode Pembayaran</a>
                    @endcan
                    <a href="{{ route('master.taxes.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('master.taxes.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Pajak</a>
                </div>
            </div>
            @endcanany

            <!-- Pembelian -->
            @canany(['view_purchase','view_purchase_return'])
            <div>
                <button @click="openMenus.pembelian = !openMenus.pembelian"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-slate-300 hover:bg-sidebar-hover hover:text-white">
                    <i class="bi bi-cart-check text-lg w-5 text-center"></i>
                    <span class="flex-1 text-left">Pembelian</span>
                    <i class="bi bi-chevron-down text-xs transition-transform duration-200" :class="openMenus.pembelian && 'rotate-180'"></i>
                </button>
                <div x-show="openMenus.pembelian" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 mt-1 space-y-0.5">
                    @can('view_purchase')
                    <a href="{{ route('transaksi.purchases.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('transaksi.purchases.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Pembelian</a>
                    @endcan
                    @can('view_purchase_return')
                    <a href="{{ route('transaksi.purchase_returns.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('transaksi.purchase_returns.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Return Pembelian</a>
                    @endcan
                </div>
            </div>
            @endcanany

            <!-- Penjualan -->
            @canany(['view_sale','view_sale_return'])
            <div>
                <button @click="openMenus.penjualan = !openMenus.penjualan"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-slate-300 hover:bg-sidebar-hover hover:text-white">
                    <i class="bi bi-cart3 text-lg w-5 text-center"></i>
                    <span class="flex-1 text-left">Penjualan</span>
                    <i class="bi bi-chevron-down text-xs transition-transform duration-200" :class="openMenus.penjualan && 'rotate-180'"></i>
                </button>
                <div x-show="openMenus.penjualan" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 mt-1 space-y-0.5">
                    @can('view_sale')
                    <a href="{{ route('pos.kasir') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('pos.kasir') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">POS Kasir</a>
                    @endcan
                    @can('view_sale_return')
                    <a href="{{ route('transaksi.sale_returns.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('transaksi.sale_returns.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Retur Penjualan</a>
                    @endcan
                    <a href="{{ route('pos.riwayat') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('pos.riwayat') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Riwayat Penjualan</a>
                </div>
            </div>
            @endcanany

            <!-- Hutang & Piutang -->
            @canany(['view_payable','view_receivable'])
            <div>
                <button @click="openMenus.hutangPiutang = !openMenus.hutangPiutang"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-slate-300 hover:bg-sidebar-hover hover:text-white">
                    <i class="bi bi-cash-stack text-lg w-5 text-center"></i>
                    <span class="flex-1 text-left">Hutang & Piutang</span>
                    <i class="bi bi-chevron-down text-xs transition-transform duration-200" :class="openMenus.hutangPiutang && 'rotate-180'"></i>
                </button>
                <div x-show="openMenus.hutangPiutang" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 mt-1 space-y-0.5">
                    @can('view_payable')
                    <a href="{{ route('keuangan.payables.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('keuangan.payables.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Bayar Hutang</a>
                    @endcan
                    @can('view_receivable')
                    <a href="{{ route('keuangan.receivables.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('keuangan.receivables.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Terima Piutang</a>
                    @endcan
                </div>
            </div>
            @endcanany

            <!-- Kas & Bank -->
            @canany(['view_cash_account','view_cash_transaction'])
            <div>
                <button @click="openMenus.kasBank = !openMenus.kasBank"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-slate-300 hover:bg-sidebar-hover hover:text-white">
                    <i class="bi bi-bank text-lg w-5 text-center"></i>
                    <span class="flex-1 text-left">Kas & Bank</span>
                    <i class="bi bi-chevron-down text-xs transition-transform duration-200" :class="openMenus.kasBank && 'rotate-180'"></i>
                </button>
                <div x-show="openMenus.kasBank" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 mt-1 space-y-0.5">
                    @can('view_cash_account')
                    <a href="{{ route('keuangan.cash_accounts.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('keuangan.cash_accounts.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Akun Kas/Bank</a>
                    @endcan
                    @can('view_cash_transaction')
                    <a href="{{ route('keuangan.cash_transactions.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('keuangan.cash_transactions.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Transaksi Kas</a>
                    @endcan
                </div>
            </div>
            @endcanany

            <!-- Akuntansi -->
            @canany(['view_journal','view_ledger','view_balance_sheet','view_income_statement'])
            <div>
                <button @click="openMenus.akuntansi = !openMenus.akuntansi"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-slate-300 hover:bg-sidebar-hover hover:text-white">
                    <i class="bi bi-journal-text text-lg w-5 text-center"></i>
                    <span class="flex-1 text-left">Akuntansi</span>
                    <i class="bi bi-chevron-down text-xs transition-transform duration-200" :class="openMenus.akuntansi && 'rotate-180'"></i>
                </button>
                <div x-show="openMenus.akuntansi" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 mt-1 space-y-0.5">
                    @can('view_journal')
                    <a href="{{ route('akuntansi.journals.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('akuntansi.journals.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Jurnal Umum</a>
                    @endcan
                    @can('view_ledger')
                    <a href="{{ route('akuntansi.ledger') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('akuntansi.ledger') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Buku Besar</a>
                    @endcan
                    @can('view_balance_sheet')
                    <a href="{{ route('akuntansi.balance_sheet') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('akuntansi.balance_sheet') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Neraca</a>
                    @endcan
                    @can('view_income_statement')
                    <a href="{{ route('akuntansi.income_statement') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('akuntansi.income_statement') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Laba Rugi</a>
                    @endcan
                </div>
            </div>
            @endcanany

            <!-- Stok Kontrol -->
            @canany(['view_stock_mutation','view_stock_opname'])
            <div>
                <button @click="openMenus.stok = !openMenus.stok"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-slate-300 hover:bg-sidebar-hover hover:text-white">
                    <i class="bi bi-box-seam text-lg w-5 text-center"></i>
                    <span class="flex-1 text-left">Stok Kontrol</span>
                    <i class="bi bi-chevron-down text-xs transition-transform duration-200" :class="openMenus.stok && 'rotate-180'"></i>
                </button>
                <div x-show="openMenus.stok" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 mt-1 space-y-0.5">
                    <a href="{{ route('stok.mutations.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('stok.mutations.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Kartu Stok</a>
                    <a href="{{ route('stok.opname') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('stok.opname') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Stock Opname</a>
                </div>
            </div>
            @endcanany

            <!-- Laporan -->
            @can('view_report')
            <a href="{{ route('laporan.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('laporan.*') ? 'bg-sidebar-active text-white shadow-sm' : 'text-slate-300 hover:bg-sidebar-hover hover:text-white' }}">
                <i class="bi bi-file-earmark-bar-graph text-lg w-5 text-center"></i>
                <span>Laporan</span>
            </a>
            @endcan

            <!-- Pengaturan -->
            @can('view_company_setting')
            <a href="{{ route('settings.company') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('settings.*') ? 'bg-sidebar-active text-white shadow-sm' : 'text-slate-300 hover:bg-sidebar-hover hover:text-white' }}">
                <i class="bi bi-gear text-lg w-5 text-center"></i>
                <span>Pengaturan</span>
            </a>
            @endcan

            <!-- Pengguna -->
            @canany(['view_user','view_role'])
            <div>
                <button @click="openMenus.pengguna = !openMenus.pengguna"
                        class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-slate-300 hover:bg-sidebar-hover hover:text-white">
                    <i class="bi bi-people text-lg w-5 text-center"></i>
                    <span class="flex-1 text-left">Pengguna</span>
                    <i class="bi bi-chevron-down text-xs transition-transform duration-200" :class="openMenus.pengguna && 'rotate-180'"></i>
                </button>
                <div x-show="openMenus.pengguna" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="ml-4 mt-1 space-y-0.5">
                    @can('view_user')
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('users.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Manajemen User</a>
                    @endcan
                    @can('view_role')
                    <a href="{{ route('roles.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-150 {{ request()->routeIs('roles.*') ? 'bg-sidebar-active text-white' : 'text-slate-400 hover:text-white hover:bg-sidebar-hover' }}">Role & Permission</a>
                    @endcan
                </div>
            </div>
            @endcanany
        </div>

        <!-- Bottom: Profile & Logout -->
        <div class="border-t border-white/5 px-3 py-3 space-y-1">
            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-slate-400 hover:bg-sidebar-hover hover:text-white">
                <i class="bi bi-person-circle text-lg w-5 text-center"></i>
                <span>Profil</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 w-full text-slate-400 hover:bg-red-500/10 hover:text-red-400">
                    <i class="bi bi-box-arrow-left text-lg w-5 text-center"></i>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>
</nav>
