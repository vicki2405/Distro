<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    imageUrl: {
        type: String,
        default: null,
    },
    images: {
        type: Array,
        default: () => [],
    },
    productName: {
        type: String,
        default: 'Artikel Distro',
    },
    categoryName: {
        type: String,
        default: 'Produk Distro',
    },
    totalStock: {
        type: Number,
        default: 0,
    },
    productId: {
        type: [Number, String],
        default: null,
    },
});

// Gabungkan semua foto (galeri multi-foto + foto utama)
const galleryList = computed(() => {
    const list = [];
    if (props.imageUrl) {
        list.push({
            id: 'main',
            url: props.imageUrl,
            label: 'Foto Utama (Depan)',
        });
    }
    if (props.images && props.images.length > 0) {
        props.images.forEach((img, idx) => {
            // Hindari duplikat jika url sama dengan foto utama
            if (img.url !== props.imageUrl) {
                list.push({
                    id: img.id,
                    url: img.url,
                    label: img.label || `Sudut ${idx + 1}`,
                });
            }
        });
    }
    return list;
});

const activeIndex = ref(0);
const activeImage = computed(() => {
    if (galleryList.value.length === 0) return null;
    return galleryList.value[activeIndex.value] || galleryList.value[0];
});

const isZoomed = ref(false);
const zoomX = ref(50);
const zoomY = ref(50);
const isLightboxOpen = ref(false);

const handleMouseMove = (e) => {
    if (!activeImage.value) return;
    const rect = e.currentTarget.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    zoomX.value = Math.max(0, Math.min(100, x));
    zoomY.value = Math.max(0, Math.min(100, y));
};

const handleMouseEnter = () => {
    if (activeImage.value) {
        isZoomed.value = true;
    }
};

const handleMouseLeave = () => {
    isZoomed.value = false;
    zoomX.value = 50;
    zoomY.value = 50;
};
</script>

