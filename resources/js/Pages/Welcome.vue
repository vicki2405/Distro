<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
});
</script>

<template>
    <Head title="Distro Operations — Sistem Manajemen Gudang" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-between selection:bg-amber-500 selection:text-white relative overflow-hidden">
        
        <!-- 1. AMBIENT BACKGROUND GLOW & MESH (ANIMASI HALUS) -->
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[500px] bg-gradient-to-b from-amber-500/15 via-purple-500/10 to-transparent rounded-full blur-3xl pointer-events-none animate-pulse"></div>
        <div class="absolute -bottom-40 right-10 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- 2. NAVBAR MINIMALIS (FOKUS & BERSIH) -->
        <header class="relative z-10 max-w-6xl w-full mx-auto px-6 py-6 sm:py-8 flex items-center justify-between">
            <div class="flex items-center gap-3 group">
                <span class="w-9 h-9 rounded-xl bg-amber-500 flex items-center justify-center font-black text-slate-950 text-base shadow-lg shadow-amber-500/25 group-hover:scale-105 transition-transform duration-300">
                    D
                </span>
                <div>
                    <div class="font-black tracking-tight text-white text-base leading-none">
                        DISTRO <span class="text-amber-500 font-normal">OPERATIONS</span>
                    </div>
                    <div class="text-[9px] text-slate-400 font-mono tracking-wider mt-0.5">
                        INTERNAL WMS
                    </div>
                </div>
            </div>

            <!-- Informasi Status Sistem (Tanpa Tombol Ganda) -->
            <div class="hidden sm:flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[11px] font-mono text-slate-400">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span>SYSTEM ONLINE</span>
            </div>
        </header>

        <!-- 3. HERO UTAMA BERANIMASI -->
        <main class="relative z-10 max-w-4xl w-full mx-auto px-6 py-10 sm:py-16 text-center space-y-8">
            
            <!-- Live Operational Beacon Badge -->
            <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-slate-900/80 border border-slate-800 text-xs font-medium backdrop-blur-md shadow-inner">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-slate-300 font-mono text-[11px] tracking-wide">
                    Live Warehouse Engine • Real-Time Inventory
                </span>
            </div>

            <!-- Heading Heroik -->
            <div class="space-y-4">
                <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-white leading-tight">
                    Kontrol Mutasi Stok <br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-amber-300 to-amber-500">
                        Aman, Presisi & Anti-Bocor
                    </span>
                </h1>
                <p class="text-slate-400 text-sm sm:text-base max-w-2xl mx-auto leading-relaxed">
                    Sistem pengawasan pergudangan pakaian distro. Verifikasi penerimaan konveksi, pencocokan surat jalan, dan buku besar audit abadi yang terpantau real-time.
                </p>
            </div>

            <!-- Single Primary Call-to-Action (Fokus, Tanpa Tombol Ganda) -->
            <div class="pt-2">
                <Link
                    :href="$page.props.auth.user ? route('warehouse.dashboard') : route('login')"
                    class="group inline-flex items-center gap-2.5 px-7 py-3.5 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-sm shadow-xl shadow-amber-500/20 hover:shadow-amber-500/35 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 cursor-pointer"
                >
                    <span>{{ $page.props.auth.user ? 'Buka Dashboard Gudang' : 'Masuk ke Sistem Gudang' }}</span>
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </Link>
            </div>

            <!-- 4. VISUAL OPERASIONAL INTERAKTIF (PREVIEW LIVE STREAM) -->
            <div class="pt-6">
                <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-800/80 rounded-3xl p-5 sm:p-6 shadow-2xl max-w-2xl mx-auto text-left space-y-4 hover:border-slate-700/80 transition-all duration-300">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800/80">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500/80"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500/80"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></span>
                            <span class="text-[11px] font-mono text-slate-400 ml-2">audit_ledger_stream.log</span>
                        </div>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            SYNC ACTIVE
                        </span>
                    </div>

                    <!-- Mock Live Activity Rows -->
                    <div class="space-y-2.5 text-xs font-mono">
                        <div class="p-2.5 rounded-xl bg-slate-950/70 border border-slate-850 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                    INBOUND
                                </span>
                                <span class="text-slate-200">Restock Konveksi: Kaos Oversize "Black Metal" (L)</span>
                            </div>
                            <span class="text-emerald-400 font-bold">+45 pcs</span>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-950/70 border border-slate-850 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-amber-500/15 text-amber-400 border border-amber-500/30">
                                    DISPATCH
                                </span>
                                <span class="text-slate-200">Kirim Toko Distro: Hoodie Boxy "Onyx Heavy" (XL)</span>
                            </div>
                            <span class="text-amber-400 font-bold">-12 pcs</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. TIGA PILAR SISTEM (HOVER LIFT INTERACTIVE CARDS) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6 text-left">
                <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-amber-500/40 hover:bg-slate-900/80 hover:-translate-y-1.5 transition-all duration-300 space-y-2 shadow-lg">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 flex items-center justify-center text-sm font-bold">
                        📥
                    </div>
                    <div class="text-white font-bold text-sm">Penerimaan Konveksi</div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Pencatatan baju masuk fisik per ukuran disertai upload foto fisik nota surat jalan.
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-amber-500/40 hover:bg-slate-900/80 hover:-translate-y-1.5 transition-all duration-300 space-y-2 shadow-lg">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/15 text-amber-400 border border-amber-500/30 flex items-center justify-center text-sm font-bold">
                        📤
                    </div>
                    <div class="text-white font-bold text-sm">Distribusi & Kiriman</div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Validasi ketat sisa stok rak sebelum pengiriman pesanan toko, reseller, atau online.
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 hover:border-amber-500/40 hover:bg-slate-900/80 hover:-translate-y-1.5 transition-all duration-300 space-y-2 shadow-lg">
                    <div class="w-8 h-8 rounded-lg bg-purple-500/15 text-purple-400 border border-purple-500/30 flex items-center justify-center text-sm font-bold">
                        📜
                    </div>
                    <div class="text-white font-bold text-sm">Buku Besar Audit</div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Jejak transaksi abadi yang immutable, tidak dapat diedit atau dihapus sembarangan.
                    </p>
                </div>
            </div>

        </main>

        <!-- 6. FOOTER BERSIH -->
        <footer class="relative z-10 max-w-6xl w-full mx-auto px-6 py-6 text-center text-xs text-slate-500 border-t border-slate-900">
            © 2026 Distro Operations • Internal Warehouse Management System
        </footer>

    </div>
</template>
