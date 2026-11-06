<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import StockReportExportModal from '@/Components/StockReportExportModal.vue';

const props = defineProps({
    products: Array,
    movements: Object,
    filters: Object,
    storeSetting: Object,
});

// Modal Cetak & Export Laporan
const isExportModalOpen = ref(false);

// Mode Tampilan: 'table' (Daftar Pengeluaran & Cetak) | 'form' (Form Input Baru)
const viewMode = ref('table');

// State Pencarian Tabel
const search = ref(props.filters?.search || '');
const applySearch = () => {
    router.get(
        route('warehouse.out.create'),
        { search: search.value || undefined },
        { preserveState: true, replace: true }
    );
};

// Modal Bukti Foto
const selectedProof = ref(null);

// Modal Detail Transaksi Khusus
const selectedMovementForDetail = ref(null);

const openDetailModal = (movement) => {
    selectedMovementForDetail.value = movement;
};

const closeDetailModal = () => {
    selectedMovementForDetail.value = null;
};

// Hapus Transaksi Pengeluaran & Revert Stok (Khusus Admin / Owner)
const deleteMovement = (movement) => {
    const ref = movement.reference_number || 'transaksi ini';
    const prodName = movement.variant?.product?.name || 'produk';
    const size = movement.variant?.size || '';
    const qty = movement.quantity;

    if (confirm(`PERINGATAN AKUN ADMIN:\n\nApakah Anda yakin ingin membatalkan & menghapus transaksi pengeluaran '${ref}'?\n\nStok artikel '${prodName}' (Size ${size}) akan otomatis dikembalikan sebanyak +${qty} pcs ke stok gudang.`)) {
        router.delete(route('warehouse.out.destroy', movement.id), {
            preserveScroll: true,
        });
    }
};

// Modal Cetak Surat Jalan
const selectedMovementForPrint = ref(null);

const openPrintModal = (movement) => {
    selectedMovementForPrint.value = movement;
};

const closePrintModal = () => {
    selectedMovementForPrint.value = null;
};

const printDocument = () => {
    window.print();
};

const formatTime = (isoString) => {
    if (!isoString) return '-';
    const d = new Date(isoString);
    return d.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Form Input Barang Keluar
const selectedProductId = ref(props.products?.length > 0 ? props.products[0].id : null);

const currentProduct = computed(() => {
    return props.products?.find((p) => p.id === selectedProductId.value);
});

const form = useForm({
    product_id: selectedProductId.value,
    destination: '',
    reference_number: '',
    recipient_name: '',
    notes: '',
    proof_image: null,
    items: [],
});

const setupItems = () => {
    if (currentProduct.value) {
        form.product_id = currentProduct.value.id;
        form.items = currentProduct.value.variants.map((v) => ({
            variant_id: v.id,
            size: v.size,
            available: v.current_stock,
            quantity: 0,
        }));
    }
};

// Inisialisasi awal
setupItems();

const onProductChange = () => {
    setupItems();
};

const totalQuantity = computed(() => {
    return form.items.reduce((acc, curr) => acc + (parseInt(curr.quantity) || 0), 0);
});

const hasOverStock = computed(() => {
    return form.items.some((item) => (parseInt(item.quantity) || 0) > item.available);
});

const imagePreview = ref(null);
const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.proof_image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('warehouse.out.store'), {
        onSuccess: () => {
            form.reset();
            imagePreview.value = null;
            viewMode.value = 'table';
        },
    });
};
</script>

