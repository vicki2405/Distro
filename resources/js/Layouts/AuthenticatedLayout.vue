<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const sidebarOpen = ref(false);

// Submenu Master Produk default terbuka jika sedang di halaman products, categories, atau sizes
const masterMenuOpen = ref(
    route().current('warehouse.products.*') || route().current('warehouse.categories.*') || route().current('warehouse.sizes.*')
);

// Submenu Stock Opname default terbuka jika sedang di halaman opnames
const opnameMenuOpen = ref(
    route().current('warehouse.opnames.*')
);
</script>

<template>
    <!-- WORKSPACE ELEGAN DENGAN LIVING AMBIENT AURORA GRADIENT PADA SELURUH SISTEM & SIDEBAR -->
    <div class="min-h-screen living-bg-mesh text-slate-100 flex selection:bg-amber-500 selection:text-white relative overflow-x-hidden">
        <!-- BACKDROP MOBILE -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-xs md:hidden"
        ></div>

        <!-- SIDEBAR KIRI (HARMONIS, LEBIH TERANG & MEMILIKI GRADASI GLOW LIVING) -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-60 bg-slate-900/75 backdrop-blur-2xl border-r border-slate-700/70 flex flex-col justify-between transition-transform duration-300 ease-in-out md:translate-x-0 shadow-2xl overflow-hidden',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- ORB CAHAYA PENDARAN DI DALAM SIDEBAR (MEMPERTERANG SIDEBAR SECARA ELEGAN) -->
            <div class="absolute -top-14 -left-14 w-48 h-48 bg-white/15 rounded-full blur-2xl pointer-events-none animate-aurora-1"></div>
            <div class="absolute bottom-28 -right-10 w-44 h-44 bg-purple-500/20 rounded-full blur-2xl pointer-events-none animate-aurora-2"></div>

            <!-- TOP BRAND -->
            <div class="relative z-10">
                <div class="h-16 px-5 flex items-center justify-between border-b border-slate-700/60 bg-slate-900/50 backdrop-blur-md">
                    <Link :href="route('warehouse.dashboard')" class="flex items-center gap-2.5 group">
                        <span class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center font-black text-slate-950 text-sm shadow-md shadow-amber-500/30 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300">
                            D
                        </span>
                        <div>
                            <div class="font-black text-sm tracking-tight text-white leading-none group-hover:text-amber-400 transition-colors">
                                DISTRO <span class="text-amber-500 font-normal">OPS</span>
                            </div>
                            <div class="text-[9px] text-slate-300 font-mono tracking-wider mt-0.5">
                                GUDANG & LOGISTIK
                            </div>
                        </div>
                    </Link>
                    <button @click="sidebarOpen = false" class="md:hidden text-slate-300 hover:text-white p-1">
                        ✕
                    </button>
                </div>

                <!-- NAVIGATION MENU DENGAN WARNA LEBIH TERANG & KONTRAST TINGGI -->
                <div class="p-3.5 space-y-4 overflow-y-auto max-h-[calc(100vh-140px)]">

                    <!-- 1. DASHBOARD -->
                    <div>
                        <Link
                            :href="route('warehouse.dashboard')"
                            :class="[
                                'group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 hover:translate-x-1.5 active:scale-95',
                                route().current('warehouse.dashboard')
                                    ? 'bg-amber-500/25 text-amber-300 border border-amber-500/50 shadow-md font-bold'
                                    : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border border-transparent hover:border-slate-700/50'
                            ]"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="text-sm transition-transform duration-200 group-hover:scale-115 group-hover:rotate-6">📊</span>
                                <span>Dashboard Stok</span>
                            </div>
                            <span
                                v-if="route().current('warehouse.dashboard')"
                                class="w-1.5 h-3.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"
                            ></span>
                        </Link>
                    </div>

                    <!-- 2. KELOMPOK: MASTER DATA -->
                    <div class="space-y-1">
                        <div class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Master Data
                        </div>

                        <!-- MASTER PRODUK DENGAN SUB-MENU -->
                        <div class="space-y-1">
                            <button
                                type="button"
                                @click="masterMenuOpen = !masterMenuOpen"
                                :class="[
                                    'group w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 hover:translate-x-1 cursor-pointer active:scale-95',
                                    (route().current('warehouse.products.*') || route().current('warehouse.categories.*') || route().current('warehouse.sizes.*'))
                                        ? 'bg-purple-500/25 text-purple-300 border border-purple-500/50 font-bold shadow-md'
                                        : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border border-transparent hover:border-slate-700/50'
                                ]"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span class="text-sm transition-transform duration-200 group-hover:scale-115">👕</span>
                                    <span>Master Produk</span>
                                </div>
                                <span
                                    class="text-[10px] transition-transform duration-300"
                                    :class="{ 'rotate-180 text-purple-400': masterMenuOpen, 'text-slate-400': !masterMenuOpen }"
                                >
                                    ▼
                                </span>
                            </button>

                            <!-- SUB-MENU DAFTAR PRODUK, KATEGORI & UKURAN -->
                            <div
                                v-show="masterMenuOpen"
                                class="pl-3.5 pr-1 space-y-1 pt-0.5 border-l border-slate-700/70 ml-4 transition-all duration-300"
                            >
                                <Link
                                    :href="route('warehouse.products.index')"
                                    :class="[
                                        'group flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 hover:translate-x-1',
                                        route().current('warehouse.products.*')
                                            ? 'bg-slate-800/90 text-purple-300 font-bold border border-purple-500/40 shadow-xs'
                                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                                    ]"
                                >
                                    <span>• Daftar Produk</span>
                                    <span v-if="route().current('warehouse.products.*')" class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                                </Link>

                                <Link
                                    :href="route('warehouse.categories.index')"
                                    :class="[
                                        'group flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 hover:translate-x-1',
                                        route().current('warehouse.categories.*')
                                            ? 'bg-slate-800/90 text-purple-300 font-bold border border-purple-500/40 shadow-xs'
                                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                                    ]"
                                >
                                    <span>• Kategori Produk</span>
                                    <span v-if="route().current('warehouse.categories.*')" class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                                </Link>

                                <Link
                                    :href="route('warehouse.sizes.index')"
                                    :class="[
                                        'group flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 hover:translate-x-1',
                                        route().current('warehouse.sizes.*')
                                            ? 'bg-slate-800/90 text-purple-300 font-bold border border-purple-500/40 shadow-xs'
                                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                                    ]"
                                >
                                    <span>• Size</span>
                                    <span v-if="route().current('warehouse.sizes.*')" class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- 3. KELOMPOK: TRANSAKSI GUDANG -->
                    <div class="space-y-1">
                        <div class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Transaksi Gudang
                        </div>

                        <Link
                            :href="route('warehouse.in.create')"
                            :class="[
                                'group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 hover:translate-x-1.5 active:scale-95',
                                route().current('warehouse.in.create')
                                    ? 'bg-emerald-500/25 text-emerald-300 border border-emerald-500/50 shadow-md font-bold'
                                    : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border border-transparent hover:border-slate-700/50'
                            ]"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="text-sm transition-transform duration-200 group-hover:scale-115">📥</span>
                                <span>Barang Masuk</span>
                            </div>
                            <span
                                v-if="route().current('warehouse.in.create')"
                                class="w-1.5 h-3.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50"
                            ></span>
                        </Link>

                        <Link
                            :href="route('warehouse.out.create')"
                            :class="[
                                'group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 hover:translate-x-1.5 active:scale-95',
                                route().current('warehouse.out.create')
                                    ? 'bg-amber-500/25 text-amber-300 border border-amber-500/50 shadow-md font-bold'
                                    : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border border-transparent hover:border-slate-700/50'
                            ]"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="text-sm transition-transform duration-200 group-hover:scale-115">📤</span>
                                <span>Barang Keluar</span>
                            </div>
                            <span
                                v-if="route().current('warehouse.out.create')"
                                class="w-1.5 h-3.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"
                            ></span>
                        </Link>
                    </div>

                    <!-- 4. KELOMPOK: AUDIT & LAPORAN -->
                    <div class="space-y-1">
                        <div class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Audit & Laporan
                        </div>

                        <!-- STOCK OPNAME DENGAN SUB-MENU RESMI RETAIL -->
                        <div class="space-y-1">
                            <button
                                type="button"
                                @click="opnameMenuOpen = !opnameMenuOpen"
                                :class="[
                                    'group w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 hover:translate-x-1 cursor-pointer active:scale-95',
                                    route().current('warehouse.opnames.*')
                                        ? 'bg-sky-500/25 text-sky-300 border border-sky-500/50 font-bold shadow-md'
                                        : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border border-transparent hover:border-slate-700/50'
                                ]"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span class="text-sm transition-transform duration-200 group-hover:scale-115">📋</span>
                                    <span>Stock Opname (SO)</span>
                                </div>
                                <span
                                    class="text-[10px] transition-transform duration-300"
                                    :class="{ 'rotate-180 text-sky-400': opnameMenuOpen, 'text-slate-400': !opnameMenuOpen }"
                                >
                                    ▼
                                </span>
                            </button>

                            <!-- SUB-MENU RESMI 3 TAHAP AUDIT -->
                            <div
                                v-show="opnameMenuOpen"
                                class="pl-3.5 pr-1 space-y-1 pt-0.5 border-l border-slate-700/70 ml-4 transition-all duration-300"
                            >
                                <Link
                                    :href="route('warehouse.opnames.index')"
                                    :class="[
                                        'group flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 hover:translate-x-1',
                                        route().current('warehouse.opnames.index')
                                            ? 'bg-slate-800/90 text-sky-300 font-bold border border-sky-500/40 shadow-xs'
                                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                                    ]"
                                >
                                    <span>• Hasil & Rekonsiliasi</span>
                                    <span v-if="route().current('warehouse.opnames.index')" class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                                </Link>

                                <Link
                                    :href="route('warehouse.opnames.sheet')"
                                    :class="[
                                        'group flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 hover:translate-x-1',
                                        route().current('warehouse.opnames.sheet')
                                            ? 'bg-slate-800/90 text-amber-300 font-bold border border-amber-500/40 shadow-xs'
                                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                                    ]"
                                >
                                    <span>• Cetak Berkas SO</span>
                                    <span v-if="route().current('warehouse.opnames.sheet')" class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                </Link>

                                <Link
                                    :href="route('warehouse.opnames.create')"
                                    :class="[
                                        'group flex items-center justify-between px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-200 hover:translate-x-1',
                                        route().current('warehouse.opnames.create')
                                            ? 'bg-slate-800/90 text-indigo-300 font-bold border border-indigo-500/40 shadow-xs'
                                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                                    ]"
                                >
                                    <span>• Input Hasil SO</span>
                                    <span v-if="route().current('warehouse.opnames.create')" class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- 5. KELOMPOK: ADMINISTRASI (KHUSUS OWNER) -->
                    <div v-if="$page.props.auth?.is_owner || $page.props.auth?.user?.role === 'owner'" class="space-y-1">
                        <div class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Administrasi
                        </div>

                        <Link
                            :href="route('warehouse.users.index')"
                            :class="[
                                'group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 hover:translate-x-1.5 active:scale-95',
                                route().current('warehouse.users.*')
                                    ? 'bg-rose-500/25 text-rose-300 border border-rose-500/50 shadow-md font-bold'
                                    : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border border-transparent hover:border-slate-700/50'
                            ]"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="text-sm transition-transform duration-200 group-hover:scale-115">👥</span>
                                <span>Kelola Pengguna</span>
                            </div>
                            <span
                                v-if="route().current('warehouse.users.*')"
                                class="w-1.5 h-3.5 rounded-full bg-rose-400 shadow-sm shadow-rose-400/50"
                            ></span>
                        </Link>

                        <Link
                            :href="route('warehouse.roles.index')"
                            :class="[
                                'group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 hover:translate-x-1.5 active:scale-95',
                                route().current('warehouse.roles.*')
                                    ? 'bg-amber-500/25 text-amber-300 border border-amber-500/50 shadow-md font-bold'
                                    : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border border-transparent hover:border-slate-700/50'
                            ]"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="text-sm transition-transform duration-200 group-hover:scale-115">🛡️</span>
                                <span>Role & Hak Akses</span>
                            </div>
                            <span
                                v-if="route().current('warehouse.roles.*')"
                                class="w-1.5 h-3.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"
                            ></span>
                        </Link>

                        <Link
                            :href="route('warehouse.settings.index')"
                            :class="[
                                'group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 hover:translate-x-1.5 active:scale-95',
                                route().current('warehouse.settings.*')
                                    ? 'bg-indigo-500/25 text-indigo-300 border border-indigo-500/50 shadow-md font-bold'
                                    : 'text-slate-300 hover:text-white hover:bg-slate-800/70 border border-transparent hover:border-slate-700/50'
                            ]"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="text-sm transition-transform duration-200 group-hover:scale-115">⚙️</span>
                                <span>Pengaturan Toko</span>
                            </div>
                            <span
                                v-if="route().current('warehouse.settings.*')"
                                class="w-1.5 h-3.5 rounded-full bg-indigo-400 shadow-sm shadow-indigo-400/50"
                            ></span>
                        </Link>
                    </div>

                </div>
            </div>

            <!-- FOOTER SIDEBAR (PROFIL OPERATOR & LOGOUT - HANYA NAMA) -->
            <div class="p-3.5 border-t border-slate-700/60 bg-slate-900/60 backdrop-blur-md relative z-10">
                <div class="flex items-center justify-between mb-2">
                    <div class="truncate pr-1">
                        <div class="text-xs font-bold text-white truncate">{{ $page.props.auth?.user?.name || '-' }}</div>
                        <div class="text-[10px] text-slate-300 font-mono">
                            {{ ($page.props.auth?.is_owner || $page.props.auth?.user?.role === 'owner') ? 'Owner / Admin' : 'Petugas Gudang' }}
                        </div>
                    </div>
                    <span
                        v-if="$page.props.auth?.is_owner || $page.props.auth?.user?.role === 'owner'"
                        class="px-2 py-0.5 text-[10px] font-black rounded-md bg-amber-500/25 text-amber-300 border border-amber-500/50 uppercase shadow-xs"
                    >
                        OWNER
                    </span>
                    <span
                        v-else
                        class="px-2 py-0.5 text-[10px] font-black rounded-md bg-slate-800 text-slate-200 border border-slate-600 uppercase shadow-xs"
                    >
                        GUDANG
                    </span>
                </div>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full mt-1.5 flex items-center justify-center gap-2 py-1.5 px-3 rounded-lg bg-slate-800/90 hover:bg-rose-500/20 hover:text-rose-300 text-slate-200 text-xs font-semibold border border-slate-700/80 hover:border-rose-500/40 transition cursor-pointer active:scale-95 shadow-xs"
                >
                    🚪 Keluar Akun
                </Link>
            </div>
        </aside>

        <!-- AREA KERJA UTAMA DENGAN LIVING AMBIENT AURORA GRADIENT -->
        <div class="flex-1 flex flex-col md:pl-60 min-w-0 w-full living-bg-mesh relative min-h-screen overflow-x-hidden">
            
            <!-- BACKGROUND LIVING AURORA GRADIENT ORBS (BERGERAK DINAMIS & TERANG) -->
            <div class="fixed inset-0 md:left-60 pointer-events-none overflow-hidden z-0">
                <!-- Orb 1: Pendaran Cahaya Putih Terang Utama (Melayang Halus) -->
                <div class="absolute -top-28 left-1/4 w-[550px] h-[550px] bg-gradient-to-br from-white/35 via-sky-200/20 to-transparent rounded-full blur-2xl animate-aurora-1"></div>

                <!-- Orb 2: Pendaran Cahaya Ambient Hangat (Bernapas Dinamis) -->
                <div class="absolute top-1/4 -right-24 w-[500px] h-[500px] bg-gradient-to-bl from-indigo-400/25 via-purple-300/15 to-transparent rounded-full blur-2xl animate-aurora-2"></div>

                <!-- Orb 3: Pendaran Cahaya Bawah (Drift Halus) -->
                <div class="absolute bottom-10 left-1/3 w-[520px] h-[520px] bg-gradient-to-tr from-cyan-300/25 via-white/20 to-transparent rounded-full blur-2xl animate-aurora-3"></div>
            </div>

            <!-- MOBILE TOPBAR -->
            <header class="h-14 px-4 md:hidden border-b border-slate-800 bg-slate-900/90 backdrop-blur-md flex items-center justify-between sticky top-0 z-30 shadow-md">
                <button
                    @click="sidebarOpen = true"
                    class="p-1.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-300 hover:text-white"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="font-bold text-xs tracking-wider text-white">DISTRO OPERATIONS</div>
                <div class="w-6"></div>
            </header>

            <!-- SUB-HEADER SLOTS (BERSIH DENGAN BLUR KACA TRANSPARAN & SHIMMER LINE AKTIF) -->
            <div v-if="$slots.header" class="relative z-10 border-b border-slate-800/80 bg-slate-900/50 backdrop-blur-md py-5 px-6 sm:px-8 lg:px-10 overflow-hidden">
                <div class="absolute inset-x-0 bottom-0 h-[1.5px] bg-slate-800/80 overflow-hidden">
                    <div class="w-1/3 h-full bg-gradient-to-r from-transparent via-white/70 to-transparent animate-shimmer"></div>
                </div>
                <slot name="header" />
            </div>

            <!-- WORKSPACE KONTEN UTAMA -->
            <main class="relative z-10 flex-1 p-6 sm:p-8 lg:p-10 w-full max-w-[1500px] mx-auto">
                <slot />
            </main>

            <!-- FOOTER -->
            <footer class="relative z-10 border-t border-slate-800/60 py-4 px-6 sm:px-8 text-center text-[11px] text-slate-400 bg-slate-900/50 backdrop-blur-sm">
                Distro Operations — Small-Scale Warehouse & Distribution Control Center
            </footer>
        </div>
    </div>
</template>
