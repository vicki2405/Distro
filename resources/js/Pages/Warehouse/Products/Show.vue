<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProductStudioViewer from '@/Components/ProductStudioViewer.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    product: Object,
    movements: Array,
    totalStock: Number,
    isOwner: Boolean,
});

// Format Rupiah
const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

// Hitung Margin untuk Owner
const profitMargin = computed(() => {
    if (!props.isOwner || !props.product.cost_price || props.product.cost_price <= 0) return null;
    const profit = props.product.selling_price - props.product.cost_price;
    const percentage = Math.round((profit / props.product.selling_price) * 100);
    return {
        nominal: profit,
        percentage: percentage,
    };
});

// Modal Foto Bukti Mutasi
const selectedProofImage = ref(null);
const openProofModal = (url) => {
    selectedProofImage.value = url;
};
const closeProofModal = () => {
    selectedProofImage.value = null;
};
</script>

<template>
    <Head :title="`${product.name} — Detail Produk`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <!-- Breadcrumbs Minimalis -->
                    <div class="flex items-center gap-2 text-xs font-mono text-slate-400 mb-1">
                        <Link :href="route('warehouse.products.index')" class="hover:text-amber-400 transition">
                            Master Produk
                        </Link>
                        <span>/</span>
                        <span class="text-purple-400 font-bold truncate max-w-xs">{{ product.name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                            {{ product.name }}
                        </h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                            {{ product.category?.name }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('warehouse.products.index')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-900 border border-slate-800 hover:border-slate-700 transition"
                    >
                        ← Kembali ke Katalog
                    </Link>
                    <Link
                        :href="route('warehouse.products.edit', product.id)"
                        class="px-4 py-2 rounded-xl text-xs font-black text-slate-950 bg-purple-500 hover:bg-purple-400 shadow-lg shadow-purple-500/20 transition flex items-center gap-1.5"
                    >
                        <span>✏️ Edit Produk</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">

            <!-- MAIN GRID: STUDIO SHOWCASE (KIRI) & INFORMASI LENGKAP (KANAN) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- 1. STUDIO PRODUCT SHOWCASE DENGAN DEEP ZOOM (KOLOM KIRI - 5 Kolom) -->
                <div class="lg:col-span-5 space-y-4">
                    <ProductStudioViewer
                        :image-url="product.image_url"
                        :product-name="product.name"
                        :category-name="product.category?.name"
                        :total-stock="totalStock"
                        :product-id="product.id"
                    />
                </div>

                <!-- 2. FULL INFORMASI DETAIL PRODUK (KOLOM KANAN - 7 Kolom) -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- KARTU 1: HARGA & NILAI KOMERSIAL -->
                    <div class="bg-slate-900/70 backdrop-blur-md rounded-3xl border border-slate-800/80 p-6 shadow-xl space-y-4">
                        <div class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                            <span>Informasi Finansial & Harga Resmi</span>
                            <span class="text-emerald-400 font-normal text-[11px]">Sistem Terverifikasi</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Harga Jual Retail Resmi -->
                            <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800/90 space-y-1">
                                <div class="text-[11px] font-mono text-slate-400 uppercase tracking-wider">
                                    Harga Jual Retail
                                </div>
                                <div class="text-2xl sm:text-3xl font-black text-amber-400 font-mono tracking-tight">
                                    {{ formatRupiah(product.selling_price) }}
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    Harga patokan kasir & reseller distro
                                </div>
                            </div>

                            <!-- Modal HPP & Estimasi Keuntungan (Khusus Owner) -->
                            <div v-if="isOwner" class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800/90 space-y-1">
                                <div class="flex items-center justify-between">
                                    <div class="text-[11px] font-mono text-emerald-400 uppercase tracking-wider font-bold">
                                        Modal HPP Konveksi
                                    </div>
                                    <span v-if="profitMargin" class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                        Margin {{ profitMargin.percentage }}%
                                    </span>
                                </div>
                                <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-mono tracking-tight">
                                    {{ formatRupiah(product.cost_price) }}
                                </div>
                                <div v-if="profitMargin" class="text-[10px] text-slate-400">
                                    Estimasi Laba Kotor: <span class="font-bold text-slate-200">{{ formatRupiah(profitMargin.nominal) }}</span> / pcs
                                </div>
                            </div>

                            <!-- Total Nilai Aset Fisik Rak (Jika bukan Owner, tampilkan Total Fisik) -->
                            <div v-else class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800/90 space-y-1">
                                <div class="text-[11px] font-mono text-slate-400 uppercase tracking-wider">
                                    Total Ketersediaan Fisik
                                </div>
                                <div class="text-2xl sm:text-3xl font-black text-white font-mono tracking-tight">
                                    {{ totalStock }} <span class="text-xs font-medium text-slate-400">pcs</span>
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    Stok siap kirim dari rak gudang
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KARTU 2: MATRIKS STOK DETAIL PER UKURAN -->
                    <div class="bg-slate-900/70 backdrop-blur-md rounded-3xl border border-slate-800/80 p-6 shadow-xl space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
                                    <span>Rincian Stok Tiap Ukuran</span>
                                    <span class="text-xs font-mono font-normal text-purple-400">({{ product.variants?.length || 0 }} Ukuran Aktif)</span>
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Ketersediaan fisik aktual pada rak penyimpanan distro.
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-[10px] font-mono text-slate-400">Total Akumulasi:</div>
                                <div class="text-sm font-black font-mono text-white">{{ totalStock }} pcs</div>
                            </div>
                        </div>

                        <!-- Grid Kartu Tiap Varian Ukuran -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div
                                v-for="variant in product.variants"
                                :key="variant.id"
                                :class="[
                                    'p-4 rounded-2xl border transition-all duration-200 flex flex-col justify-between space-y-3',
                                    variant.current_stock <= 5
                                        ? 'bg-rose-950/20 border-rose-800/70'
                                        : 'bg-slate-950/80 border-slate-800/90 hover:border-slate-700'
                                ]"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-mono font-black text-white px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800">
                                        Size {{ variant.size }}
                                    </span>
                                    <span
                                        :class="[
                                            'text-[10px] font-mono font-bold px-2 py-0.5 rounded-full',
                                            variant.current_stock === 0
                                                ? 'bg-rose-500/20 text-rose-400 border border-rose-500/30'
                                                : variant.current_stock <= 5
                                                ? 'bg-amber-500/20 text-amber-400 border border-amber-500/30'
                                                : 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                                        ]"
                                    >
                                        {{ variant.current_stock === 0 ? 'Habis' : variant.current_stock <= 5 ? 'Menipis' : 'Ready' }}
                                    </span>
                                </div>

                                <div>
                                    <div class="text-2xl font-black font-mono tracking-tight text-white">
                                        {{ variant.current_stock }}
                                        <span class="text-xs font-normal text-slate-400 font-sans">pcs</span>
                                    </div>
                                    <!-- Bar Visual Level Stok -->
                                    <div class="w-full h-1.5 rounded-full bg-slate-900 overflow-hidden mt-2 border border-slate-800">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="variant.current_stock <= 5 ? 'bg-rose-500' : 'bg-emerald-500'"
                                            :style="{ width: `${Math.min(100, (variant.current_stock / 30) * 100)}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KARTU 3: KARAKTERISTIK BAHAN & SPESIFIKASI -->
                    <div class="bg-slate-900/70 backdrop-blur-md rounded-3xl border border-slate-800/80 p-6 shadow-xl space-y-3">
                        <div class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400">
                            Deskripsi & Spesifikasi Produk
                        </div>
                        <p class="text-sm text-slate-300 leading-relaxed font-sans bg-slate-950/70 p-4 rounded-2xl border border-slate-850">
                            {{ product.description || 'Tidak ada catatan spesifikasi bahan tambahan untuk artikel ini.' }}
                        </p>
                    </div>

                    <!-- KARTU 4: RIWAYAT AUDIT MUTASI KHUSUS PRODUK INI -->
                    <div class="bg-slate-900/70 backdrop-blur-md rounded-3xl border border-slate-800/80 p-6 shadow-xl space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400">
                                    Riwayat Audit Mutasi Terkini
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    Catatan buku besar masuk dan keluar khusus artikel ini.
                                </p>
                            </div>
                            <Link
                                :href="route('warehouse.movements')"
                                class="text-xs font-mono text-purple-400 hover:text-purple-300 underline"
                            >
                                Lihat Semua Mutasi →
                            </Link>
                        </div>

                        <!-- Tabel Mutasi Produk -->
                        <div v-if="movements && movements.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-xs font-mono">
                                <thead class="bg-slate-950 text-slate-400 border-b border-slate-800 uppercase text-[10px]">
                                    <tr>
                                        <th class="p-3">Waktu</th>
                                        <th class="p-3">Tipe</th>
                                        <th class="p-3">Ukuran</th>
                                        <th class="p-3 text-right">Jumlah</th>
                                        <th class="p-3">No. Surat Jalan</th>
                                        <th class="p-3 text-center">Foto Bukti</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60 text-slate-300 text-xs">
                                    <tr v-for="m in movements" :key="m.id" class="hover:bg-slate-800/30 transition">
                                        <td class="p-3 whitespace-nowrap text-slate-400 text-[11px]">
                                            {{ new Date(m.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) }}
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <span
                                                :class="[
                                                    'px-2 py-0.5 rounded text-[10px] font-black',
                                                    m.type === 'in'
                                                        ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                                                        : 'bg-amber-500/15 text-amber-400 border border-amber-500/30'
                                                ]"
                                            >
                                                {{ m.type === 'in' ? 'MASUK' : 'KELUAR' }}
                                            </span>
                                        </td>
                                        <td class="p-3 whitespace-nowrap font-bold text-white">
                                            Size {{ m.variant?.size }}
                                        </td>
                                        <td class="p-3 text-right whitespace-nowrap font-bold" :class="m.type === 'in' ? 'text-emerald-400' : 'text-amber-400'">
                                            {{ m.type === 'in' ? '+' : '-' }}{{ m.quantity }} pcs
                                        </td>
                                        <td class="p-3 whitespace-nowrap text-slate-300">
                                            {{ m.reference_number || '-' }}
                                        </td>
                                        <td class="p-3 text-center whitespace-nowrap">
                                            <button
                                                v-if="m.proof_image_url"
                                                type="button"
                                                @click="openProofModal(m.proof_image_url)"
                                                class="px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 text-[10px] border border-slate-700 cursor-pointer"
                                            >
                                                📷 Lihat
                                            </button>
                                            <span v-else class="text-slate-600 text-[10px]">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="p-6 text-center text-slate-500 text-xs bg-slate-950/50 rounded-2xl border border-slate-850">
                            Belum ada riwayat mutasi masuk atau keluar untuk artikel ini.
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <!-- MODAL FOTO BUKTI SURAT JALAN / MUTASI -->
        <div
            v-if="selectedProofImage"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md"
            @click.self="closeProofModal"
        >
            <div class="relative bg-slate-900 border border-slate-800 rounded-3xl p-4 max-w-xl w-full shadow-2xl space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                    <span class="text-xs font-bold text-white">Foto Bukti Surat Jalan Fisik</span>
                    <button
                        type="button"
                        @click="closeProofModal"
                        class="text-slate-400 hover:text-white font-bold text-sm cursor-pointer"
                    >
                        ✕ Tutup
                    </button>
                </div>
                <div class="rounded-2xl overflow-hidden max-h-[75vh] flex items-center justify-center bg-black">
                    <img :src="selectedProofImage" alt="Foto Bukti" class="max-h-[75vh] w-auto object-contain" />
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