<template>
    <Head title="Pengeluaran Barang & Surat Jalan — Gudang Distro" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 no-print">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Pengeluaran Barang (Surat Jalan & Distribusi)
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Kelola data pengeluaran baju ke cabang/reseller, cetak surat jalan resmi, dan input pengeluaran baru.
                    </p>
                </div>

                <!-- Tombol Navigasi Toggle Antara Tabel dan Form -->
                <div class="flex items-center gap-2.5">
                    <!-- Tombol Cetak / Export Laporan (Sesuai Hak Akses stock.report atau Owner) -->
                    <button
                        v-if="$page.props.auth?.is_owner || $page.props.auth?.permissions?.includes('stock.report') || $page.props.auth?.user?.role === 'owner'"
                        type="button"
                        @click="isExportModalOpen = true"
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700 font-bold text-xs transition active:scale-95 cursor-pointer shadow-sm"
                    >
                        <span>🖨️</span>
                        <span>Cetak Laporan</span>
                    </button>

                    <button
                        v-if="viewMode === 'table'"
                        type="button"
                        @click="viewMode = 'form'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 font-black text-xs transition shadow-lg shadow-amber-500/25 active:scale-95 cursor-pointer"
                    >
                        <span>➕</span>
                        <span>Input Pengeluaran Baru</span>
                    </button>

                    <button
                        v-else
                        type="button"
                        @click="viewMode = 'table'"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs transition cursor-pointer"
                    >
                        <span>←</span>
                        <span>Kembali ke Daftar Tabel</span>
                    </button>

                    <Link
                        :href="route('warehouse.dashboard')"
                        class="text-xs font-semibold text-slate-400 hover:text-white bg-slate-900 border border-slate-800 hover:border-slate-700 px-3.5 py-2.5 rounded-xl transition"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
        </template>

        <!-- 1. TAMPILAN UTAMA: TABEL INFORMASI PENGELUARAN & SURAT JALAN -->
        <div v-show="viewMode === 'table'" class="space-y-5 no-print">
            
            <!-- BILAH PENCARIAN & STATISTIK RINGKAS -->
            <div class="bg-slate-900/80 backdrop-blur-md p-4 sm:p-5 rounded-2xl border border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg shrink-0">
                        📦
                    </div>
                    <div>
                        <div class="text-xs font-bold text-white uppercase tracking-wider">
                            Daftar Surat Jalan Pengeluaran
                        </div>
                        <div class="text-[11px] text-slate-400">
                            Total tercatat: <span class="font-bold text-amber-400">{{ movements?.total || 0 }} transaksi</span>
                        </div>
                    </div>
                </div>

                <div class="relative w-full sm:w-80">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-xs">
                        🔍
                    </span>
                    <input
                        v-model="search"
                        @keyup.enter="applySearch"
                        type="text"
                        placeholder="Cari No. Resi, tujuan, artikel baju..."
                        class="w-full text-xs rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-500 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 py-2.5 pl-10 pr-9 transition"
                    />
                    <button
                        v-if="search"
                        type="button"
                        @click="search = ''; applySearch()"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-white text-xs cursor-pointer"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <!-- TABEL INFORMASI PENGELUARAN BARANG (LEGA & MINIMALIS 5 KOLOM) -->
            <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/80 uppercase tracking-wider text-[10px] text-slate-400 border-b border-slate-800 font-mono">
                            <tr>
                                <th class="py-3.5 px-5">Surat Jalan</th>
                                <th class="py-3.5 px-5">Artikel Baju</th>
                                <th class="py-3.5 px-5">Tujuan Pengiriman</th>
                                <th class="py-3.5 px-5 text-center">Jumlah</th>
                                <th class="py-3.5 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/70">
                            <tr
                                v-for="m in movements?.data"
                                :key="m.id"
                                class="hover:bg-slate-800/40 transition group"
                            >
                                <!-- 1. No. Surat Jalan & Tanggal Disatukan -->
                                <td class="py-3.5 px-5 whitespace-nowrap">
                                    <div class="font-mono font-bold text-amber-400 text-xs">
                                        {{ m.reference_number || '-' }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                                        {{ formatTime(m.created_at) }}
                                    </div>
                                </td>

                                <!-- 2. Artikel & Ukuran Disatukan -->
                                <td class="py-3.5 px-5">
                                    <div class="font-bold text-white text-xs">
                                        {{ m.variant?.product?.name || 'Artikel Dihapus' }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        Size <strong class="text-slate-200">{{ m.variant?.size }}</strong> • {{ m.variant?.sku }}
                                    </div>
                                </td>

                                <!-- 3. Tujuan & Catatan -->
                                <td class="py-3.5 px-5">
                                    <div class="text-xs font-semibold text-slate-200">
                                        {{ m.destination || '-' }}
                                    </div>
                                    <div v-if="m.notes" class="text-[10px] text-slate-500 truncate max-w-xs mt-0.5">
                                        {{ m.notes }}
                                    </div>
                                </td>

                                <!-- 4. Jumlah Keluar -->
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-lg bg-rose-500/15 border border-rose-500/30 text-rose-300 font-black font-mono text-xs">
                                        -{{ m.quantity }} pcs
                                    </span>
                                </td>

                                <!-- 5. Aksi: Detail & Cetak -->
                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openDetailModal(m)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition active:scale-95 cursor-pointer shadow-sm"
                                        >
                                            <span>👁️</span>
                                            <span>Detail</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openPrintModal(m)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-indigo-500/15 hover:bg-indigo-500/25 border border-indigo-500/35 text-indigo-300 hover:text-white font-bold text-xs transition active:scale-95 cursor-pointer shadow-sm"
                                        >
                                            <span>🖨️</span>
                                            <span>Cetak</span>
                                        </button>
                                        <!-- Tombol Hapus Transaksi (Khusus Akun Admin / Owner) -->
                                        <button
                                            v-if="$page.props.auth?.is_owner || $page.props.auth?.user?.role === 'owner'"
                                            type="button"
                                            @click="deleteMovement(m)"
                                            class="inline-flex items-center gap-1 px-2 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition active:scale-95 cursor-pointer"
                                            title="Hapus Transaksi & Kembalikan Stok Produk (Khusus Admin)"
                                        >
                                            <span>🗑️</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Data Kosong -->
                            <tr v-if="!movements?.data || movements.data.length === 0">
                                <td colspan="5" class="p-12 text-center text-slate-500">
                                    <div class="text-3xl mb-2">📭</div>
                                    <div class="text-sm font-semibold text-slate-400">Belum ada riwayat pengeluaran barang</div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Klik tombol "Input Pengeluaran Baru" di atas untuk mulai mencatat pengeluaran stok.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINASI -->
                <div v-if="movements?.links && movements.links.length > 3" class="p-4 border-t border-slate-800 flex items-center justify-between gap-2 flex-wrap text-xs">
                    <span class="text-slate-500">
                        Menampilkan {{ movements.from || 0 }} - {{ movements.to || 0 }} dari {{ movements.total }} data
                    </span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in movements.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 rounded-lg font-medium transition',
                                link.active ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>

        <!-- 2. TAMPILAN FORM INPUT BARANG KELUAR (DIBUKA KETIKA TOMBOL DIKLIK) -->
        <div v-show="viewMode === 'form'" class="max-w-4xl mx-auto space-y-6 no-print">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-lg font-black text-white">Form Pengeluaran Baju Distro</h3>
                    <p class="text-xs text-slate-400">Pilih artikel pakaian dan masukkan jumlah per ukuran yang akan dikeluarkan.</p>
                </div>
                <button
                    type="button"
                    @click="viewMode = 'table'"
                    class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition cursor-pointer"
                >
                    ✕ Batal
                </button>
            </div>

            <form @submit.prevent="submit" class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-6 sm:p-8 space-y-6 shadow-xl">
                
                <!-- Error Alert -->
                <div v-if="$page.props.errors.error" class="p-4 rounded-xl bg-rose-950/40 border border-rose-800/80 text-rose-300 text-xs">
                    {{ $page.props.errors.error }}
                </div>

                <!-- 1. PILIH ARTIKEL PRODUK -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Pilih Artikel Baju yang Dikeluarkan <span class="text-amber-400">*</span>
                    </label>
                    <select
                        v-model="selectedProductId"
                        @change="onProductChange"
                        class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-3 px-3.5 transition"
                    >
                        <option v-for="p in products" :key="p.id" :value="p.id">
                            {{ p.name }} (Sisa Stok: {{ p.variants.reduce((a, b) => a + b.current_stock, 0) }} pcs)
                        </option>
                    </select>
                </div>

                <!-- 2. TUJUAN & NOMOR REFERENSI -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <!-- Tujuan Pengeluaran Bebas Ketik -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Tujuan Pengeluaran <span class="text-amber-400">*</span>
                        </label>
                        <input
                            v-model="form.destination"
                            type="text"
                            placeholder="Contoh: Toko Cabang 1 / Reseller Bandung / Shopee"
                            required
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                        />
                        <div v-if="form.errors.destination" class="text-rose-400 text-[11px]">
                            {{ form.errors.destination }}
                        </div>
                    </div>

                    <!-- Nomor Surat Jalan / Resi -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            No. Surat Jalan / No. Resi / Order ID <span class="text-amber-400">*</span>
                        </label>
                        <input
                            v-model="form.reference_number"
                            type="text"
                            placeholder="Contoh: DO-2026/04 atau RESI-JNE-123"
                            required
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600 font-mono"
                        />
                    </div>
                </div>

                <!-- Penerima / Kurir -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Nama Penerima / Kurir / Penanggung Jawab
                    </label>
                    <input
                        v-model="form.recipient_name"
                        type="text"
                        placeholder="Contoh: Mas Agus (Toko Cabang 1) / Kurir SiCepat"
                        class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                    />
                </div>

                <!-- 3. MATRIKS INPUT JUMLAH PENGELUARAN DENGAN INFO STOK TERSEDIA -->
                <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-white">
                                Jumlah Baju yang Dikeluarkan
                            </label>
                            <p class="text-[11px] text-slate-500 mt-0.5">Cek fisik stok rak & jumlah sisa sebelum input.</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-500">Total Baju Keluar: </span>
                            <span class="text-xl font-black text-amber-400 font-mono">{{ totalQuantity }} pcs</span>
                        </div>
                    </div>

                    <!-- Grid Ukuran Box -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3.5 pt-1">
                        <div
                            v-for="(item, idx) in form.items"
                            :key="idx"
                            class="bg-slate-900 p-3.5 rounded-xl border border-slate-800 text-center space-y-2 transition"
                            :class="item.quantity > item.available ? 'border-rose-500/80 bg-rose-950/20' : 'focus-within:border-amber-500/60'"
                        >
                            <div class="flex items-center justify-between text-xs px-1">
                                <span class="font-black text-slate-200 text-sm">{{ item.size }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">Sisa: {{ item.available }}</span>
                            </div>
                            <input
                                v-model.number="item.quantity"
                                type="number"
                                min="0"
                                :max="item.available"
                                class="w-full text-center text-base font-bold font-mono rounded-lg border-slate-800 bg-slate-950 text-white focus:ring-amber-500 focus:border-amber-500 py-1.5"
                            />
                            <div v-if="item.quantity > item.available" class="text-[10px] text-rose-400 font-bold">
                                Melebihi stok!
                            </div>
                            <div v-else class="text-[10px] text-slate-500">pcs</div>
                        </div>
                    </div>
                </div>

                <!-- 4. UPLOAD FOTO PAKET / RESI -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Foto Bukti Paket Diserahkan / Resi (Bukti Fisik)
                    </label>
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <label class="flex flex-col items-center justify-center w-full sm:w-72 h-32 border-2 border-dashed border-slate-800 hover:border-amber-500/50 rounded-xl cursor-pointer bg-slate-950 hover:bg-slate-900 transition p-4 text-center">
                            <svg class="w-8 h-8 text-slate-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="text-xs font-medium text-slate-400">Pilih / Jepret Foto Paket</span>
                            <input type="file" accept="image/*" @change="handleImageUpload" class="hidden" />
                        </label>

                        <div v-if="imagePreview" class="relative">
                            <img :src="imagePreview" class="w-32 h-32 object-cover rounded-xl border border-slate-800 shadow-md" />
                            <span class="absolute top-1.5 right-1.5 bg-amber-500 text-slate-950 font-black rounded-full w-5 h-5 flex items-center justify-center text-[11px]">✓</span>
                        </div>
                    </div>
                </div>

                <!-- 5. CATATAN PENGIRIMAN -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">Catatan Pengiriman</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        placeholder="Contoh: Paket dipacking rapi dalam polymailer hitam tebal."
                        class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                    ></textarea>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="viewMode = 'table'"
                        class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing || totalQuantity === 0 || hasOverStock"
                        class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-600 hover:from-amber-300 hover:to-amber-500 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg shadow-amber-500/20 cursor-pointer transition active:scale-95"
                    >
                        {{ form.processing ? 'Memproses...' : 'Keluarkan Barang & Kurangi Stok' }}
                    </button>
                </div>

            </form>
        </div>

        <!-- MODAL DEDICATED DETAIL TRANSAKSI PENGELUARAN -->
        <div
            v-if="selectedMovementForDetail"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-6 overflow-y-auto no-print"
        >
            <div class="relative w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                
                <!-- Header Modal Detail -->
                <div class="p-4 sm:p-5 bg-slate-950 border-b border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg">
                            📄
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 uppercase">
                                    BARANG KELUAR
                                </span>
                                <span class="font-mono font-bold text-amber-400 text-sm">
                                    {{ selectedMovementForDetail.reference_number || '-' }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5 font-mono">
                                Waktu: {{ formatTime(selectedMovementForDetail.created_at) }}
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeDetailModal"
                        class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition cursor-pointer text-xs"
                    >
                        ✕
                    </button>
                </div>

                <!-- Body Modal Detail -->
                <div class="p-5 sm:p-6 overflow-y-auto space-y-5 text-xs">
                    
                    <!-- 1. KARTU ARTIKEL PRODUK & AUDIT STOK -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                            Rincian Artikel & Perubahan Stok
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-bold text-white">
                                    {{ selectedMovementForDetail.variant?.product?.name || 'Artikel Dihapus' }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Kategori: {{ selectedMovementForDetail.variant?.product?.category?.name || 'Tanpa Kategori' }} • Kode: {{ selectedMovementForDetail.variant?.product?.code || '-' }}
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="px-2 py-1 rounded-lg bg-slate-800 border border-slate-700 font-bold font-mono text-white text-xs">
                                        Ukuran {{ selectedMovementForDetail.variant?.size }}
                                    </span>
                                    <span class="text-slate-400 font-mono text-[11px]">
                                        SKU: {{ selectedMovementForDetail.variant?.sku }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="text-[10px] text-slate-400 uppercase">Jumlah Keluar</div>
                                <div class="text-xl font-black font-mono text-rose-400 mt-0.5">
                                    -{{ selectedMovementForDetail.quantity }} pcs
                                </div>
                            </div>
                        </div>

                        <!-- Audit Jejak Stok Sebelum & Sesudah -->
                        <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-800/80 text-[11px] font-mono">
                            <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                                <span class="text-slate-500 block text-[10px] uppercase">Stok di Rak Sebelum:</span>
                                <span class="font-bold text-slate-300 text-xs">{{ selectedMovementForDetail.stock_before }} pcs</span>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                                <span class="text-slate-500 block text-[10px] uppercase">Sisa Stok di Rak Sesudah:</span>
                                <span class="font-bold text-emerald-400 text-xs">{{ selectedMovementForDetail.stock_after }} pcs</span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. KARTU TUJUAN & CATATAN -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2.5">
                        <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                            Informasi Logistik & Pengiriman
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Tujuan Pengeluaran & Penerima:</span>
                            <span class="text-xs font-bold text-white mt-0.5 block">
                                {{ selectedMovementForDetail.destination || '-' }}
                            </span>
                        </div>
                        <div v-if="selectedMovementForDetail.notes" class="pt-2 border-t border-slate-800/80">
                            <span class="text-slate-400 block text-[11px]">Catatan Petugas:</span>
                            <span class="text-xs text-slate-300 mt-0.5 block">
                                {{ selectedMovementForDetail.notes }}
                            </span>
                        </div>
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                            <span>Operator Pencatat:</span>
                            <span class="text-white font-semibold">{{ selectedMovementForDetail.user?.name || '-' }}</span>
                        </div>
                    </div>

                    <!-- 3. FOTO BUKTI FISIK (JIKA ADA) -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                        <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                            Foto Bukti Fisik / Resi Ekspedisi
                        </div>
                        <div v-if="selectedMovementForDetail.proof_image" class="space-y-2">
                            <div class="relative rounded-xl overflow-hidden border border-slate-800 max-h-56 bg-black flex items-center justify-center">
                                <img
                                    :src="'/storage/' + selectedMovementForDetail.proof_image"
                                    class="w-full h-auto max-h-56 object-contain"
                                    alt="Foto Bukti"
                                />
                            </div>
                            <button
                                type="button"
                                @click="selectedProof = selectedMovementForDetail.proof_image"
                                class="text-[11px] text-amber-400 hover:text-amber-300 font-semibold cursor-pointer"
                            >
                                🔍 Lihat Foto Ukuran Penuh
                            </button>
                        </div>
                        <div v-else class="text-slate-500 italic text-xs py-2">
                            Tidak ada foto bukti fisik dilampirkan pada transaksi ini.
                        </div>
                    </div>

                </div>

                <!-- Footer Modal Detail -->
                <div class="p-4 bg-slate-950 border-t border-slate-800 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="openPrintModal(selectedMovementForDetail); closeDetailModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-500/15 hover:bg-indigo-500/25 border border-indigo-500/35 text-indigo-300 hover:text-white font-bold text-xs transition cursor-pointer active:scale-95 shadow-sm"
                    >
                        <span>🖨️</span>
                        <span>Cetak Surat Jalan</span>
                    </button>
                    <button
                        type="button"
                        @click="closeDetailModal"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>

            </div>
        </div>

        <!-- 3. MODAL CETAK SURAT JALAN RESMI (PRINTABLE VIEW) -->
        <div
            v-if="selectedMovementForPrint"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-6 overflow-y-auto"
        >
            <div class="relative w-full max-w-3xl bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[95vh]">
                
                <!-- Top Action Header (Sembunyi saat print kertas) -->
                <div class="p-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between gap-3 no-print">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🖨️</span>
                        <span class="font-bold text-sm text-white">Pratinjau Cetak Surat Jalan</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="printDocument"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition cursor-pointer active:scale-95 shadow-md shadow-amber-500/20"
                        >
                            <span>🖨️ Cetak / Print PDF</span>
                        </button>
                        <button
                            type="button"
                            @click="closePrintModal"
                            class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition cursor-pointer"
                        >
                            ✕ Tutup
                        </button>
                    </div>
                </div>

                <!-- AREA CETAK FORMAL SURAT JALAN (PRINTABLE DOCUMENT AREA) -->
                <div class="p-6 sm:p-10 overflow-y-auto bg-slate-950 flex-1">
                    <div id="printable-surat-jalan" class="bg-white text-slate-900 p-8 sm:p-10 rounded-xl shadow-lg font-sans">
                        
                        <!-- KOP SURAT JALAN RESMI -->
                        <div class="flex items-center justify-between border-b-2 border-slate-900 pb-5 mb-6">
                            <div class="flex items-center gap-3.5">
                                <img
                                    v-if="storeSetting?.logo_url"
                                    :src="storeSetting.logo_url"
                                    class="w-12 h-12 object-contain"
                                    alt="Logo Distro"
                                />
                                <div v-else class="w-12 h-12 rounded-lg bg-slate-900 text-white font-black flex items-center justify-center text-xl">
                                    {{ (storeSetting?.store_name || 'D').charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h1 class="text-xl font-black tracking-tight text-slate-950 leading-none">
                                        {{ storeSetting?.store_name || 'DISTRO OPERATIONS' }}
                                    </h1>
                                    <p class="text-xs text-slate-600 font-mono tracking-wider mt-1 uppercase">
                                        {{ storeSetting?.store_tagline || 'Official Apparel & Logistics Feed' }}
                                    </p>
                                    <p v-if="storeSetting?.whatsapp_number" class="text-[11px] text-slate-500 mt-0.5">
                                        WhatsApp CS: {{ storeSetting.whatsapp_number }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="inline-block px-3 py-1 rounded bg-slate-900 text-white font-mono font-bold text-xs uppercase tracking-wider">
                                    SURAT JALAN RESMI
                                </div>
                                <div class="text-xs font-mono font-bold text-slate-700 mt-1.5">
                                    NO: {{ selectedMovementForPrint.reference_number || '-' }}
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    Tgl: {{ formatTime(selectedMovementForPrint.created_at) }}
                                </div>
                            </div>
                        </div>

                        <!-- DATA INFORMASI PENGIRIMAN -->
                        <div class="grid grid-cols-2 gap-6 p-4 rounded-lg bg-slate-50 border border-slate-200 mb-6 text-xs">
                            <div>
                                <span class="font-bold text-slate-500 uppercase tracking-wider block text-[10px]">Tujuan Pengeluaran / Ekspedisi:</span>
                                <span class="text-sm font-bold text-slate-900 mt-0.5 block">
                                    {{ selectedMovementForPrint.destination || '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="font-bold text-slate-500 uppercase tracking-wider block text-[10px]">Operator Gudang Pengirim:</span>
                                <span class="text-sm font-bold text-slate-900 mt-0.5 block">
                                    {{ selectedMovementForPrint.user?.name || '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- TABEL RINCIAN ITEM BARANG KELUAR -->
                        <div class="mb-6">
                            <table class="w-full border-collapse border border-slate-300 text-xs">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700 font-mono font-bold uppercase text-[10px]">
                                        <th class="border border-slate-300 p-2.5 text-center w-12">No</th>
                                        <th class="border border-slate-300 p-2.5 text-left">Kode SKU</th>
                                        <th class="border border-slate-300 p-2.5 text-left">Nama Artikel Baju Distro</th>
                                        <th class="border border-slate-300 p-2.5 text-center w-20">Ukuran</th>
                                        <th class="border border-slate-300 p-2.5 text-right w-24">Jumlah Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border border-slate-300 p-2.5 text-center font-mono">1</td>
                                        <td class="border border-slate-300 p-2.5 font-mono font-bold text-slate-800">
                                            {{ selectedMovementForPrint.variant?.sku || '-' }}
                                        </td>
                                        <td class="border border-slate-300 p-2.5 font-semibold text-slate-900">
                                            {{ selectedMovementForPrint.variant?.product?.name || 'Artikel Pakaian' }}
                                        </td>
                                        <td class="border border-slate-300 p-2.5 text-center font-mono font-bold text-slate-800">
                                            {{ selectedMovementForPrint.variant?.size || '-' }}
                                        </td>
                                        <td class="border border-slate-300 p-2.5 text-right font-mono font-black text-slate-900 text-sm">
                                            {{ selectedMovementForPrint.quantity }} pcs
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-100 font-bold">
                                        <td colspan="4" class="border border-slate-300 p-2.5 text-right font-mono uppercase text-xs">
                                            Total Keseluruhan Barang Diserahkan:
                                        </td>
                                        <td class="border border-slate-300 p-2.5 text-right font-mono font-black text-slate-900 text-base">
                                            {{ selectedMovementForPrint.quantity }} pcs
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- CATATAN DOKUMEN -->
                        <div v-if="selectedMovementForPrint.notes" class="p-3 rounded-lg border border-slate-200 bg-slate-50 text-xs mb-8">
                            <span class="font-bold text-slate-600 block text-[10px] uppercase">Catatan:</span>
                            <span class="text-slate-800">{{ selectedMovementForPrint.notes }}</span>
                        </div>

                        <!-- KOLOM TANDA TANGAN SERAH TERIMA 3 PIHAK -->
                        <div class="grid grid-cols-3 gap-6 pt-6 text-center text-xs text-slate-700">
                            <div>
                                <p class="font-bold uppercase tracking-wider text-[10px] text-slate-500">Yang Menyerahkan,</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">(Petugas Gudang)</p>
                                <div class="h-16"></div>
                                <p class="font-bold text-slate-900 border-t border-slate-400 pt-1 mx-4">
                                    {{ selectedMovementForPrint.user?.name || '(..........................)' }}
                                </p>
                            </div>

                            <div>
                                <p class="font-bold uppercase tracking-wider text-[10px] text-slate-500">Pengantar / Driver,</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">(Kurir Ekspedisi)</p>
                                <div class="h-16"></div>
                                <p class="font-bold text-slate-900 border-t border-slate-400 pt-1 mx-4">
                                    (..........................)
                                </p>
                            </div>

                            <div>
                                <p class="font-bold uppercase tracking-wider text-[10px] text-slate-500">Yang Menerima,</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">(Toko Cabang / Konsumen)</p>
                                <div class="h-16"></div>
                                <p class="font-bold text-slate-900 border-t border-slate-400 pt-1 mx-4">
                                    (..........................)
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- 4. MODAL PREVIEW FOTO BUKTI -->
        <div
            v-if="selectedProof"
            @click="selectedProof = null"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 no-print"
        >
            <div class="relative max-w-lg w-full bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-2xl" @click.stop>
                <img :src="'/storage/' + selectedProof" class="w-full h-auto max-h-[75vh] object-contain rounded-xl" alt="Bukti Foto Paket" />
                <button
                    type="button"
                    @click="selectedProof = null"
                    class="mt-3 w-full py-2.5 bg-slate-800 hover:bg-slate-700 text-xs font-bold rounded-xl text-white transition cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>

        <!-- MODAL EXPORT LAPORAN BARANG KELUAR -->
        <StockReportExportModal
            :show="isExportModalOpen"
            type="out"
            :export-url="route('warehouse.out.export')"
            @close="isExportModalOpen = false"
        />

    </AuthenticatedLayout>
</template>

<style>
/* CSS KHUSUS CETAK DOKUMEN SURAT JALAN KE KERTAS ATAU PDF */
@media print {
    /* Sembunyikan seluruh elemen antarmuka website */
    body * {
        visibility: hidden !important;
    }
    
    /* Hanya tampilkan kertas dokumen Surat Jalan */
    #printable-surat-jalan,
    #printable-surat-jalan * {
        visibility: visible !important;
    }

    #printable-surat-jalan {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: auto !important;
        background: white !important;
        color: black !important;
        padding: 24px !important;
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        z-index: 999999 !important;
    }

    .no-print {
        display: none !important;
    }
}
</style>
