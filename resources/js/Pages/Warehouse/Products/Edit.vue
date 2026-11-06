<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { convertAndCompressImage } from '@/Utils/imageConverter';

const props = defineProps({
    product: Object,
    categories: Array,
    availableSizes: {
        type: Array,
        default: () => [],
    },
    isOwner: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    _method: 'PUT',
    name: props.product.name,
    category_id: props.product.category_id,
    cost_price: props.product.cost_price,
    selling_price: props.product.selling_price,
    description: props.product.description || '',
    rack_location: props.product.rack_location || '',
    image: null,
    images: [],
    image_labels: [],
    deleted_image_ids: [],
    delete_main_image: false,
    primary_image_id: null,
    variant_stocks: [],
    deleted_variant_ids: [],
    new_variants: [],
    new_sizes: [],
});

// State Existing Images
const existingImages = ref(props.product.images ? [...props.product.images] : []);

// Cek apakah ada Foto Utama legacy yang tidak ada di tabel product_images
const hasStandaloneMainImage = ref(
    Boolean(props.product.image && !props.product.images?.some(img => img.image_path === props.product.image))
);

const primaryImageId = ref(
    props.product.images?.find(img => img.image_path === props.product.image)?.id ||
    (existingImages.value.length > 0 ? existingImages.value[0].id : null)
);

const setPrimaryImage = (id) => {
    primaryImageId.value = id;
    form.primary_image_id = id;
};

const removeExistingImage = (id) => {
    if (!form.deleted_image_ids.includes(id)) {
        form.deleted_image_ids.push(id);
    }
    existingImages.value = existingImages.value.filter(img => img.id !== id);
    if (primaryImageId.value === id) {
        primaryImageId.value = existingImages.value.length > 0 ? existingImages.value[0].id : null;
        form.primary_image_id = primaryImageId.value;
    }
};

const removeStandaloneMainImage = () => {
    hasStandaloneMainImage.value = false;
    form.delete_main_image = true;
};

// State Upload Foto Baru (Multi-Foto)
const fileInputRef = ref(null);
const newPhotos = ref([]);
const isConverting = ref(false);
const conversionError = ref(null);
const isDragging = ref(false);

const anglePresets = ['Tampak Depan', 'Tampak Belakang', 'Samping Kiri', 'Samping Kanan', 'Detail Sablon / Bahan'];

const processMultipleFiles = async (files) => {
    if (!files || files.length === 0) return;
    conversionError.value = null;
    isConverting.value = true;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (!file.type.startsWith('image/')) continue;

        try {
            const result = await convertAndCompressImage(file, 1200, 1200, 0.85);
            // Default preset angle label sesuai urutan
            const defaultLabel = anglePresets[newPhotos.value.length % anglePresets.length] || `Foto ${newPhotos.value.length + 1}`;

            newPhotos.value.push({
                file: result.file,
                previewUrl: result.previewUrl,
                label: defaultLabel,
                size: result.formattedConvertedSize,
                saved: result.compressionRatio,
            });
        } catch (err) {
            conversionError.value = `Gagal memproses gambar "${file.name}": ${err.message}`;
            break;
        }
    }

    isConverting.value = false;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const handleFileInputChange = (e) => {
    processMultipleFiles(e.target.files);
};

const handleDrop = (e) => {
    isDragging.value = false;
    processMultipleFiles(e.dataTransfer.files);
};

const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const removeNewPhoto = (index) => {
    newPhotos.value.splice(index, 1);
};

// State Varian Ukuran & Stok
const existingVariants = ref(
    props.product.variants
        ? props.product.variants.map(v => ({
            id: v.id,
            size: v.size,
            sku: v.sku,
            current_stock: v.current_stock,
            original_stock: v.current_stock,
        }))
        : []
);

const newVariants = ref([]);

