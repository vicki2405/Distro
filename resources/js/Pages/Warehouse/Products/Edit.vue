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
    images: [],
    image_labels: [],
    deleted_image_ids: [],
    new_sizes: [],
});

// State Existing Images
const existingImages = ref(props.product.images ? [...props.product.images] : []);
const removeExistingImage = (id) => {
    if (!form.deleted_image_ids.includes(id)) {
        form.deleted_image_ids.push(id);
    }
    existingImages.value = existingImages.value.filter(img => img.id !== id);
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

// Varian Ukuran Baru
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
    syncFormPhotos();
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
                        Perbarui nama produk, kategori, harga, varian ukuran, atau tambahkan galeri multi-foto sudut pandang.
                    </p>
                </div>
                <Link
                    :href="route('warehouse.products.show', product.id)"
                    class="text-xs font-semibold text-slate-400 hover:text-white bg-slate-900 border border-slate-800 hover:border-slate-700 px-4 py-2 rounded-xl transition"
                >
                    ← Lihat Detail
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

                <!-- 2. GALERI MULTI-FOTO PRODUK (OPSIONAL LEBIH DARI 4 FOTO) -->
                <div class="p-6 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-purple-400 flex items-center gap-2">
                                <span>2. Galeri Foto Produk</span>
                                <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                    Multi-Sudut (Depan, Belakang, Kiri, Kanan)
                                </span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Bisa menambahkan lebih dari 4 foto pakaian dari berbagai sudut pandang. Sifatnya opsional.
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

                    <!-- FOTO YANG SUDAH TERSIMPAN DI DATABASE -->
                    <div v-if="existingImages.length > 0 || product.image_url" class="space-y-2">
                        <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            Foto yang Sedang Aktif:
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <!-- Foto Utama -->
                            <div v-if="product.image_url" class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex flex-col items-center gap-2">
                                <img
                                    :src="product.image_url"
                                    alt="Foto Utama"
                                    class="w-full h-28 object-contain rounded-lg bg-black/40"
                                />
                                <span class="text-[10px] font-mono font-bold text-amber-400">Foto Utama</span>
                            </div>

                            <!-- Galeri Foto Tambahan -->
                            <div
                                v-for="img in existingImages"
                                :key="img.id"
                                class="p-2.5 rounded-xl bg-slate-900 border border-slate-800 flex flex-col items-center gap-2 relative group"
                            >
                                <img
                                    :src="img.url"
                                    :alt="img.label || 'Foto Galeri'"
                                    class="w-full h-28 object-contain rounded-lg bg-black/40"
                                />
                                <span class="text-[10px] font-mono text-slate-300 truncate max-w-full">
                                    {{ img.label || 'Foto Galeri' }}
                                </span>
                                <button
                                    type="button"
                                    @click="removeExistingImage(img.id)"
                                    class="text-[10px] text-rose-400 hover:text-rose-300 underline cursor-pointer"
                                >
                                    🗑️ Hapus Foto
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PREVIEW FOTO BARU YANG AKAN DIUNGGAH -->
                    <div v-if="newPhotos.length > 0" class="space-y-2 pt-2 border-t border-slate-800/60">
                        <div class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">
                            Foto Baru yang Akan Ditambahkan ({{ newPhotos.length }} Foto):
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
                                        placeholder="Label (contoh: Tampak Belakang)"
                                        class="w-full text-xs py-1 px-2 rounded-lg bg-slate-950 border border-slate-800 text-white font-mono"
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
                            'border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition-all duration-200 flex flex-col items-center justify-center gap-2',
                            isDragging
                                ? 'border-purple-400 bg-purple-500/10'
                                : 'border-slate-800 hover:border-purple-500/50 bg-slate-900/50 hover:bg-slate-900/80'
                        ]"
                    >
                        <div v-if="isConverting" class="flex flex-col items-center gap-2 py-3">
                            <span class="w-6 h-6 border-2 border-purple-500 border-t-transparent rounded-full animate-spin"></span>
                            <span class="text-xs font-mono text-purple-300">Mengonversi dan mengompresi foto...</span>
                        </div>
                        <template v-else>
                            <span class="text-2xl">📸</span>
                            <div class="text-xs font-bold text-slate-200">
                                + Klik atau seret foto-foto baru ke sini (Bisa pilih banyak sekaligus)
                            </div>
                            <div class="text-[11px] text-slate-500">
                                Contoh sudut: Tampak Depan, Belakang, Samping Kiri, Samping Kanan, Detail Jahitan (Format JPG, PNG, WEBP)
                            </div>
                        </template>
                    </div>

                    <div v-if="conversionError" class="text-xs text-rose-400 font-semibold">
                        {{ conversionError }}
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
