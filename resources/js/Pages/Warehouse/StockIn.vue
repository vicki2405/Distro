<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { convertAndCompressImage } from '@/Utils/imageConverter';
import StockReportExportModal from '@/Components/StockReportExportModal.vue';

const props = defineProps({
    products: Array,
    categories: Array,
    movements: Object,
    filters: Object,
});

// Modal Cetak & Export Laporan
const isExportModalOpen = ref(false);

// Mode Tampilan: 'table' (Daftar Riwayat Penerimaan) | 'form' (Form Input Penerimaan Baru)
const viewMode = ref('table');

// State Pencarian Tabel Riwayat
const search = ref(props.filters?.search || '');
const applySearch = () => {
    router.get(
        route('warehouse.in.create'),
        { search: search.value || undefined },
        { preserveState: true, replace: true }
    );
};

// Modal Bukti Foto Fisik
const selectedProof = ref(null);

// Modal Detail Transaksi Penerimaan
const selectedMovementForDetail = ref(null);

const openDetailModal = (movement) => {
    selectedMovementForDetail.value = movement;
};

const closeDetailModal = () => {
    selectedMovementForDetail.value = null;
};

// Hapus Transaksi Penerimaan & Revert Stok (Khusus Admin / Owner)
const deleteMovement = (movement) => {
    const ref = movement.reference_number || 'transaksi ini';
    const prodName = movement.variant?.product?.name || 'produk';
    const size = movement.variant?.size || '';
    const qty = movement.quantity;

    if (confirm(`PERINGATAN AKUN ADMIN:\n\nApakah Anda yakin ingin membatalkan & menghapus transaksi penerimaan '${ref}'?\n\nStok artikel '${prodName}' (Size ${size}) akan otomatis dikurangi kembali sebanyak ${qty} pcs dari stok gudang.`)) {
        router.delete(route('warehouse.in.destroy', movement.id), {
            preserveScroll: true,
        });
    }
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

// ==========================================
// FORM STATE & LOGIC INPUT PENERIMAAN BARANG
// ==========================================
const defaultSizes = ['S', 'M', 'L', 'XL', 'XXL'];

const selectedProductId = ref(props.products && props.products.length > 0 ? props.products[0].id : null);

const selectedProduct = computed(() => {
    return props.products?.find((p) => p.id === selectedProductId.value) || null;
});

const form = useForm({
    product_id: selectedProductId.value,
    reference_number: '',
    supplier_name: '',
    notes: '',
    proof_image: null,
    items: [],
});

const setupItems = () => {
    form.product_id = selectedProductId.value;
    const prod = selectedProduct.value;
    if (prod && prod.variants && prod.variants.length > 0) {
        form.items = prod.variants.map((v) => ({
            variant_id: v.id,
            size: v.size,
            current_stock: v.current_stock || 0,
            quantity: 0,
        }));
    } else {
        form.items = defaultSizes.map((s) => ({
            variant_id: null,
            size: s,
            current_stock: 0,
            quantity: 0,
        }));
    }
};

// Inisialisasi awal formulir
setupItems();

const onProductChange = () => {
    setupItems();
};

const incrementQty = (item) => {
    item.quantity = (parseInt(item.quantity) || 0) + 1;
};

const decrementQty = (item) => {
    const val = parseInt(item.quantity) || 0;
    if (val > 0) {
        item.quantity = val - 1;
    }
};

const totalQuantity = computed(() => {
    return form.items.reduce((acc, curr) => acc + (parseInt(curr.quantity) || 0), 0);
});

// Metrik Ringkasan Transaksi Tabel
const totalPcsInCurrentPage = computed(() => {
    if (!props.movements?.data) return 0;
    return props.movements.data.reduce((acc, m) => acc + (parseInt(m.quantity) || 0), 0);
});

// Upload & Kompresi Foto Bukti Nota
const imagePreview = ref(null);
const isCompressing = ref(false);
const compressionStats = ref(null);

const handleImageUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        alert('File yang dipilih harus berupa file gambar (JPG, PNG, atau WEBP).');
        return;
    }

    try {
        isCompressing.value = true;
        const result = await convertAndCompressImage(file, 1600, 1600, 0.85);
        form.proof_image = result.file;
        imagePreview.value = result.previewUrl;
        compressionStats.value = {
            original: result.formattedOriginalSize,
            converted: result.formattedConvertedSize,
            saved: result.savedPercentage,
        };
    } catch (err) {
        console.warn('Gagal mengompresi gambar, menggunakan file asli:', err);
        form.proof_image = file;
        imagePreview.value = URL.createObjectURL(file);
        compressionStats.value = null;
    } finally {
        isCompressing.value = false;
    }
};

