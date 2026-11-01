<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
});
</script>

<template>
    <Head title="Portal Gudang Distro" />

    <div class="min-h-screen bg-slate-900 text-slate-100 flex flex-col justify-between selection:bg-amber-500 selection:text-white">
        <!-- 1. HEADER RINGKAS -->
        <header class="max-w-5xl w-full mx-auto px-6 py-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-lg bg-amber-500 flex items-center justify-center font-black text-slate-950 text-sm shadow-md shadow-amber-500/20">
                    D
                </span>
                <span class="font-extrabold tracking-tight text-white text-base">
                    DISTRO <span class="text-amber-500 font-normal">OPERATIONS</span>
                </span>
            </div>

            <nav v-if="canLogin">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('warehouse.dashboard')"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 transition"
                >
                    Dashboard Gudang →
                </Link>
                <Link
                    v-else
                    :href="route('login')"
                    class="text-xs font-semibold px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white transition border border-white/10"
                >
                    Masuk (Login)
                </Link>
            </nav>
        </header>

        <!-- 2. HERO UTAMA (FOKUS & BERSIH) -->
        <main class="max-w-3xl w-full mx-auto px-6 py-12 text-center space-y-8">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs font-medium">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Sistem Manajemen Stok & Pengawasan Gudang
            </div>

            <div class="space-y-4">
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight text-white leading-tight">
                    Kontrol Mutasi Baju <br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-amber-600">
                        Aman, Tepat & Anti-Bocor
                    </span>
                </h1>
                <p class="text-slate-400 text-sm sm:text-base max-w-xl mx-auto leading-relaxed">
                    Pencatatan penerimaan konveksi, verifikasi surat jalan, dan distribusi barang keluar dengan kartu stok abadi yang terpantau real-time.
                </p>
            </div>

            <!-- Single Call-to-Action (Tidak Ada Tombol Ganda) -->
            <div>
                <Link
                    :href="$page.props.auth.user ? route('warehouse.dashboard') : route('login')"
                    class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/25 transition cursor-pointer"
                >
                    {{ $page.props.auth.user ? 'Buka Dashboard Gudang' : 'Masuk ke Sistem Gudang' }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </Link>
            </div>

            <!-- 3 Ringkasan Nilai Utama (Informasi Saja, Bukan Tombol Duplikat) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-10 text-left border-t border-slate-800">
                <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-800">
                    <div class="text-amber-500 font-bold text-sm mb-1">01. Barang Masuk</div>
                    <p class="text-xs text-slate-400">Verifikasi fisik konveksi per ukuran & bukti foto nota.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-800">
                    <div class="text-amber-500 font-bold text-sm mb-1">02. Barang Keluar</div>
                    <p class="text-xs text-slate-400">Distribusi ke toko/reseller terikat nomor surat jalan resmi.</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-800">
                    <div class="text-amber-500 font-bold text-sm mb-1">03. Audit Abadi</div>
                    <p class="text-xs text-slate-400">Buku besar stok tidak bisa diedit/dihapus sembarangan.</p>
                </div>
            </div>
        </main>

        <!-- 3. FOOTER MINIMALIS -->
        <footer class="max-w-5xl w-full mx-auto px-6 py-6 text-center text-xs text-slate-500 border-t border-slate-800/60">
            © 2026 Distro Operations • Internal Warehouse Control System
        </footer>
    </div>
</template>
