<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    imageUrl: {
        type: String,
        default: null,
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

const isZoomed = ref(false);
const zoomX = ref(50);
const zoomY = ref(50);
const isLightboxOpen = ref(false);

const handleMouseMove = (e) => {
    if (!props.imageUrl) return;
    const rect = e.currentTarget.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    zoomX.value = Math.max(0, Math.min(100, x));
    zoomY.value = Math.max(0, Math.min(100, y));
};

const handleMouseEnter = () => {
    if (props.imageUrl) {
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
            </div>
            <div class="flex items-center gap-2">
                <button
                    v-if="imageUrl"
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
                    Ganti Foto
                </Link>
            </div>
        </div>

        <!-- MAIN IMAGE VIEWPORT DENGAN DEEP ZOOM INTERAKTIF -->
        <div
            class="relative w-full aspect-square sm:aspect-[4/4.2] overflow-hidden bg-slate-950 flex items-center justify-center cursor-crosshair select-none"
            @mousemove="handleMouseMove"
            @mouseenter="handleMouseEnter"
            @mouseleave="handleMouseLeave"
            @click="imageUrl ? (isLightboxOpen = true) : null"
        >
            <!-- FOTO PRODUK ASLI DENGAN LOUPE ZOOM -->
            <template v-if="imageUrl">
                <div class="w-full h-full flex items-center justify-center p-6 sm:p-8">
                    <img
                        :src="imageUrl"
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
                        <span>Arahkan mouse untuk zoom detail sablon & jahitan</span>
                    </span>
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
                        Unggah foto artikel pakaian untuk menampilkan showcase resolusi tinggi di sini.
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

        <!-- FOOTER BADGES INFO -->
        <div class="p-4 bg-slate-950 border-t border-slate-800/80 flex items-center justify-between text-xs font-mono">
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
        v-if="isLightboxOpen && imageUrl"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-8 bg-slate-950/90 backdrop-blur-xl"
        @click.self="isLightboxOpen = false"
    >
        <div class="relative bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full p-4 sm:p-6 shadow-2xl flex flex-col space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-sm font-bold text-white">{{ productName }}</h3>
                    <div class="text-[11px] font-mono text-slate-400">Foto Asli Resolusi Penuh</div>
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
                    :src="imageUrl"
                    :alt="productName"
                    class="max-h-[70vh] w-auto object-contain rounded-xl shadow-2xl"
                />
            </div>
        </div>
    </div>
</template>
