<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { convertAndCompressImage } from '@/Utils/imageConverter';

const props = defineProps({
    product: Object,
    categories: Array,
});

const form = useForm({
    _method: 'PUT',
    name: props.product.name,
    category_id: props.product.category_id,
    cost_price: props.product.cost_price,
    selling_price: props.product.selling_price,
    description: props.product.description || '',
    image: null,
    new_sizes: [],
});

// State Upload & Konversi Gambar
const fileInputRef = ref(null);
const currentImageUrl = ref(props.product.image_url);
const imagePreview = ref(null);
const conversionStats = ref(null);
const isConverting = ref(false);
const conversionError = ref(null);
const isDragging = ref(false);

const processFile = async (file) => {
    if (!file) return;

    if (!file.type.startsWith('image/')) {
        conversionError.value = 'File yang dipilih harus berupa file gambar (JPG, PNG, WEBP, dll).';
        return;
    }

    conversionError.value = null;
    isConverting.value = true;

    try {
        const result = await convertAndCompressImage(file, 1200, 1200, 0.85);
        form.image = result.file;
        imagePreview.value = result.previewUrl;
        conversionStats.value = {
            originalSize: result.formattedOriginalSize,
            convertedSize: result.formattedConvertedSize,
            savedPercentage: result.savedPercentage,
            fileName: result.file.name,
        };
    } catch (err) {
        conversionError.value = err.message || 'Terjadi kesalahan saat memproses gambar.';
    } finally {
        isConverting.value = false;
    }
};

const handleFileInputChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        processFile(file);
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        processFile(file);
    }
};

