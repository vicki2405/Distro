<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { convertAndCompressImage } from '@/Utils/imageConverter';

const props = defineProps({
    categories: Array,
    defaultSizes: {
        type: Array,
        default: () => ['S', 'M', 'L', 'XL', 'XXL'],
    },
    availableSizes: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: '',
    category_id: props.categories.length > 0 ? props.categories[0].id : '',
    cost_price: '',
    selling_price: '',
    description: '',
    rack_location: '',
    image: null,
    images: [],
    image_labels: [],
    sizes: [...props.defaultSizes],
});

const availableSizes = ref(
    props.availableSizes && props.availableSizes.length > 0
        ? [...props.availableSizes]
        : [...props.defaultSizes, '3XL', 'ALL_SIZE']
);
const customSizeInput = ref('');

// State Upload & Konversi Multi-Foto
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
            const defaultLabel = anglePresets[newPhotos.value.length % anglePresets.length] || `Foto ${newPhotos.value.length + 1}`;

            newPhotos.value.push({
                file: result.file,
                previewUrl: result.previewUrl,
                label: defaultLabel,
                size: result.formattedConvertedSize,
                saved: result.savedPercentage,
            });
        } catch (err) {
            conversionError.value = err.message || 'Gagal mengompresi gambar.';
        }
    }

    syncFormPhotos();
    isConverting.value = false;
};

const syncFormPhotos = () => {
    form.images = newPhotos.value.map(p => p.file);
    form.image_labels = newPhotos.value.map(p => p.label);
    if (newPhotos.value.length > 0) {
        form.image = newPhotos.value[0].file;
    } else {
        form.image = null;
    }
};

const removeNewPhoto = (idx) => {
    newPhotos.value.splice(idx, 1);
    syncFormPhotos();
};

const handleFileInputChange = (event) => {
    const files = event.target.files;
    if (files && files.length > 0) {
        processMultipleFiles(Array.from(files));
    }
    if (fileInputRef.value) fileInputRef.value.value = '';
};

const handleDrop = (event) => {
    isDragging.value = false;
    const files = event.dataTransfer.files;
    if (files && files.length > 0) {
        processMultipleFiles(Array.from(files));
    }
};

const triggerFileInput = () => {
    if (fileInputRef.value) {
        fileInputRef.value.click();
    }
};

const toggleSize = (size) => {
    const idx = form.sizes.indexOf(size);
    if (idx > -1) {
        if (form.sizes.length > 1) {
            form.sizes.splice(idx, 1);
        }
    } else {
        form.sizes.push(size);
    }
};

const addCustomSize = () => {
    const s = customSizeInput.value.trim().toUpperCase();
    if (s && !availableSizes.value.includes(s)) {
        availableSizes.value.push(s);
        form.sizes.push(s);
        customSizeInput.value = '';
    }
};