const removeExistingVariant = (id) => {
    const totalRemaining = existingVariants.value.length + newVariants.value.length;
    if (totalRemaining <= 1) {
        alert('Produk harus memiliki minimal satu ukuran.');
        return;
    }

    if (confirm('Apakah Anda yakin ingin menghapus varian ukuran ini? Tindakan ini akan menghapus varian ukuran dari katalog.')) {
        if (!form.deleted_variant_ids.includes(id)) {
            form.deleted_variant_ids.push(id);
        }
        existingVariants.value = existingVariants.value.filter(v => v.id !== id);
    }
};

const removeNewVariant = (index) => {
    newVariants.value.splice(index, 1);
};

const stepStock = (variant, amount) => {
    const newVal = (parseInt(variant.current_stock) || 0) + amount;
    if (newVal >= 0) {
        variant.current_stock = newVal;
    }
};

const stepNewStock = (nv, amount) => {
    const newVal = (parseInt(nv.initial_stock) || 0) + amount;
    if (newVal >= 0) {
        nv.initial_stock = newVal;
    }
};

// Form Tambah Ukuran Baru (Pilihan Dropdown + Ketik Stok)
const selectedDropdownSize = ref('');
const customSizeText = ref('');
const inputStockValue = ref(null);

const standardSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', 'ALL_SIZE'];

const sizeDropdownOptions = computed(() => {
    const fromProps = props.availableSizes && props.availableSizes.length > 0
        ? props.availableSizes
        : standardSizes;

    const usedSizes = [
        ...existingVariants.value.map(v => v.size.toUpperCase()),
        ...newVariants.value.map(v => v.size.toUpperCase())
    ];

    return fromProps.filter(s => !usedSizes.includes(s.toUpperCase()));
});

const saveVariantFromDropdown = () => {
    let sizeToSave = '';
    if (selectedDropdownSize.value === 'CUSTOM') {
        sizeToSave = customSizeText.value.trim().toUpperCase();
        if (!sizeToSave) {
            alert('Harap ketik nama ukuran yang ingin ditambahkan.');
            return;
        }
    } else if (selectedDropdownSize.value) {
        sizeToSave = selectedDropdownSize.value.trim().toUpperCase();
    } else {
        alert('Pilih ukuran terlebih dahulu.');
        return;
    }

    const isDuplicate = existingVariants.value.some(v => v.size.toUpperCase() === sizeToSave) ||
                        newVariants.value.some(v => v.size.toUpperCase() === sizeToSave);

    if (isDuplicate) {
        alert(`Ukuran ${sizeToSave} sudah ada dalam daftar.`);
        return;
    }

    const stock = inputStockValue.value !== null && inputStockValue.value !== ''
        ? Math.max(0, parseInt(inputStockValue.value) || 0)
        : 0;

    newVariants.value.push({
        size: sizeToSave,
        initial_stock: stock,
    });

    selectedDropdownSize.value = '';
    customSizeText.value = '';
    inputStockValue.value = null;
};

const totalStockCalculated = computed(() => {
    const existingTotal = existingVariants.value.reduce((acc, curr) => acc + (parseInt(curr.current_stock) || 0), 0);
    const newTotal = newVariants.value.reduce((acc, curr) => acc + (parseInt(curr.initial_stock) || 0), 0);
    return existingTotal + newTotal;
});