const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const removeNewImage = () => {
    form.image = null;
    imagePreview.value = null;
    conversionStats.value = null;
    conversionError.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const newSizeInput = ref('');
const addNewSize = () => {
    const s = newSizeInput.value.trim().toUpperCase();
    const existing = props.product.variants.some(v => v.size === s);
    if (s && !existing && !form.new_sizes.includes(s)) {
        form.new_sizes.push(s);
        newSizeInput.value = '';
    }
};

const removeNewSize = (idx) => {
    form.new_sizes.splice(idx, 1);
};

const submit = () => {
    form.post(route('warehouse.products.update', props.product.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="`Edit ${product.name} - Distro Ops`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Edit Master Produk: {{ product.name }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Perbarui nama produk, kategori, foto, harga, atau tambahkan varian ukuran baru.
                    </p>
                </div>
                <Link
                    :href="route('warehouse.products.index')"
                    class="text-xs font-semibold text-slate-400 hover:text-white bg-slate-900 border border-slate-800 hover:border-slate-700 px-4 py-2 rounded-xl transition"
                >
                    Kembali ke Daftar
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 p-6 sm:p-8 space-y-6 shadow-xl">

                <!-- 1. IDENTITAS UTAMA PRODUK -->
                <div class="space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-wider text-purple-400">
                        1. Informasi Produk
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Nama Model / Artikel <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-white py-3 px-3.5 transition font-semibold"
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
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-100 py-3 px-3.5 transition"
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Modal HPP (Rp)
                            </label>
                            <input
                                v-model.number="form.cost_price"
                                type="number"
                                min="0"
                                class="w-full text-sm font-mono rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-emerald-400 py-3 px-3.5 transition"
                            />
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
                                class="w-full text-sm font-mono rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-amber-400 font-bold py-3 px-3.5 transition"
                            />
                            <div v-if="form.errors.selling_price" class="text-xs text-rose-400 font-semibold mt-1">
                                {{ form.errors.selling_price }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Deskripsi / Karakter Bahan
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition"
                        ></textarea>
                    </div>
                </div>

                <!-- 2. UPLOAD & AUTO-CONVERT FOTO PRODUK -->
                <div class="p-6 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-purple-400 flex items-center gap-2">
                                <span>2. Foto Produk</span>
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                    Auto Convert & Compress WebP
                                </span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Unggah foto pakaian atau produk distro baru untuk memperbarui foto katalog.
                            </p>
                        </div>
                    </div>

                    <!-- Hidden Native File Input -->
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/jpg"
                        class="hidden"
                        @change="handleFileInputChange"
                    />

                    <!-- Display Current Photo or Dropzone -->
                    <div v-if="!imagePreview && currentImageUrl" class="flex items-center gap-4 p-4 rounded-xl bg-slate-900/90 border border-slate-800">
                        <img
                            :src="currentImageUrl"
                            alt="Foto Saat Ini"
                            class="w-20 h-20 object-cover rounded-xl border border-slate-700 shadow-md"
                        />
                        <div class="space-y-1">
                            <div class="text-xs font-bold text-slate-200">Foto Produk Aktif</div>
                            <div class="text-[11px] text-slate-400">Tersimpan di katalog distro</div>
                            <button
                                type="button"
                                @click="triggerFileInput"
                                class="text-xs font-semibold text-purple-400 hover:text-purple-300 underline cursor-pointer pt-1"
                            >
                                Ganti Foto dengan yang Baru
                            </button>
                        </div>
                    </div>

                    <!-- Dropzone if no preview and no current image, or replacing -->
                    <div
                        v-else-if="!imagePreview"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop"
                        @click="triggerFileInput"
                        :class="[
                            'border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition-all duration-200 flex flex-col items-center justify-center gap-3',
                            isDragging
                                ? 'border-purple-400 bg-purple-500/10'
                                : 'border-slate-800 hover:border-purple-500/50 bg-slate-900/50 hover:bg-slate-900/80'
                        ]"
                    >
                        <div v-if="isConverting" class="flex flex-col items-center gap-2 py-4">
                            <span class="w-8 h-8 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></span>
                            <span class="text-xs font-mono text-purple-300">Mengonversi dan mengompresi gambar...</span>
                        </div>
                        <template v-else>
                            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 text-xl">
                                📷
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs font-bold text-slate-200">
                                    Klik untuk memilih foto atau seret ke sini
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    Mendukung JPG, PNG, WEBP (Otomatis dioptimalkan)
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- New Image Preview & Stats -->
                    <div v-else class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-4 rounded-xl bg-slate-900/90 border border-slate-800">
                        <img
                            :src="imagePreview"
                            alt="Preview Foto Baru"
                            class="w-24 h-24 object-cover rounded-xl border border-slate-700 shadow-md"
                        />
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-white truncate max-w-[200px] sm:max-w-xs font-mono">
                                    {{ conversionStats?.fileName }}
                                </span>
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                    Foto Baru Terkonversi
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-[11px] font-mono">
                                <span class="text-slate-400">
                                    Awal: <span class="text-slate-300 font-bold">{{ conversionStats?.originalSize }}</span>
                                </span>
                                <span class="text-slate-500">→</span>
                                <span class="text-slate-400">
                                    Setelah Dikonversi: <span class="text-purple-400 font-bold">{{ conversionStats?.convertedSize }}</span>
                                </span>
                                <span v-if="conversionStats?.savedPercentage > 0" class="text-emerald-400 font-bold bg-emerald-500/10 px-1.5 py-0.5 rounded">
                                    Hemat {{ conversionStats?.savedPercentage }}%
                                </span>
                            </div>
                            <div class="flex items-center gap-2 pt-1">
                                <button
                                    type="button"
                                    @click="triggerFileInput"
                                    class="text-xs font-medium text-slate-300 hover:text-white underline cursor-pointer"
                                >
                                    Ganti Foto Lain
                                </button>
                                <span class="text-slate-600">•</span>
                                <button
                                    type="button"
                                    @click="removeNewImage"
                                    class="text-xs font-medium text-rose-400 hover:text-rose-300 cursor-pointer"
                                >
                                    Batalkan Perubahan Foto
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="conversionError || form.errors.image" class="text-xs text-rose-400 font-semibold mt-1">
                        {{ conversionError || form.errors.image }}
                    </div>
                </div>

                <!-- 3. DAFTAR VARIAN UKURAN AKTIF & TAMBAH UKURAN BARU -->
                <div class="p-6 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-4">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-purple-400">
                            3. Varian Ukuran Produk
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Ukuran yang sudah memiliki riwayat mutasi stok terkunci otomatis untuk menjaga audit ledger.
                        </p>
                    </div>

                    <!-- Ukuran yang sudah ada -->
                    <div class="space-y-1.5">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ukuran Aktif Saat Ini:</div>
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-for="v in product.variants"
                                :key="v.id"
                                class="px-3.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono flex items-center gap-2"
                            >
                                <span class="font-bold text-slate-200">Size {{ v.size }}</span>
                                <span class="text-slate-600">|</span>
                                <span class="text-slate-400">Stok: {{ v.current_stock }} pcs</span>
                            </div>
                        </div>
                    </div>

                    <!-- Ukuran baru yang akan ditambahkan -->
                    <div v-if="form.new_sizes.length > 0" class="space-y-1.5 pt-2 border-t border-slate-800/60">
                        <div class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Ukuran Baru yang Akan Ditambahkan:</div>
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-for="(ns, idx) in form.new_sizes"
                                :key="idx"
                                class="px-3 py-1 rounded-xl bg-emerald-950/40 border border-emerald-500/40 text-emerald-300 text-xs font-mono flex items-center gap-2"
                            >
                                <span>Size {{ ns }}</span>
                                <button type="button" @click="removeNewSize(idx)" class="text-emerald-400 hover:text-rose-400 font-bold cursor-pointer">✕</button>
                            </div>
                        </div>
                    </div>

                    <!-- Input Tambah Ukuran Baru -->
                    <div class="flex items-center gap-2 pt-2 border-t border-slate-800/60">
                        <span class="text-xs text-slate-400">Tambahkan ukuran baru:</span>
                        <input
                            v-model="newSizeInput"
                            @keyup.enter="addNewSize"
                            type="text"
                            placeholder="Contoh: XXL / 3XL"
                            class="text-xs px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white w-36 uppercase font-mono"
                        />
                        <button
                            type="button"
                            @click="addNewSize"
                            class="text-xs px-3 py-1.5 rounded-lg bg-purple-500 hover:bg-purple-400 text-slate-950 cursor-pointer font-bold transition"
                        >
                            + Tambah
                        </button>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing || isConverting"
                        class="px-7 py-3 rounded-xl bg-purple-500 hover:bg-purple-400 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg shadow-purple-500/20 cursor-pointer transition flex items-center gap-2"
                    >
                        <span v-if="form.processing" class="w-3.5 h-3.5 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Perbarui Master Produk' }}</span>
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