const submit = () => {
    syncFormPhotos();
    form.post(route('warehouse.products.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Tambah Master Produk - Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Pendaftaran Produk Baru (Master Data)
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Daftarkan nama artikel, kategori, foto produk resmi (bisa multi-sudut), harga, dan varian ukuran ke dalam katalog distro.
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
                        1. Informasi Produk / Artikel
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Nama Model / Artikel <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Contoh: Oversize Heavy Cotton 'Tokyo Night'"
                                required
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-white py-3 px-3.5 transition placeholder-slate-600 font-semibold"
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
                                Modal HPP (Rp) <span class="text-slate-500 text-[10px] font-normal">(Opsional)</span>
                            </label>
                            <input
                                v-model.number="form.cost_price"
                                type="number"
                                min="0"
                                placeholder="Contoh: 55000"
                                class="w-full text-sm font-mono rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-emerald-400 py-3 px-3.5 transition placeholder-slate-600"
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
                                placeholder="Contoh: 145000"
                                required
                                class="w-full text-sm font-mono rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-amber-400 font-bold py-3 px-3.5 transition placeholder-slate-600"
                            />
                            <div v-if="form.errors.selling_price" class="text-xs text-rose-400 font-semibold mt-1">
                                {{ form.errors.selling_price }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Deskripsi / Karakter Bahan <span class="text-slate-500 text-[10px] font-normal">(Opsional)</span>
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Contoh: 100% Cotton Combed 24s, sablon discharge, jahitan rantai rapi."
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
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
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-100 py-3 px-3.5 transition placeholder-slate-600"
                            />
                            <div class="text-[10px] text-slate-500">
                                Membantu staf menemukan artikel di rak saat packing & stock opname.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. UPLOAD & AUTO-CONVERT FOTO PRODUK (OPSIONAL MULTI-FOTO) -->
                <div class="p-6 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-purple-400 flex items-center gap-2">
                                <span>2. Galeri Foto Produk</span>
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                    Multi-Sudut Opsional
                                </span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Unggah foto produk (bisa lebih dari 4 foto: Depan, Belakang, Samping Kiri, Kanan, dll). Foto otomatis dikompresi ringan tanpa penurunan kualitas.
                            </p>
                        </div>
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

                    <!-- LIST FOTO YANG AKAN DIUNGGAH -->
                    <div v-if="newPhotos.length > 0" class="space-y-2">
                        <div class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">
                            Daftar Foto yang Dipilih ({{ newPhotos.length }} Foto):
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="(photo, idx) in newPhotos"
                                :key="idx"
                                class="p-3 rounded-xl bg-slate-900 border border-emerald-500/30 flex items-center gap-3"
                            >
                                <img
                                    :src="photo.previewUrl"
                                    alt="Preview"
                                    class="w-16 h-16 object-contain rounded-lg bg-black/50 shrink-0"
                                />
                                <div class="flex-1 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-mono text-emerald-400 font-bold">
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
                                        placeholder="Label (contoh: Tampak Depan)"
                                        class="w-full text-xs py-1 px-2 rounded-lg bg-slate-950 border border-slate-800 text-white font-mono"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dropzone / Upload Area -->
                    <div
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
                                📸
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs font-bold text-slate-200">
                                    Klik atau seret foto-foto pakaian ke sini (Bisa pilih banyak sekaligus)
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    Contoh: Tampak Depan, Belakang, Samping Kiri, Kanan, Detail Jahitan (Format JPG, PNG, WEBP)
                                </div>
                            </div>
                        </template>
                    </div>

                    <div v-if="conversionError" class="text-xs text-rose-400 font-semibold mt-1">
                        {{ conversionError }}
                    </div>
                </div>

                <!-- 3. PEMILIHAN VARIAN UKURAN RESMI -->
                <div class="p-6 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-4">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-purple-400">
                            3. Struktur Varian Ukuran Resmi
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Pilih ukuran apa saja yang diproduksi untuk produk ini.
                        </p>
                    </div>

                    <!-- Pills Checkbox Ukuran -->
                    <div class="flex flex-wrap gap-2.5">
                        <button
                            v-for="s in availableSizes"
                            :key="s"
                            type="button"
                            @click="toggleSize(s)"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-black font-mono transition cursor-pointer flex items-center gap-2 border',
                                form.sizes.includes(s)
                                    ? 'bg-purple-500 text-slate-950 border-purple-400 shadow-md shadow-purple-500/20'
                                    : 'bg-slate-900 text-slate-400 border-slate-800 hover:border-slate-700 hover:text-white'
                            ]"
                        >
                            <span>{{ form.sizes.includes(s) ? '✓' : '+' }}</span>
                            <span>Size {{ s }}</span>
                        </button>
                    </div>

                    <!-- Tambah Ukuran Kustom -->
                    <div class="flex items-center gap-2 pt-2 border-t border-slate-800/60">
                        <span class="text-xs text-slate-400">Tambah ukuran non-standar:</span>
                        <input
                            v-model="customSizeInput"
                            @keyup.enter="addCustomSize"
                            type="text"
                            placeholder="Contoh: 4XL / ALL"
                            class="text-xs px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white w-36 uppercase font-mono"
                        />
                        <button
                            type="button"
                            @click="addCustomSize"
                            class="text-xs px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 cursor-pointer font-bold"
                        >
                            + Tambahkan
                        </button>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing || form.sizes.length === 0 || isConverting"
                        class="px-7 py-3 rounded-xl bg-purple-500 hover:bg-purple-400 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg shadow-purple-500/20 cursor-pointer transition flex items-center gap-2"
                    >
                        <span v-if="form.processing" class="w-3.5 h-3.5 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan ke Master Data Produk' }}</span>
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