const submit = () => {
    if (existingVariants.value.length === 0 && newVariants.value.length === 0) {
        alert('Produk harus memiliki minimal satu ukuran.');
        return;
    }

    form.variant_stocks = existingVariants.value.map(v => ({
        id: v.id,
        current_stock: v.current_stock,
    }));

    form.new_variants = newVariants.value.map(v => ({
        size: v.size,
        initial_stock: v.initial_stock,
    }));

    form.new_sizes = newVariants.value.map(v => v.size);

    form.images = newPhotos.value.map(p => p.file);
    form.image_labels = newPhotos.value.map(p => p.label);

    form.post(route('warehouse.products.update', props.product.id), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit ${product.name} - Distro Ops`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-purple-500/10 border border-purple-500/25 text-purple-400 text-xs font-mono font-bold mb-1">
                        <span>👕 MASTER PRODUK • {{ product.code }}</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Edit Master Produk: {{ product.name }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Perbarui nama produk, kategori, harga jual/modal, varian ukuran, serta galeri multi-sudut pandang.
                    </p>
                </div>
                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('warehouse.products.show', product.id)"
                        class="text-xs font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 border border-slate-700 px-4 py-2.5 rounded-xl transition active:scale-95 shadow-sm"
                    >
                        ← Lihat Detail
                    </Link>
                </div>
            </div>
        </template>

        <!-- CONTAINER KERJA FULL-WIDTH (2-KOLOM ERGONOMIS & TANPA RUANG KOSONG) -->
        <div class="w-full">
            <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- ========================================== -->
                <!-- KOLOM KIRI (7/12): DATA PRODUK & STOK RAK -->
                <!-- ========================================== -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- CARD 1: INFORMASI UTAMA PRODUK -->
                    <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 sm:p-7 space-y-5 shadow-xl">
                        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                            <h3 class="text-xs font-black uppercase tracking-wider text-purple-400 flex items-center gap-2">
                                <span>1. Informasi Utama Produk</span>
                            </h3>
                            <span class="text-[11px] font-mono text-slate-500">Kode: {{ product.code }}</span>
                        </div>

                        <!-- Baris 1: Nama & Kategori -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                    Nama Model / Artikel <span class="text-rose-400">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Kaos Oversize Heavyweight Black"
                                    class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-white py-2.5 px-3.5 transition font-semibold"
                                />
                                <div v-if="form.errors.name" class="text-xs text-rose-400 font-semibold mt-1">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                    Kategori Produk <span class="text-rose-400">*</span>
                                </label>
                                <select
                                    v-model="form.category_id"
                                    required
                                    class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition"
                                >
                                    <option v-for="c in categories" :key="c.id" :value="c.id">
                                        {{ c.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.category_id" class="text-xs text-rose-400 font-semibold mt-1">
                                    {{ form.errors.category_id }}
                                </div>
                            </div>
                        </div>

                        <!-- Baris 2: Harga HPP & Harga Jual -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                    Modal HPP (Rp)
                                </label>
                                <input
                                    v-model.number="form.cost_price"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    class="w-full text-sm font-mono rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-emerald-400 py-2.5 px-3.5 transition"
                                />
                                <p class="text-[10px] text-slate-500">Harga pokok modal produksi konveksi.</p>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                    Harga Jual Retail (Rp) <span class="text-rose-400">*</span>
                                </label>
                                <input
                                    v-model.number="form.selling_price"
                                    type="number"
                                    min="0"
                                    required
                                    placeholder="Contoh: 125000"
                                    class="w-full text-sm font-mono rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-amber-400 font-bold py-2.5 px-3.5 transition"
                                />
                                <div v-if="form.errors.selling_price" class="text-xs text-rose-400 font-semibold mt-1">
                                    {{ form.errors.selling_price }}
                                </div>
                            </div>
                        </div>

                        <!-- Baris 3: Deskripsi Bahan & Lokasi Rak -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                    Deskripsi / Karakter Bahan <span class="text-slate-500 text-[10px] font-normal">(Opsional)</span>
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Tuliskan spesifikasi detail bahan (misal: Cotton Combed 24s, sablon discharge, jahitan rantai)..."
                                    class="w-full text-xs sm:text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition leading-relaxed"
                                ></textarea>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                    Lokasi Rak / Penyimpanan <span class="text-slate-500 text-[10px] font-normal">(Opsional)</span>
                                </label>
                                <input
                                    v-model="form.rack_location"
                                    type="text"
                                    placeholder="Contoh: Rak A-01, Gantungan Depan, atau kosongkan"
                                    class="w-full text-xs sm:text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition"
                                />
                                <div class="text-[10px] text-slate-500">
                                    Membantu staf menemukan artikel di rak saat packing & stock opname.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: MATRIKS VARIAN UKURAN & STOK GUDANG -->
                    <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 sm:p-7 space-y-5 shadow-xl">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-800/80 pb-4">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-purple-400 flex items-center gap-2">
                                    <span>2. Varian Ukuran & Stok Fisik Rak</span>
                                    <span v-if="isOwner" class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-mono font-bold">
                                        Owner: Edit & Hapus Aktif
                                    </span>
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Sesuaikan stok fisik per ukuran atau tambahkan ukuran baru untuk artikel ini.
                                </p>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-xs text-slate-400">Total Stok Fisik: </span>
                                <span class="text-base font-black text-emerald-400 font-mono">{{ totalStockCalculated }} pcs</span>
                            </div>
                        </div>

                        <!-- TABEL MATRIKS VARIAN UKURAN (LUAS & LEGA) -->
                        <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950/70">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-900 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800 font-mono text-[10px]">
                                        <tr>
                                            <th class="py-3 px-4">Ukuran</th>
                                            <th class="py-3 px-4">SKU Internal</th>
                                            <th class="py-3 px-4 text-center">Stok Fisik di Rak</th>
                                            <th class="py-3 px-4 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60 font-mono">
                                        <!-- 1. Varian yang Sudah Ada di Database -->
                                        <tr
                                            v-for="v in existingVariants"
                                            :key="v.id"
                                            class="hover:bg-slate-800/30 transition"
                                        >
                                            <td class="py-3 px-4 font-bold text-white text-sm">
                                                Size {{ v.size }}
                                            </td>
                                            <td class="py-3 px-4 text-slate-400 text-xs">
                                                {{ v.sku }}
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <div v-if="isOwner" class="inline-flex items-center gap-1.5 justify-center">
                                                    <button
                                                        type="button"
                                                        @click="stepStock(v, -1)"
                                                        class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs flex items-center justify-center transition cursor-pointer active:scale-95"
                                                    >
                                                        -
                                                    </button>
                                                    <input
                                                        v-model.number="v.current_stock"
                                                        type="number"
                                                        min="0"
                                                        class="w-20 text-center text-sm font-bold font-mono rounded-lg border-slate-800 bg-slate-950 text-emerald-400 py-1 focus:ring-1 focus:ring-purple-500 focus:border-purple-500"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="stepStock(v, 1)"
                                                        class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs flex items-center justify-center transition cursor-pointer active:scale-95"
                                                    >
                                                        +
                                                    </button>
                                                    <span
                                                        v-if="v.current_stock !== v.original_stock"
                                                        class="text-[10px] ml-1 font-bold"
                                                        :class="v.current_stock > v.original_stock ? 'text-emerald-400' : 'text-rose-400'"
                                                    >
                                                        ({{ v.current_stock > v.original_stock ? '+' : '' }}{{ v.current_stock - v.original_stock }})
                                                    </span>
                                                </div>
                                                <span v-else class="font-bold text-slate-200 text-sm">
                                                    {{ v.current_stock }} pcs
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <button
                                                    v-if="isOwner"
                                                    type="button"
                                                    @click="removeExistingVariant(v.id)"
                                                    class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-[11px] font-sans font-bold cursor-pointer transition active:scale-95"
                                                    title="Hapus varian ukuran ini"
                                                >
                                                    🗑️ Hapus
                                                </button>
                                                <span v-else class="text-slate-600 text-[11px]">-</span>
                                            </td>
                                        </tr>

                                        <!-- 2. Varian Baru yang Akan Ditambahkan -->
                                        <tr
                                            v-for="(nv, idx) in newVariants"
                                            :key="'new-' + idx"
                                            class="bg-emerald-950/20 hover:bg-emerald-950/30 transition border-l-2 border-emerald-500"
                                        >
                                            <td class="py-3 px-4 font-bold text-emerald-300 text-sm flex items-center gap-2">
                                                <span>Size {{ nv.size }}</span>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-sans uppercase">
                                                    Baru
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-slate-500 text-xs italic">
                                                {{ product.code }}-{{ nv.size }}
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                <div class="inline-flex items-center gap-1.5 justify-center">
                                                    <button
                                                        type="button"
                                                        @click="stepNewStock(nv, -1)"
                                                        class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs flex items-center justify-center transition cursor-pointer active:scale-95"
                                                    >
                                                        -
                                                    </button>
                                                    <input
                                                        v-model.number="nv.initial_stock"
                                                        type="number"
                                                        min="0"
                                                        placeholder="Stok"
                                                        class="w-20 text-center text-sm font-bold font-mono rounded-lg border-emerald-800/60 bg-slate-950 text-emerald-300 py-1 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="stepNewStock(nv, 1)"
                                                        class="w-7 h-7 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs flex items-center justify-center transition cursor-pointer active:scale-95"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <button
                                                    type="button"
                                                    @click="removeNewVariant(idx)"
                                                    class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-[11px] font-sans font-bold cursor-pointer transition active:scale-95"
                                                >
                                                    Batal ✕
                                                </button>
                                            </td>
                                        </tr>

                                        <tr v-if="existingVariants.length === 0 && newVariants.length === 0">
                                            <td colspan="4" class="py-6 text-center text-slate-500">
                                                Belum ada ukuran yang terdaftar. Tambahkan ukuran di bawah ini.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- FORM MODEL DROPDOWN PILIHAN SIZE + KETIK ANGKA STOK + TOMBOL SIMPAN -->
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                            <div class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">
                                Tambah Ukuran Baru:
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                                <!-- 1. Dropdown Pilihan Ukuran -->
                                <div :class="selectedDropdownSize === 'CUSTOM' ? 'sm:col-span-4' : 'sm:col-span-5'" class="space-y-1">
                                    <label class="text-[11px] font-semibold text-slate-400">Pilih Ukuran:</label>
                                    <select
                                        v-model="selectedDropdownSize"
                                        class="w-full text-xs py-2 px-3 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition"
                                    >
                                        <option value="" disabled>-- Pilih Ukuran --</option>
                                        <option v-for="s in sizeDropdownOptions" :key="s" :value="s">Size {{ s }}</option>
                                        <option value="CUSTOM">Lainnya (Ketik Manual...)</option>
                                    </select>
                                </div>

                                <!-- Input jika memilih kustom -->
                                <div v-if="selectedDropdownSize === 'CUSTOM'" class="sm:col-span-3 space-y-1">
                                    <label class="text-[11px] font-semibold text-slate-400">Nama Ukuran:</label>
                                    <input
                                        v-model="customSizeText"
                                        type="text"
                                        placeholder="Contoh: 4XL / 28"
                                        class="w-full text-xs py-2 px-3 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono uppercase focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition"
                                    />
                                </div>

                                <!-- 2. Ketik Angka Stok -->
                                <div :class="selectedDropdownSize === 'CUSTOM' ? 'sm:col-span-2' : 'sm:col-span-4'" class="space-y-1">
                                    <label class="text-[11px] font-semibold text-slate-400">Jumlah Stok (Pcs):</label>
                                    <input
                                        v-model.number="inputStockValue"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        class="w-full text-xs py-2 px-3 rounded-lg bg-slate-900 border border-slate-800 text-emerald-400 font-mono font-bold text-center focus:border-purple-500 focus:ring-1 focus:ring-purple-500 transition"
                                    />
                                </div>

                                <!-- 3. Tombol Simpan -->
                                <div :class="selectedDropdownSize === 'CUSTOM' ? 'sm:col-span-3' : 'sm:col-span-3'">
                                    <button
                                        type="button"
                                        @click="saveVariantFromDropdown"
                                        class="w-full py-2 px-3 rounded-lg bg-purple-500 hover:bg-purple-400 text-slate-950 font-black text-xs transition cursor-pointer shadow-md shadow-purple-500/10 flex items-center justify-center gap-1.5 active:scale-95"
                                    >
                                        <span>+ Simpan Ukuran</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ============================================== -->
                <!-- KOLOM KANAN (5/12): MEDIA FOTO & ACTION BUTTON -->
                <!-- ============================================== -->
                <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-4">

                    <!-- CARD 3: GALERI MULTI-FOTO PRODUK -->
                    <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 space-y-4 shadow-xl">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-wider text-purple-400 flex items-center gap-2">
                                    <span>3. Galeri Foto Produk</span>
                                </h3>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Multi-Sudut (Depan, Belakang, Detail Jahitan).
                                </p>
                            </div>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">
                                {{ existingImages.length + newPhotos.length + (hasStandaloneMainImage ? 1 : 0) }} Foto
                            </span>
                        </div>

                        <!-- Hidden Native Multiple File Input -->
                        <input
                            ref="fileInputRef"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp,image/jpg"
                            class="hidden"
                            @change="handleFileInputChange"
                        />

                        <!-- FOTO YANG SUDAH TERSIMPAN DI DATABASE -->
                        <div v-if="existingImages.length > 0 || hasStandaloneMainImage" class="space-y-2">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center justify-between">
                                <span>Foto Aktif Saat Ini:</span>
                                <span class="text-[9px] text-slate-500">Pilih '★ Utama' untuk sampul</span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                <!-- Foto Utama Mandiri (Legacy) -->
                                <div v-if="hasStandaloneMainImage" class="p-2 rounded-xl bg-slate-950 border border-slate-800 flex flex-col items-center gap-1.5 relative">
                                    <img
                                        :src="product.image_url"
                                        alt="Foto Utama"
                                        class="w-full h-24 object-contain rounded-lg bg-black/40"
                                        @error="(e) => e.target.style.opacity = '0.3'"
                                    />
                                    <span class="text-[9px] font-mono font-bold text-amber-400 bg-amber-400/10 px-1.5 py-0.5 rounded border border-amber-400/20">
                                        ★ Utama
                                    </span>
                                    <button
                                        type="button"
                                        @click="removeStandaloneMainImage"
                                        class="text-[10px] text-rose-400 hover:text-rose-300 underline cursor-pointer"
                                    >
                                        🗑️ Hapus
                                    </button>
                                </div>

                                <!-- Galeri Foto Aktif -->
                                <div
                                    v-for="img in existingImages"
                                    :key="img.id"
                                    class="p-2 rounded-xl bg-slate-950 border flex flex-col items-center gap-1.5 relative transition"
                                    :class="primaryImageId === img.id ? 'border-amber-500/70 shadow-lg shadow-amber-500/10 bg-slate-900/90' : 'border-slate-800'"
                                >
                                    <img
                                        :src="img.url"
                                        :alt="img.label || 'Foto Galeri'"
                                        class="w-full h-24 object-contain rounded-lg bg-black/40"
                                        @error="(e) => e.target.style.opacity = '0.3'"
                                    />
                                    <div class="flex items-center gap-1">
                                        <span
                                            v-if="primaryImageId === img.id"
                                            class="text-[9px] font-mono font-bold text-amber-400 bg-amber-400/10 px-1.5 py-0.5 rounded border border-amber-400/20"
                                        >
                                            ★ Utama
                                        </span>
                                        <span class="text-[9px] font-mono text-slate-300 truncate max-w-[90px]">
                                            {{ img.label || 'Galeri' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 pt-0.5 text-[10px]">
                                        <button
                                            v-if="primaryImageId !== img.id"
                                            type="button"
                                            @click="setPrimaryImage(img.id)"
                                            class="text-amber-400 hover:text-amber-300 underline cursor-pointer"
                                        >
                                            Utama
                                        </button>
                                        <button
                                            type="button"
                                            @click="removeExistingImage(img.id)"
                                            class="text-rose-400 hover:text-rose-300 underline cursor-pointer"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PREVIEW FOTO BARU YANG AKAN DIUNGGAH -->
                        <div v-if="newPhotos.length > 0" class="space-y-2 pt-2 border-t border-slate-800/60">
                            <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">
                                Foto Baru yang Ditambahkan ({{ newPhotos.length }} Foto):
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="(photo, idx) in newPhotos"
                                    :key="idx"
                                    class="p-2.5 rounded-xl bg-slate-950 border border-emerald-500/30 flex items-center gap-2.5"
                                >
                                    <img
                                        :src="photo.previewUrl"
                                        alt="Preview"
                                        class="w-14 h-14 object-contain rounded-lg bg-black/50 shrink-0"
                                    />
                                    <div class="flex-1 space-y-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[9px] font-mono text-emerald-400 font-bold">
                                                {{ photo.size }} • Hemat {{ photo.saved }}%
                                            </span>
                                            <button
                                                type="button"
                                                @click="removeNewPhoto(idx)"
                                                class="text-rose-400 hover:text-rose-300 text-xs font-bold cursor-pointer"
                                            >
                                                ✕
                                            </button>
                                        </div>
                                        <input
                                            v-model="photo.label"
                                            type="text"
                                            placeholder="Label (contoh: Tampak Belakang)"
                                            class="w-full text-xs py-1 px-2 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DROPZONE UNTUK TAMBAH FOTO -->
                        <div
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                            @click="triggerFileInput"
                            :class="[
                                'border-2 border-dashed rounded-xl p-5 text-center cursor-pointer transition-all duration-200 flex flex-col items-center justify-center gap-1.5',
                                isDragging
                                    ? 'border-purple-400 bg-purple-500/10'
                                    : 'border-slate-800 hover:border-purple-500/50 bg-slate-950/60 hover:bg-slate-950'
                            ]"
                        >
                            <div v-if="isConverting" class="flex flex-col items-center gap-2 py-2">
                                <span class="w-5 h-5 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></span>
                                <span class="text-[11px] font-mono text-purple-300">Mengonversi dan mengompresi foto...</span>
                            </div>
                            <template v-else>
                                <span class="text-xl">📸</span>
                                <div class="text-xs font-bold text-slate-200">
                                    + Klik atau seret foto-foto baru ke sini
                                </div>
                                <div class="text-[10px] text-slate-500">
                                    Bisa pilih banyak foto sekaligus (JPG, PNG, WEBP)
                                </div>
                            </template>
                        </div>

                        <div v-if="conversionError" class="text-xs text-rose-400 font-semibold">
                            {{ conversionError }}
                        </div>
                    </div>

                    <!-- CARD 4: PANEL RINGKASAN & TOMBOL SIMPAN (STICKY) -->
                    <div class="bg-slate-900/90 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Ringkasan Produk</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                {{ product.code }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs font-mono">
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <span class="text-[10px] text-slate-500 block uppercase">Total Varian</span>
                                <span class="text-sm font-bold text-white">{{ existingVariants.length + newVariants.length }} Ukuran</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <span class="text-[10px] text-slate-500 block uppercase">Total Stok Fisik</span>
                                <span class="text-sm font-bold text-emerald-400">{{ totalStockCalculated }} Pcs</span>
                            </div>
                        </div>

                        <!-- Notifikasi Error Validasi Jika Ada -->
                        <div v-if="Object.keys(form.errors).length > 0" class="p-3 rounded-xl bg-rose-950/40 border border-rose-800/80 text-rose-300 text-xs space-y-1">
                            <div class="font-bold flex items-center gap-1">
                                <span>⚠️</span>
                                <span>Periksa input form:</span>
                            </div>
                            <ul class="list-disc pl-4 text-[11px] space-y-0.5">
                                <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                            </ul>
                        </div>

                        <!-- Tombol Aksi Simpan & Batal -->
                        <div class="pt-2 flex items-center gap-3">
                            <Link
                                :href="route('warehouse.products.show', product.id)"
                                class="flex-1 py-3 px-3.5 rounded-xl text-center bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition border border-slate-700 cursor-pointer active:scale-95"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing || isConverting"
                                class="flex-2 py-3 px-5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-400 hover:to-indigo-500 disabled:opacity-50 text-white text-xs font-black shadow-lg shadow-purple-500/25 cursor-pointer transition flex items-center justify-center gap-2 active:scale-95"
                            >
                                <span v-if="form.processing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <span>{{ form.processing ? 'Menyimpan...' : 'Perbarui Master Produk' }}</span>
                            </button>
                        </div>
                    </div>

                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