<template>
    <div class="relative w-full rounded-3xl overflow-hidden bg-slate-900/80 border border-slate-800 shadow-2xl flex flex-col group">

        <!-- HEADER SHOWCASE -->
        <div class="p-4 sm:p-5 border-b border-slate-800/80 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                <span class="text-xs font-mono font-bold uppercase tracking-wider text-white">
                    Studio Showcase
                </span>
                <span v-if="galleryList.length > 1" class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-slate-950 text-slate-400 border border-slate-800">
                    {{ activeIndex + 1 }} / {{ galleryList.length }} Foto
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    v-if="activeImage"
                    type="button"
                    @click="isLightboxOpen = true"
                    class="text-xs font-medium px-3 py-1.5 rounded-xl bg-slate-950 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>🔍</span>
                    <span>Layar Penuh</span>
                </button>
                <Link
                    v-if="productId"
                    :href="route('warehouse.products.edit', productId)"
                    class="text-xs font-medium px-3 py-1.5 rounded-xl bg-slate-950 hover:bg-slate-800 text-purple-400 hover:text-purple-300 border border-slate-800 transition"
                >
                    + Kelola Foto
                </Link>
            </div>
        </div>

        <!-- MAIN IMAGE VIEWPORT DENGAN DEEP ZOOM INTERAKTIF -->
        <div
            class="relative w-full aspect-square sm:aspect-[4/4.2] overflow-hidden bg-slate-950 flex items-center justify-center cursor-crosshair select-none"
            @mousemove="handleMouseMove"
            @mouseenter="handleMouseEnter"
            @mouseleave="handleMouseLeave"
            @click="activeImage ? (isLightboxOpen = true) : null"
        >
            <!-- FOTO AKTIF DENGAN LOUPE ZOOM -->
            <template v-if="activeImage">
                <div class="w-full h-full flex items-center justify-center p-6 sm:p-8">
                    <img
                        :key="activeImage.url"
                        :src="activeImage.url"
                        :alt="productName"
                        class="max-w-full max-h-full object-contain rounded-2xl drop-shadow-2xl transition-transform duration-150 ease-out"
                        :style="{
                            transformOrigin: `${zoomX}% ${zoomY}%`,
                            transform: isZoomed ? 'scale(2.3)' : 'scale(1)',
                        }"
                    />
                </div>

                <!-- Floating Zoom Indicator Hint -->
                <div
                    class="absolute bottom-4 inset-x-0 flex justify-center pointer-events-none transition-opacity duration-200"
                    :class="isZoomed ? 'opacity-0' : 'opacity-100'"
                >
                    <span class="text-[11px] font-mono text-slate-300 bg-slate-950/85 px-3.5 py-1 rounded-full border border-slate-800/90 backdrop-blur-md shadow-lg flex items-center gap-2">
                        <span>🔍</span>
                        <span>Arahkan mouse untuk zoom detail kain & sablon</span>
                    </span>
                </div>

                <!-- Label Sudut Foto Aktif (Kiri Atas) -->
                <div
                    v-if="activeImage.label"
                    class="absolute top-4 left-4 pointer-events-none px-2.5 py-1 rounded-lg bg-slate-950/85 border border-slate-800 text-amber-400 text-[10px] font-mono font-bold backdrop-blur-md shadow"
                >
                    {{ activeImage.label }}
                </div>

                <!-- Indikator Zoom Aktif (Kanan Atas) -->
                <div
                    v-if="isZoomed"
                    class="absolute top-4 right-4 pointer-events-none px-2.5 py-1 rounded-lg bg-purple-500/90 text-slate-950 text-[10px] font-mono font-black backdrop-blur-md shadow"
                >
                    ZOOM 2.3x
                </div>
            </template>

            <!-- FALLBACK JIKA BELUM ADA FOTO -->
            <div v-else class="flex flex-col items-center justify-center text-center p-8 space-y-3">
                <div class="w-20 h-20 rounded-3xl bg-slate-900 border border-slate-800 flex items-center justify-center text-4xl shadow-inner">
                    👕
                </div>
                <div>
                    <h4 class="text-sm font-bold text-white">Belum Ada Foto Produk</h4>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs">
                        Unggah foto artikel pakaian (Depan, Belakang, Kiri, Kanan) untuk menampilkan galeri di sini.
                    </p>
                </div>
                <Link
                    v-if="productId"
                    :href="route('warehouse.products.edit', productId)"
                    class="px-4 py-2 rounded-xl text-xs font-bold text-slate-950 bg-purple-500 hover:bg-purple-400 shadow-lg shadow-purple-500/20 transition cursor-pointer"
                >
                    + Upload Foto Sekarang
                </Link>
            </div>
        </div>

        <!-- THUMBNAIL MULTI-FOTO GALLERY BAR (JIKA ADA LEBIH DARI 1 FOTO) -->
        <div v-if="galleryList.length > 1" class="p-3 bg-slate-950 border-t border-slate-800/80">
            <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 mb-2 px-1 flex items-center justify-between">
                <span>Sudut Pandang Foto (Klik untuk melihat):</span>
                <span class="text-slate-500">{{ galleryList.length }} Foto Tersedia</span>
            </div>
            <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin">
                <button
                    v-for="(item, idx) in galleryList"
                    :key="item.id"
                    type="button"
                    @click="activeIndex = idx"
                    :class="[
                        'shrink-0 p-1 rounded-xl border transition-all duration-200 cursor-pointer flex flex-col items-center gap-1 group',
                        activeIndex === idx
                            ? 'bg-purple-500/15 border-purple-500 shadow-md shadow-purple-500/20'
                            : 'bg-slate-900 border-slate-800 hover:border-slate-700'
                    ]"
                >
                    <img
                        :src="item.url"
                        :alt="item.label"
                        class="w-14 h-14 object-cover rounded-lg border border-slate-850"
                    />
                    <span
                        :class="[
                            'text-[9px] font-mono font-semibold max-w-[64px] truncate px-1',
                            activeIndex === idx ? 'text-purple-300 font-bold' : 'text-slate-400'
                        ]"
                    >
                        {{ item.label || `Foto ${idx + 1}` }}
                    </span>
                </button>
            </div>
        </div>

        <!-- FOOTER BADGES INFO -->
        <div class="p-4 bg-slate-950/60 border-t border-slate-800/80 flex items-center justify-between text-xs font-mono">
            <span class="text-slate-400">
                Kategori: <strong class="text-white">{{ categoryName }}</strong>
            </span>
            <span
                :class="[
                    'px-2.5 py-0.5 rounded-full font-bold',
                    totalStock > 10
                        ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                        : totalStock > 0
                        ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30'
                        : 'bg-rose-500/15 text-rose-400 border border-rose-500/30'
                ]"
            >
                {{ totalStock > 0 ? `${totalStock} pcs Ready` : 'Stok Kosong' }}
            </span>
        </div>

    </div>

    <!-- LIGHTBOX MODAL RESOLUSI PENUH -->
    <div
        v-if="isLightboxOpen && activeImage"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-8 bg-slate-950/90 backdrop-blur-xl"
        @click.self="isLightboxOpen = false"
    >
        <div class="relative bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl flex flex-col space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-sm font-bold text-white">{{ productName }}</h3>
                    <div class="text-[11px] font-mono text-slate-400">
                        {{ activeImage.label || 'Foto Resolusi Penuh' }}
                    </div>
                </div>
                <button
                    type="button"
                    @click="isLightboxOpen = false"
                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold cursor-pointer"
                >
                    ✕ Tutup
                </button>
            </div>
            <div class="max-h-[75vh] overflow-hidden flex items-center justify-center rounded-2xl bg-black/60 p-4">
                <img
                    :src="activeImage.url"
                    :alt="productName"
                    class="max-h-[70vh] w-auto object-contain rounded-xl shadow-2xl"
                />
            </div>
        </div>
    </div>
</template>
