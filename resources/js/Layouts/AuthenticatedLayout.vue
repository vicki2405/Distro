<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const sidebarOpen = ref(false);

// Submenu Master Produk default terbuka jika sedang di halaman products atau categories
const masterMenuOpen = ref(
    route().current('warehouse.products.*') || route().current('warehouse.categories.*')
);
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex selection:bg-amber-500 selection:text-white">
        <!-- BACKDROP MOBILE -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-black/80 backdrop-blur-xs md:hidden"
        ></div>

        <!-- SIDEBAR KIRI (KOMPAK & RAMPING w-60) -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-60 bg-slate-950 border-r border-slate-800/80 flex flex-col justify-between transition-transform duration-200 ease-in-out md:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- TOP BRAND -->
            <div>
                <div class="h-16 px-5 flex items-center justify-between border-b border-slate-800/80">
                    <Link :href="route('warehouse.dashboard')" class="flex items-center gap-2.5 group">
                        <span class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center font-black text-slate-950 text-sm shadow-md shadow-amber-500/20 group-hover:scale-105 transition">
                            D
                        </span>
                        <div>
                            <div class="font-black text-sm tracking-tight text-white leading-none">
                                DISTRO <span class="text-amber-500 font-normal">OPS</span>
                            </div>
                            <div class="text-[9px] text-slate-400 font-mono tracking-wider mt-0.5">
                                GUDANG & LOGISTIK
                            </div>
                        </div>
                    </Link>
                    <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white p-1">
                        ✕
                    </button>
                </div>

                <!-- NAVIGATION MENU DIKELOMPOKKAN SESUAI FUNGSI -->
                <div class="p-3.5 space-y-4 overflow-y-auto max-h-[calc(100vh-140px)]">

                    <!-- 1. DASHBOARD -->
                    <div>
                        <Link
                            :href="route('warehouse.dashboard')"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition',
                                route().current('warehouse.dashboard')
                                    ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs'
                                    : 'text-slate-400 hover:text-white hover:bg-slate-900'
                            ]"
                        >
                            <span class="text-sm">📊</span>
                            <span>Dashboard Stok</span>
                        </Link>
                    </div>

                    <!-- 2. KELOMPOK: MASTER DATA -->
                    <div class="space-y-1">
                        <div class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Master Data
                        </div>

                        <!-- MASTER PRODUK DENGAN SUB-MENU -->
                        <div class="space-y-1">
                            <button
                                type="button"
                                @click="masterMenuOpen = !masterMenuOpen"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition cursor-pointer',
                                    (route().current('warehouse.products.*') || route().current('warehouse.categories.*'))
                                        ? 'bg-purple-500/15 text-purple-400 border border-purple-500/30'
                                        : 'text-slate-400 hover:text-white hover:bg-slate-900'
                                ]"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span class="text-sm">👕</span>
                                    <span>Master Produk</span>
                                </div>
                                <span class="text-[10px] transition-transform duration-200" :class="{ 'rotate-180': masterMenuOpen }">▼</span>
                            </button>

                            <!-- SUB-MENU DAFTAR PRODUK & KATEGORI PRODUK -->
                            <div v-show="masterMenuOpen" class="pl-4 pr-1 space-y-1 pt-0.5">
                                <Link
                                    :href="route('warehouse.products.index')"
                                    :class="[
                                        'block px-3 py-1.5 rounded-lg text-xs font-medium transition',
                                        route().current('warehouse.products.*')
                                            ? 'bg-slate-800 text-purple-400 font-bold border border-slate-700/60'
                                            : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'
                                    ]"
                                >
                                    • Daftar Produk
                                </Link>

                                <Link
                                    :href="route('warehouse.categories.index')"
                                    :class="[
                                        'block px-3 py-1.5 rounded-lg text-xs font-medium transition',
                                        route().current('warehouse.categories.*')
                                            ? 'bg-slate-800 text-purple-400 font-bold border border-slate-700/60'
                                            : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'
                                    ]"
                                >
                                    • Kategori Produk
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- 3. KELOMPOK: TRANSAKSI GUDANG -->
                    <div class="space-y-1">
                        <div class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Transaksi Gudang
                        </div>

                        <Link
                            :href="route('warehouse.in.create')"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition',
                                route().current('warehouse.in.create')
                                    ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 shadow-xs'
                                    : 'text-slate-400 hover:text-white hover:bg-slate-900'
                            ]"
                        >
                            <span class="text-sm">📥</span>
                            <span>Barang Masuk</span>
                        </Link>

                        <Link
                            :href="route('warehouse.out.create')"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition',
                                route().current('warehouse.out.create')
                                    ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30 shadow-xs'
                                    : 'text-slate-400 hover:text-white hover:bg-slate-900'
                            ]"
                        >
                            <span class="text-sm">📤</span>
                            <span>Barang Keluar</span>
                        </Link>
                    </div>

                    <!-- 4. KELOMPOK: AUDIT & LAPORAN -->
                    <div class="space-y-1">
                        <div class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Audit & Laporan
                        </div>

                        <Link
                            :href="route('warehouse.movements')"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold transition',
                                route().current('warehouse.movements')
                                    ? 'bg-sky-500/15 text-sky-400 border border-sky-500/30 shadow-xs'
                                    : 'text-slate-400 hover:text-white hover:bg-slate-900'
                            ]"
                        >
                            <span class="text-sm">📜</span>
                            <span>Riwayat Mutasi</span>
                        </Link>
                    </div>

                </div>
            </div>

            <!-- FOOTER SIDEBAR (PROFIL OPERATOR & LOGOUT) -->
            <div class="p-3.5 border-t border-slate-800/80 bg-slate-900/30">
                <div class="flex items-center justify-between mb-2">
                    <div class="truncate pr-1">
                        <div class="text-xs font-bold text-white truncate">{{ $page.props.auth.user.name }}</div>
                        <div class="text-[10px] text-slate-400 truncate">{{ $page.props.auth.user.email }}</div>
                    </div>
                    <span
                        v-if="$page.props.auth.user.role === 'owner'"
                        class="px-2 py-0.5 text-[10px] font-black rounded-md bg-amber-500/20 text-amber-400 border border-amber-500/40 uppercase"
                    >
                        OWNER
                    </span>
                    <span
                        v-else
                        class="px-2 py-0.5 text-[10px] font-black rounded-md bg-slate-800 text-slate-300 border border-slate-700 uppercase"
                    >
                        GUDANG
                    </span>
                </div>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="w-full mt-1.5 flex items-center justify-center gap-2 py-1.5 px-3 rounded-lg bg-slate-900 hover:bg-rose-500/15 hover:text-rose-400 text-slate-400 text-xs font-semibold border border-slate-800 hover:border-rose-500/30 transition cursor-pointer"
                >
                    🚪 Keluar Akun
                </Link>
            </div>
        </aside>

        <!-- AREA KERJA UTAMA (LEBAR, LEGA & TIDAK SEMPIT) -->
        <div class="flex-1 flex flex-col md:pl-60 min-w-0 w-full">
            <!-- MOBILE TOPBAR -->
            <header class="h-14 px-4 md:hidden border-b border-slate-800/80 bg-slate-950 flex items-center justify-between sticky top-0 z-30">
                <button
                    @click="sidebarOpen = true"
                    class="p-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-white"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="font-bold text-xs tracking-wider text-white">DISTRO OPERATIONS</div>
                <div class="w-6"></div>
            </header>

            <!-- SUB-HEADER SLOTS -->
            <div v-if="$slots.header" class="border-b border-slate-800/80 bg-slate-900/20 py-5 px-6 sm:px-8 lg:px-10">
                <slot name="header" />
            </div>

            <!-- WORKSPACE KONTEN UTAMA -->
            <main class="flex-1 p-6 sm:p-8 lg:p-10 w-full max-w-[1500px] mx-auto">
                <slot />
            </main>

            <!-- FOOTER -->
            <footer class="border-t border-slate-900 py-4 px-6 sm:px-8 text-center text-[11px] text-slate-600">
                Distro Operations — Small-Scale Warehouse & Distribution Control Center
            </footer>
        </div>
    </div>
</template>