const removeProofImage = () => {
    form.proof_image = null;
    imagePreview.value = null;
    compressionStats.value = null;
};

const submit = () => {
    form.post(route('warehouse.in.store'), {
        onSuccess: () => {
            form.reset('reference_number', 'supplier_name', 'notes', 'proof_image');
            form.items.forEach((item) => (item.quantity = 0));
            imagePreview.value = null;
            compressionStats.value = null;
            viewMode.value = 'table';
        },
    });
};
</script>

<template>
    <Head title="Penerimaan Barang Masuk (Konveksi) — Gudang Distro" />

    <AuthenticatedLayout>
        <!-- HEADER UTAMA -->
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span>Penerimaan Barang Masuk</span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 uppercase">
                            Restock Konveksi
                        </span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Kelola data surat jalan konveksi, pantau riwayat barang masuk, dan catat penambahan stok ke rak gudang.
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
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs transition active:scale-95 cursor-pointer shadow-lg shadow-emerald-500/20"
                    >
                        <span>+</span>
                        <span>Catat Penerimaan Baru</span>
                    </button>

                    <button
                        v-else
                        type="button"
                        @click="viewMode = 'table'"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 text-slate-300 hover:text-white font-semibold text-xs transition active:scale-95 cursor-pointer"
                    >
                        <span>←</span>
                        <span>Kembali ke Riwayat Penerimaan</span>
                    </button>

                    <Link
                        :href="route('warehouse.dashboard')"
                        class="text-xs font-semibold text-slate-400 hover:text-white bg-slate-900 border border-slate-800 hover:border-slate-700 px-4 py-2.5 rounded-xl transition"
                    >
                        Dashboard
                    </Link>
                </div>
            </div>
        </template>

        <!-- ============================================================== -->
        <!-- VIEW MODE 1: TABEL DAFTAR RIWAYAT PENERIMAAN KONVEKSI           -->
        <!-- ============================================================== -->
        <div v-if="viewMode === 'table'" class="space-y-6">

            <!-- KARTU METRIK RINGKAS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Surat Masuk</div>
                        <div class="text-2xl font-black text-white mt-1 font-mono">
                            {{ movements?.total || 0 }} <span class="text-xs font-normal text-slate-500">transaksi</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-xl">
                        📥
                    </div>
                </div>

                <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Fisik Masuk (Hal Ini)</div>
                        <div class="text-2xl font-black text-emerald-400 mt-1 font-mono">
                            {{ totalPcsInCurrentPage }} <span class="text-xs font-normal text-slate-500">pcs</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 text-xl">
                        👕
                    </div>
                </div>

                <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Status Operasional</div>
                        <div class="text-sm font-bold text-slate-200 mt-1 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Sinkronisasi Realtime
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 text-xl">
                        ⚡
                    </div>
                </div>
            </div>

            <!-- SEARCH BAR -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-slate-900/60 p-4 rounded-2xl border border-slate-800/80">
                <div class="relative flex-1">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 text-sm">
                        🔍
                    </span>
                    <input
                        v-model="search"
                        @keyup.enter="applySearch"
                        type="text"
                        placeholder="Cari No. Surat Jalan, Konveksi, Artikel Baju, atau SKU..."
                        class="w-full pl-10 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 transition"
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

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="applySearch"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold border border-slate-700 transition cursor-pointer"
                    >
                        Cari
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'form'"
                        class="px-4 py-2.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-400 font-bold text-xs transition cursor-pointer whitespace-nowrap"
                    >
                        + Input Nota Baru
                    </button>
                </div>
            </div>

            <!-- TABEL INFORMASI PENERIMAAN BARANG -->
            <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-950/80 uppercase tracking-wider text-[10px] text-slate-400 border-b border-slate-800 font-mono">
                            <tr>
                                <th class="py-3.5 px-5">Surat Jalan / Nota</th>
                                <th class="py-3.5 px-5">Artikel Baju</th>
                                <th class="py-3.5 px-5">Asal Konveksi / Supplier</th>
                                <th class="py-3.5 px-5 text-center">Jumlah Masuk</th>
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
                                    <div class="font-mono font-bold text-emerald-400 text-xs">
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

                                <!-- 3. Asal Konveksi & Catatan -->
                                <td class="py-3.5 px-5">
                                    <div class="text-xs font-semibold text-slate-200">
                                        {{ m.destination || 'Konveksi' }}
                                    </div>
                                    <div v-if="m.notes" class="text-[10px] text-slate-500 truncate max-w-xs mt-0.5">
                                        {{ m.notes }}
                                    </div>
                                </td>

                                <!-- 4. Jumlah Masuk -->
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 font-black font-mono text-xs">
                                        +{{ m.quantity }} pcs
                                    </span>
                                </td>

                                <!-- 5. Aksi: Detail & Foto Nota -->
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
                                            v-if="m.proof_image"
                                            type="button"
                                            @click="selectedProof = '/storage/' + m.proof_image"
                                            class="inline-flex items-center gap-1 px-2 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-semibold transition cursor-pointer"
                                            title="Lihat Foto Bukti Fisik"
                                        >
                                            <span>📷</span>
                                        </button>
                                        <!-- Tombol Hapus Transaksi (Khusus Akun Admin / Owner) -->
                                        <button
                                            v-if="$page.props.auth?.is_owner || $page.props.auth?.user?.role === 'owner'"
                                            type="button"
                                            @click="deleteMovement(m)"
                                            class="inline-flex items-center gap-1 px-2 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition active:scale-95 cursor-pointer"
                                            title="Hapus Transaksi & Kurangi Stok Produk (Khusus Admin)"
                                        >
                                            <span>🗑️</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State Tabel -->
                            <tr v-if="!movements?.data || movements.data.length === 0">
                                <td colspan="5" class="py-12 text-center text-slate-500">
                                    <div class="text-3xl mb-2">📥</div>
                                    <p class="text-sm font-semibold text-slate-400">Belum ada riwayat penerimaan barang masuk.</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Klik tombol di bawah untuk mencatat barang tiba dari konveksi.</p>
                                    <button
                                        type="button"
                                        @click="viewMode = 'form'"
                                        class="mt-4 px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs hover:bg-emerald-400 transition cursor-pointer"
                                    >
                                        + Catat Penerimaan Pertama
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Links -->
                <div v-if="movements?.links && movements.links.length > 3" class="p-4 bg-slate-950/60 border-t border-slate-800 flex items-center justify-between gap-3 text-xs">
                    <span class="text-slate-500">
                        Menampilkan {{ movements.from || 0 }} - {{ movements.to || 0 }} dari {{ movements.total || 0 }} mutasi masuk
                    </span>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in movements.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-lg border text-xs font-semibold transition',
                                link.active
                                    ? 'bg-emerald-500 text-slate-950 border-emerald-500'
                                    : link.url
                                    ? 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white hover:border-slate-700'
                                    : 'opacity-40 cursor-not-allowed border-transparent text-slate-600',
                            ]"
                        />
                    </div>
                </div>
            </div>

        </div>

        <!-- ============================================================== -->
        <!-- VIEW MODE 2: FORM INPUT PENERIMAAN BARANG BARU (FULL WIDTH)    -->
        <!-- ============================================================== -->
        <div v-else class="space-y-6">

            <!-- BANNER NAVIGASI TAMBAH ARTIKEL MASTER DATA -->
            <div class="p-4 rounded-2xl bg-purple-950/40 border border-purple-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5 text-purple-300">
                    <span class="text-base">👕</span>
                    <span>Barang yang baru tiba merupakan <strong>model/artikel baru</strong> yang belum pernah ada di katalog?</span>
                </div>
                <Link
                    :href="route('warehouse.products.create')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-purple-500 hover:bg-purple-400 text-slate-950 font-bold text-xs whitespace-nowrap transition cursor-pointer"
                >
                    + Daftarkan di Master Produk →
                </Link>
            </div>

            <!-- Error Flash Alert -->
            <div v-if="$page.props.errors.error" class="p-4 rounded-xl bg-rose-950/40 border border-rose-800/80 text-rose-300 text-xs">
                {{ $page.props.errors.error }}
            </div>

            <!-- FORM GRID 2-KOLOM RESPONSIVE FULL WIDTH -->
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- ============================================== -->
                <!-- KOLOM KIRI (7/12): ARTIKEL & MATRIKS STOK FISIK -->
                <!-- ============================================== -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- CARD 1: PILIH ARTIKEL PRODUK -->
                    <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-6 space-y-5 shadow-xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    <span>📦</span>
                                    <span>Identifikasi Artikel Baju</span>
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">Pilih model baju yang baru selesai diproduksi oleh pihak konveksi.</p>
                            </div>
                            <Link :href="route('warehouse.products.index')" class="text-[11px] text-emerald-400 hover:text-emerald-300 font-semibold">
                                Kelola Master Produk →
                            </Link>
                        </div>

                        <!-- Dropdown Pilihan Produk -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Pilih Artikel Baju yang Masuk <span class="text-emerald-400">*</span>
                            </label>
                            <select
                                v-model="selectedProductId"
                                @change="onProductChange"
                                required
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-950 text-slate-100 py-3 px-3.5 transition"
                            >
                                <option v-for="p in products" :key="p.id" :value="p.id">
                                    {{ p.name }} ({{ p.category?.name || 'Tanpa Kategori' }}) — Kode: {{ p.code }}
                                </option>
                            </select>
                            <div v-if="products.length === 0" class="text-xs text-rose-400 mt-1">
                                Belum ada produk di Master Data. Silakan daftarkan produk terlebih dahulu.
                            </div>
                        </div>

                        <!-- Pratinjau Info Produk Terpilih -->
                        <div v-if="selectedProduct" class="p-4 rounded-xl bg-slate-950 border border-slate-800/80 flex items-center gap-4">
                            <div class="w-14 h-14 rounded-lg bg-slate-900 border border-slate-800 overflow-hidden flex items-center justify-center shrink-0">
                                <img
                                    v-if="selectedProduct.image_url"
                                    :src="selectedProduct.image_url"
                                    :alt="selectedProduct.name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-xl">👕</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-white truncate">{{ selectedProduct.name }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Kategori: <span class="text-slate-300 font-medium">{{ selectedProduct.category?.name || '-' }}</span> • SKU: <span class="font-mono text-emerald-400">{{ selectedProduct.code }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 mt-1">
                                    Total Varian: {{ selectedProduct.variants?.length || 0 }} ukuran terdaftar
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: MATRIKS UKURAN & STOK GUDANG -->
                    <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-6 space-y-5 shadow-xl">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                    <span>📏</span>
                                    <span>Jumlah Baju Masuk per Ukuran</span>
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Pantau stok rak saat ini dan tentukan jumlah pcs fisik yang datang.
                                </p>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-xs text-slate-400">Total Kedatangan: </span>
                                <span class="text-lg font-black text-emerald-400 font-mono">{{ totalQuantity }} pcs</span>
                            </div>
                        </div>

                        <!-- Matriks Input Ukuran Table/Card -->
                        <div class="space-y-3">
                            <div
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="p-3.5 rounded-xl bg-slate-950/80 border border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-slate-700 transition"
                            >
                                <!-- Ukuran & Stok Saat Ini -->
                                <div class="flex items-center gap-3">
                                    <span class="w-11 h-11 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center font-black text-sm text-white font-mono shrink-0">
                                        {{ item.size }}
                                    </span>
                                    <div>
                                        <div class="text-xs font-semibold text-slate-200">Ukuran {{ item.size }}</div>
                                        <div class="text-[11px] text-slate-500 font-mono mt-0.5">
                                            Stok Gudang Saat Ini: <strong class="text-slate-300">{{ item.current_stock }} pcs</strong>
                                        </div>
                                    </div>
                                </div>

                                <!-- Stepper Input & Estimasi Akhir -->
                                <div class="flex items-center justify-between sm:justify-end gap-4">
                                    <!-- Stepper Counter -->
                                    <div class="flex items-center border border-slate-800 rounded-xl bg-slate-900 overflow-hidden">
                                        <button
                                            type="button"
                                            @click="decrementQty(item)"
                                            class="px-3 py-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition active:scale-95 font-bold cursor-pointer"
                                        >
                                            -
                                        </button>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="0"
                                            class="w-16 text-center text-sm font-bold font-mono border-0 bg-transparent text-white focus:ring-0 py-1.5"
                                        />
                                        <button
                                            type="button"
                                            @click="incrementQty(item)"
                                            class="px-3 py-1.5 text-slate-400 hover:text-white hover:bg-slate-800 transition active:scale-95 font-bold cursor-pointer"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <!-- Estimasi Stok Setelah Masuk -->
                                    <div class="text-right shrink-0 min-w-[90px]">
                                        <div class="text-[10px] text-slate-500 uppercase">Menjadi</div>
                                        <div class="text-xs font-bold font-mono text-emerald-400">
                                            {{ item.current_stock + (parseInt(item.quantity) || 0) }} pcs
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ============================================== -->
                <!-- KOLOM KANAN (5/12): NOTA, FOTO & AKSI SIMPAN   -->
                <!-- ============================================== -->
                <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-4">

                    <!-- CARD 3: DOKUMEN & NOTA KONVEKSI -->
                    <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-6 space-y-5 shadow-xl">
                        <div class="pb-3 border-b border-slate-800">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>📄</span>
                                <span>Dokumen Surat Jalan / Nota</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Informasi legalitas dan asal pengiriman konveksi.</p>
                        </div>

                        <!-- No. Surat Jalan / Nota -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                No. Surat Jalan / Nota Konveksi <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.reference_number"
                                type="text"
                                placeholder="Contoh: SJ-2026/11/04 atau NOTA-981"
                                required
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600 font-mono"
                            />
                            <div v-if="form.errors.reference_number" class="text-xs text-rose-400">{{ form.errors.reference_number }}</div>
                        </div>

                        <!-- Nama Konveksi / Supplier -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Nama Konveksi / Vendor
                            </label>
                            <input
                                v-model="form.supplier_name"
                                type="text"
                                placeholder="Contoh: Konveksi Berkah Bandung"
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                            />
                        </div>

                        <!-- Upload Foto Fisik Nota -->
                        <div class="space-y-2 pt-2 border-t border-slate-800/80">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Foto Fisik Nota / Surat Jalan (Opsional)
                            </label>

                            <!-- Dropzone & Upload Button -->
                            <div v-if="!imagePreview" class="relative">
                                <label
                                    class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-slate-800 hover:border-emerald-500/50 rounded-xl cursor-pointer bg-slate-950 hover:bg-slate-900/50 transition p-4 text-center group"
                                >
                                    <span v-if="isCompressing" class="text-xs text-emerald-400 font-semibold animate-pulse">
                                        Mengompresi & mengoptimalkan gambar...
                                    </span>
                                    <template v-else>
                                        <svg class="w-8 h-8 text-slate-500 group-hover:text-emerald-400 transition mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="text-xs font-medium text-slate-400 group-hover:text-white transition">Pilih / Jepret Foto Nota Fisik</span>
                                        <span class="text-[10px] text-slate-600 mt-0.5">Otomatis dikonversi ke WebP ringan</span>
                                    </template>
                                    <input type="file" accept="image/*" @change="handleImageUpload" class="hidden" :disabled="isCompressing" />
                                </label>
                            </div>

                            <!-- Preview Foto Nota & Metrik Kompresi -->
                            <div v-else class="p-3 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <img :src="imagePreview" class="w-14 h-14 object-cover rounded-lg border border-slate-800" />
                                    <div>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                            FOTO SIAP
                                        </span>
                                        <div v-if="compressionStats" class="text-[10px] text-slate-400 font-mono mt-1">
                                            {{ compressionStats.converted }} <span class="text-emerald-400 font-semibold">(Hemat {{ compressionStats.saved }}%)</span>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="removeProofImage"
                                    class="p-2 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 transition cursor-pointer text-xs"
                                    title="Hapus foto"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div class="space-y-1.5 pt-2 border-t border-slate-800/80">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">Catatan Tambahan</label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Contoh: Diterima dalam kardus utuh, jahitan rapi, barcode sudah dicek."
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                            ></textarea>
                        </div>
                    </div>

                    <!-- CARD 4: PANEL RINGKASAN & TOMBOL SIMPAN -->
                    <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-6 space-y-4 shadow-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Masuk ke Rak</span>
                            <span class="text-2xl font-black text-emerald-400 font-mono">{{ totalQuantity }} pcs</span>
                        </div>

                        <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                @click="viewMode = 'table'"
                                class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || totalQuantity === 0"
                                class="flex-1 px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg shadow-emerald-500/20 cursor-pointer transition text-center"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Simpan & Tambah ke Stok' }}
                            </button>
                        </div>
                    </div>

                </div>

            </form>

        </div>

        <!-- ============================================================== -->
        <!-- MODAL 1: LIHAT DETAIL TRANSAKSI PENERIMAAN (VIEW MODAL KHUSUS)  -->
        <!-- ============================================================== -->
        <div
            v-if="selectedMovementForDetail"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto"
        >
            <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Header Modal Detail -->
                <div class="p-4 sm:p-5 bg-slate-950 border-b border-slate-800 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg">
                            📥
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase">
                                    PENERIMAAN RESTOCK
                                </span>
                                <span class="font-mono font-bold text-white text-sm">
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
                    
                    <!-- 1. Rincian Artikel & Perubahan Stok -->
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
                                <div class="text-[10px] text-slate-400 uppercase">Jumlah Masuk</div>
                                <div class="text-xl font-black font-mono text-emerald-400 mt-0.5">
                                    +{{ selectedMovementForDetail.quantity }} pcs
                                </div>
                            </div>
                        </div>

                        <!-- Perhitungan Audit Fisik -->
                        <div class="pt-2 border-t border-slate-800/80 grid grid-cols-3 gap-2 text-center text-[11px] font-mono">
                            <div class="bg-slate-900 p-2 rounded-lg">
                                <div class="text-slate-500 text-[10px]">Stok Awal</div>
                                <div class="font-bold text-slate-300 mt-0.5">{{ selectedMovementForDetail.stock_before }} pcs</div>
                            </div>
                            <div class="bg-slate-900 p-2 rounded-lg border border-emerald-500/20">
                                <div class="text-emerald-400 text-[10px]">Diterima</div>
                                <div class="font-bold text-emerald-400 mt-0.5">+{{ selectedMovementForDetail.quantity }} pcs</div>
                            </div>
                            <div class="bg-slate-900 p-2 rounded-lg">
                                <div class="text-slate-500 text-[10px]">Stok Akhir</div>
                                <div class="font-bold text-white mt-0.5">{{ selectedMovementForDetail.stock_after }} pcs</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Informasi Konveksi & Operator Pencatat (Aturan: Hanya Nama Saja) -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2.5">
                        <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                            Informasi Pengiriman & Dokumen
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">Asal Konveksi / Supplier:</span>
                            <span class="text-white font-semibold">{{ selectedMovementForDetail.destination || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400">Nomor Nota / Surat Jalan:</span>
                            <span class="font-mono text-emerald-400 font-bold">{{ selectedMovementForDetail.reference_number || '-' }}</span>
                        </div>
                        <div v-if="selectedMovementForDetail.notes" class="pt-2 border-t border-slate-800/80">
                            <span class="text-slate-400 text-[11px] block">Catatan Penerimaan:</span>
                            <span class="text-xs text-slate-300 mt-0.5 block">
                                {{ selectedMovementForDetail.notes }}
                            </span>
                        </div>
                        <!-- Operator Pencatat: Hanya Nama Saja Tanpa Email -->
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                            <span>Operator Pencatat:</span>
                            <span class="text-white font-semibold">{{ selectedMovementForDetail.user?.name || '-' }}</span>
                        </div>
                    </div>

                    <!-- 3. Foto Bukti Fisik Nota Surat Jalan -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                        <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">
                            Foto Bukti Fisik Surat Jalan / Nota
                        </div>
                        <div v-if="selectedMovementForDetail.proof_image" class="mt-2">
                            <img
                                :src="'/storage/' + selectedMovementForDetail.proof_image"
                                alt="Foto Bukti Surat Jalan"
                                @click="selectedProof = '/storage/' + selectedMovementForDetail.proof_image"
                                class="w-full max-h-52 object-cover rounded-xl border border-slate-800 cursor-pointer hover:opacity-90 transition"
                            />
                            <p class="text-[10px] text-slate-500 text-center mt-1">Klik gambar untuk melihat ukuran penuh</p>
                        </div>
                        <div v-else class="text-slate-500 italic text-[11px]">
                            Tidak ada berkas foto nota fisik yang dilampirkan pada mutasi ini.
                        </div>
                    </div>

                </div>

                <!-- Footer Modal Detail -->
                <div class="p-4 bg-slate-950 border-t border-slate-800 flex items-center justify-end">
                    <button
                        type="button"
                        @click="closeDetailModal"
                        class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs transition cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- MODAL 2: LIGHTBOX FOTO BUKTI FISIK                             -->
        <!-- ============================================================== -->
        <div
            v-if="selectedProof"
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm flex items-center justify-center p-4"
            @click.self="selectedProof = null"
        >
            <div class="relative max-w-2xl max-h-[90vh] bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden p-2">
                <img :src="selectedProof" class="w-full h-auto max-h-[80vh] object-contain rounded-xl" />
                <button
                    @click="selectedProof = null"
                    class="absolute top-4 right-4 bg-slate-950/80 hover:bg-slate-900 text-white rounded-full w-8 h-8 flex items-center justify-center border border-slate-700 transition"
                >
                    ✕
                </button>
            </div>
        </div>

        <!-- MODAL EXPORT LAPORAN BARANG MASUK -->
        <StockReportExportModal
            :show="isExportModalOpen"
            type="in"
            :export-url="route('warehouse.in.export')"
            @close="isExportModalOpen = false"
        />

    </AuthenticatedLayout>
</template>
