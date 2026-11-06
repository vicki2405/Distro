<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    metrics: Object,
    products: Object,
    recentMovements: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const showAssetValue = ref(false);

const handleSearch = () => {
    router.get(route('warehouse.dashboard'), { search: search.value }, { preserveState: true, replace: true });
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};

const formatTime = (isoString) => {
    const d = new Date(isoString);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Stok & Gudang Distro" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span class="text-[11px] font-mono uppercase tracking-widest text-slate-400">Inventory Status: Live</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Pusat Kontrol Stok Gudang
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Pemantauan stok fisik per ukuran, pencegahan kebocoran barang, dan riwayat mutasi real-time.
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('warehouse.in.create')"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/40 text-emerald-400 hover:text-emerald-300 text-xs font-bold transition shadow-xs cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        + Barang Masuk
                    </Link>
                    <Link
                        :href="route('warehouse.out.create')"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/40 text-amber-400 hover:text-amber-300 text-xs font-bold transition shadow-xs cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        Barang Keluar
                    </Link>
                </div>
            </div>
        </template>

        <!-- KONTEN UTAMA LEGA (TIDAK SEMPIT) -->
        <div class="space-y-6">

            <!-- 1. METRIC STAT CARDS LEBAR -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Total Pcs -->
                <div class="bg-slate-900/70 backdrop-blur-md p-5 sm:p-6 rounded-2xl border border-slate-800/80 space-y-2 hover:border-slate-700 transition">
                    <div class="flex items-center justify-between text-slate-400 text-[11px] font-bold uppercase tracking-wider">
                        <span>Total Stok Fisik</span>
                        <span class="p-1.5 rounded-lg bg-slate-800 text-slate-300">📦</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-white tabular-nums tracking-tight">
                        {{ metrics.total_pieces }} <span class="text-xs font-medium text-slate-400">pcs</span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        Tersebar di <span class="text-slate-300 font-semibold">{{ metrics.total_variants }} varian</span> ukuran
                    </div>
                </div>

                <!-- Nilai Modal Gudang (Khusus Owner) / Retail Value (Staff) -->
                <div class="bg-slate-900/70 backdrop-blur-md p-5 sm:p-6 rounded-2xl border border-slate-800/80 space-y-2 hover:border-slate-700 transition">
                    <div class="flex items-center justify-between text-slate-400 text-[11px] font-bold uppercase tracking-wider">
                        <span v-if="metrics.is_owner">Nilai Modal (HPP)</span>
                        <span v-else>Potensi Omset Jual</span>
                        <button
                            v-if="metrics.is_owner"
                            @click="showAssetValue = !showAssetValue"
                            class="text-slate-400 hover:text-amber-400 cursor-pointer text-xs p-1 rounded-md hover:bg-slate-800 transition"
                            title="Sembunyikan/Tampilkan Nominal"
                        >
                            {{ showAssetValue ? '👁️' : '🙈' }}
                        </button>
                        <span v-else class="p-1.5 rounded-lg bg-slate-800">💰</span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-amber-400 tabular-nums tracking-tight">
                        <template v-if="metrics.is_owner">
                            {{ showAssetValue ? formatRupiah(metrics.total_cost_value) : 'Rp ••••••••' }}
                        </template>
                        <template v-else>
                            {{ formatRupiah(metrics.total_selling_value) }}
                        </template>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        <span v-if="metrics.is_owner">Total nilai aset modal</span>
                        <span v-else>Estimasi retail value</span>
                    </div>
                </div>

                <!-- Keluar Hari Ini -->
                <div class="bg-slate-900/70 backdrop-blur-md p-5 sm:p-6 rounded-2xl border border-slate-800/80 space-y-2 hover:border-slate-700 transition">
                    <div class="flex items-center justify-between text-slate-400 text-[11px] font-bold uppercase tracking-wider">
                        <span>Keluar Hari Ini</span>
                        <span class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[10px] font-black">OUT</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-amber-400 tabular-nums tracking-tight">
                        {{ metrics.today_out }} <span class="text-xs font-medium text-slate-400">pcs</span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        Terkirim ke toko / marketplace
                    </div>
                </div>

                <!-- Masuk Hari Ini -->
                <div class="bg-slate-900/70 backdrop-blur-md p-5 sm:p-6 rounded-2xl border border-slate-800/80 space-y-2 hover:border-slate-700 transition">
                    <div class="flex items-center justify-between text-slate-400 text-[11px] font-bold uppercase tracking-wider">
                        <span>Masuk Hari Ini</span>
                        <span class="px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-black">IN</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-400 tabular-nums tracking-tight">
                        {{ metrics.today_in }} <span class="text-xs font-medium text-slate-400">pcs</span>
                    </div>
                    <div class="text-[11px] text-slate-400">
                        Diterima dari konveksi
                    </div>
                </div>
            </div>

            <!-- 2. STOK KRITIS ALERT -->
            <div v-if="metrics.low_stock_count > 0" class="p-4 rounded-2xl bg-rose-950/40 border border-rose-800/80 flex items-center justify-between gap-3 text-rose-300 text-xs">
                <div class="flex items-center gap-2.5">
                    <span class="p-1.5 rounded-lg bg-rose-900/50 text-rose-400 text-base">⚠️</span>
                    <span>Ada <strong>{{ metrics.low_stock_count }} varian ukuran</strong> dengan stok menipis (sisa 5 pcs atau kurang). Harap segera koordinasi restock konveksi!</span>
                </div>
            </div>

            <!-- 3. MAIN WORKSPACE: KATALOG STOK (LEBAR) & MUTASI TERKINI -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                <!-- KOLOM KIRI (2 Kolom di layar lebar): DAFTAR ARTIKEL & MATRIKS STOK UKURAN -->
                <div class="xl:col-span-2 bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h3 class="font-bold text-white text-base">Katalog Baju & Matriks Stok</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Rincian ketersediaan stok fisik per ukuran (S - XXL).</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="search"
                                @keyup.enter="handleSearch"
                                type="text"
                                placeholder="Cari nama artikel baju..."
                                class="text-xs px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-500 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 w-48 sm:w-64 transition"
                            />
                            <button
                                @click="handleSearch"
                                class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold cursor-pointer transition border border-slate-700"
                            >
                                Cari
                            </button>
                        </div>
                    </div>

                    <!-- LIST PRODUK LEGA -->
                    <div class="divide-y divide-slate-800/60">
                        <div
                            v-for="product in products.data"
                            :key="product.id"
                            class="p-5 sm:p-6 hover:bg-slate-800/30 transition"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-3.5">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-14 h-14 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-center shrink-0 overflow-hidden shadow-inner">
                                        <img
                                            v-if="product.image_url"
                                            :src="product.image_url"
                                            :alt="product.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else class="text-slate-600 font-mono text-[10px] font-bold">
                                            NO FOTO
                                        </span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700/60">
                                                {{ product.category?.name }}
                                            </span>
                                        </div>
                                        <h4 class="font-bold text-white text-base mt-1.5">
                                            {{ product.name }}
                                        </h4>
                                    </div>
                                </div>
                                <div class="text-left sm:text-right shrink-0">
                                    <div class="text-[11px] text-slate-400">Harga Retail</div>
                                    <div class="text-base font-extrabold text-white">
                                        {{ formatRupiah(product.selling_price) }}
                                    </div>
                                    <div v-if="metrics.is_owner && showAssetValue" class="text-xs text-emerald-400 font-mono mt-0.5">
                                        Modal: {{ formatRupiah(product.cost_price) }}
                                    </div>
                                </div>
                            </div>

                            <!-- MATRIX UKURAN PILL BADGES LEGA -->
                            <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-800/80">
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2.5">
                                    Rincian Stok per Ukuran:
                                </div>
                                <div class="flex flex-wrap gap-2.5">
                                    <div
                                        v-for="v in product.variants"
                                        :key="v.id"
                                        class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl bg-slate-900 border text-xs"
                                        :class="v.current_stock <= 5 ? 'border-rose-800/80 bg-rose-950/20' : 'border-slate-800'"
                                    >
                                        <span class="font-black text-slate-300 text-xs">{{ v.size }}</span>
                                        <span class="text-slate-600">|</span>
                                        <span
                                            :class="[
                                                'font-mono font-bold text-xs',
                                                v.current_stock <= 5 ? 'text-rose-400 font-black' : 'text-slate-100'
                                            ]"
                                        >
                                            {{ v.current_stock }} <span class="text-[10px] text-slate-400 font-normal">pcs</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="products.data.length === 0" class="p-10 text-center text-slate-400 text-xs">
                            Tidak ada artikel baju yang cocok dengan pencarian.
                        </div>
                    </div>

                    <!-- PAGINASI -->
                    <div v-if="products.links && products.links.length > 3" class="p-4 border-t border-slate-800 flex justify-center gap-1.5">
                        <Link
                            v-for="(link, i) in products.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition',
                                link.active ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-400 hover:bg-slate-800 hover:text-white',
                                !link.url && 'opacity-40 pointer-events-none'
                            ]"
                        />
                    </div>
                </div>

                <!-- KOLOM KANAN: LIVE MUTASI TERKINI (ACTIVITY FEED) -->
                <div class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 overflow-hidden flex flex-col">
                    <div class="p-5 sm:p-6 border-b border-slate-800/80 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-white text-base">Aktivitas Terkini</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Jejak pergerakan barang terbaru.</p>
                        </div>
                        <Link
                            :href="route('warehouse.opnames.index')"
                            class="text-xs font-bold text-sky-400 hover:text-sky-300 transition"
                        >
                            Lihat Stock Opname →
                        </Link>
                    </div>

                    <div class="divide-y divide-slate-800/60 flex-1">
                        <div
                            v-for="m in recentMovements"
                            :key="m.id"
                            class="p-4 sm:p-5 hover:bg-slate-800/30 transition space-y-2"
                        >
                            <div class="flex items-center justify-between text-xs">
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-black tracking-wider',
                                        m.type === 'in' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border border-amber-500/30'
                                    ]"
                                >
                                    {{ m.type === 'in' ? 'MASUK (+)' : 'KELUAR (-)' }}
                                </span>
                                <span class="text-[11px] text-slate-400">
                                    {{ formatTime(m.created_at) }}
                                </span>
                            </div>
                            <div class="text-xs font-semibold text-white">
                                {{ m.variant?.product?.name }}
                                <span class="text-amber-400 font-mono">({{ m.variant?.size }})</span>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-slate-400">
                                <span>
                                    Jumlah: <strong class="text-white">{{ m.quantity }} pcs</strong>
                                </span>
                                <span class="text-[10px] font-mono text-slate-400 truncate max-w-[130px]">
                                    {{ m.reference_number }}
                                </span>
                            </div>
                        </div>

                        <div v-if="recentMovements.length === 0" class="p-8 text-center text-slate-400 text-xs">
                            Belum ada riwayat mutasi.
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
