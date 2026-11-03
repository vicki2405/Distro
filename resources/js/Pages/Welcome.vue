<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
    stats: Object,
    canLogin: Boolean,
});

// Mode tampilan di perangkat mobile (compact: 2 kolom, spacious: 1 kolom)
const mobileViewMode = ref('compact');

// State Filter & Pencarian
const search = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const sort = ref(props.filters?.sort || 'latest');

// Custom Dropdown Urutkan (Bebas Bug White-on-White Windows Browser)
const sortOptions = [
    { value: 'latest', label: 'Terbaru', icon: '✨' },
    { value: 'price_asc', label: 'Harga Terendah', icon: '📉' },
    { value: 'price_desc', label: 'Harga Tertinggi', icon: '📈' },
    { value: 'name', label: 'Nama (A-Z)', icon: '🔤' },
];
const isSortOpen = ref(false);
const sortDropdownRef = ref(null);

const selectSort = (val) => {
    sort.value = val;
    isSortOpen.value = false;
    applyFilters();
};

const currentSortLabel = computed(() => {
    return sortOptions.find((o) => o.value === sort.value)?.label || 'Terbaru';
});

const handleClickOutside = (e) => {
    if (sortDropdownRef.value && !sortDropdownRef.value.contains(e.target)) {
        isSortOpen.value = false;
    }
};

const applyFilters = () => {
    router.get(
        route('welcome'),
        {
            search: search.value || undefined,
            category: selectedCategory.value || undefined,
            sort: sort.value !== 'latest' ? sort.value : undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true }
    );
};

const selectCategory = (catId) => {
    selectedCategory.value = selectedCategory.value === catId ? '' : catId;
    applyFilters();
};

const clearSearch = () => {
    search.value = '';
    applyFilters();
};

const resetAllFilters = () => {
    search.value = '';
    selectedCategory.value = '';
    sort.value = 'latest';
    applyFilters();
};

// Modal Detail / Quick View (Mobile Bottom Sheet & Desktop Modal)
const previewProduct = ref(null);
const activeAngleIndex = ref(0);

const openQuickView = (product) => {
    previewProduct.value = product;
    activeAngleIndex.value = 0;
};

const closeQuickView = () => {
    previewProduct.value = null;
    activeAngleIndex.value = 0;
};

// Kunci scroll halaman latar belakang saat modal aktif di mobile/desktop
watch(previewProduct, (val) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = val ? 'hidden' : '';
    }
});

// Listener tombol Escape untuk menutup modal secara instan
const handleKeyDown = (e) => {
    if (e.key === 'Escape' && previewProduct.value) {
        closeQuickView();
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('keydown', handleKeyDown);
        window.addEventListener('click', handleClickOutside);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleKeyDown);
        window.removeEventListener('click', handleClickOutside);
    }
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

// Galeri gambar produk terpilih di modal
const previewGallery = computed(() => {
    if (!previewProduct.value) return [];
    const list = [];
    if (previewProduct.value.image_url) {
        list.push({ url: previewProduct.value.image_url, label: 'Tampak Depan' });
    }
    if (previewProduct.value.images && previewProduct.value.images.length > 0) {
        previewProduct.value.images.forEach((img, idx) => {
            if (img.url !== previewProduct.value.image_url) {
                list.push({ url: img.url, label: img.label || `Sudut ${idx + 1}` });
            }
        });
    }
    return list;
});

const currentPreviewImage = computed(() => {
    if (previewGallery.value.length === 0) return null;
    return previewGallery.value[activeAngleIndex.value] || previewGallery.value[0];
});

// Format Rupiah
const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

// Hitung total stok produk dari semua varian
const getTotalStock = (product) => {
    if (!product.variants || product.variants.length === 0) return 0;
    return product.variants.reduce((sum, v) => sum + (v.current_stock || 0), 0);
};

// Status Stok Badge dengan warna neon elegan
const getStockBadge = (total) => {
    if (total <= 0) {
        return {
            label: 'Habis',
            badgeClass: 'bg-rose-500/20 text-rose-300 border-rose-500/40',
            dotClass: 'bg-rose-500 shadow-sm shadow-rose-500',
        };
    }
    if (total <= 5) {
        return {
            label: `${total} pcs`,
            badgeClass: 'bg-amber-500/25 text-amber-300 border-amber-500/50',
            dotClass: 'bg-amber-400 shadow-sm shadow-amber-400',
        };
    }
    return {
        label: `${total} pcs`,
        badgeClass: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
        dotClass: 'bg-emerald-400 shadow-sm shadow-emerald-400',
    };
};
</script>

<template>
    <Head title="Katalog Produk Distro Resmi — Apparel & Streetwear" />

    <!-- CANVAS UTAMA: Deep Slate & Cosmic Indigo Berenergi (Nyaman di Mata, Anti-Hitam Mati) -->
    <div class="min-h-screen bg-gradient-to-br from-[#0c1322] via-[#111a30] to-[#0c1426] text-slate-100 flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950 relative overflow-x-hidden">

        <!-- 1. ANIMATED AURORA MESH GLOW (BOLA CAHAYA HANGAT & ENERGIK DI LATAR) -->
        <div class="fixed top-0 left-1/4 -translate-x-1/2 w-[550px] sm:w-[700px] h-[550px] sm:h-[700px] bg-gradient-to-br from-amber-500/25 via-orange-500/15 to-transparent rounded-full blur-[130px] pointer-events-none animate-pulse"></div>
        <div class="fixed -bottom-40 right-10 w-[600px] sm:w-[750px] h-[600px] sm:h-[750px] bg-gradient-to-tl from-indigo-500/25 via-sky-500/15 to-transparent rounded-full blur-[140px] pointer-events-none"></div>

        <!-- TEKSTUR SUBTLE TECH GRID -->
        <div class="fixed inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:24px_24px] pointer-events-none opacity-80"></div>

        <!-- 2. TOP NAVBAR GLASSMORPHIC (DEEP SLATE, TOUCH-FRIENDLY & STICKY) -->
        <header class="sticky top-0 z-40 backdrop-blur-xl bg-[#0d1629]/90 border-b border-slate-700/60 shadow-lg shadow-black/25 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-3.5 sm:px-6 lg:px-8 h-14 sm:h-18 flex items-center justify-between gap-3">
                
                <!-- Brand Logo Distro dengan Glowing Effect -->
                <Link :href="route('welcome')" class="flex items-center gap-2.5 group shrink-0">
                    <span class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 flex items-center justify-center font-black text-slate-950 text-sm sm:text-lg shadow-lg shadow-amber-500/30 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300">
                        D
                    </span>
                    <div>
                        <div class="font-black tracking-tight text-white text-xs sm:text-base leading-none group-hover:text-amber-400 transition-colors">
                            DISTRO <span class="text-amber-400 font-normal">CATALOG</span>
                        </div>
                        <div class="text-[8px] sm:text-[9px] text-slate-400 font-mono tracking-wider mt-0.5 hidden sm:block">
                            APPAREL & STREETWEAR
                        </div>
                    </div>
                </Link>

                <!-- Beacon Live Inventory (Pulsing Indicator) -->
                <div class="hidden md:flex items-center gap-2 px-3.5 py-1 rounded-full bg-slate-800/80 border border-slate-700/70 text-[11px] font-mono text-slate-300 backdrop-blur-md shadow-inner">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400 shadow-sm shadow-emerald-400"></span>
                    </span>
                    <span>LIVE STOCK FEED</span>
                </div>

                <!-- Tombol Masuk / Dashboard (Warna Amber Energik) -->
                <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="route('warehouse.dashboard')"
                            class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-xl bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 font-black text-[11px] sm:text-xs transition-all duration-200 shadow-md shadow-amber-500/25 active:scale-95"
                        >
                            <span>Dashboard ({{ $page.props.auth.user.name.split(' ')[0] }})</span>
                            <span class="hidden sm:inline">➔</span>
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center gap-1.5 px-3.5 sm:px-4 py-1.5 sm:py-2 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/70 text-white font-bold text-[11px] sm:text-xs transition-all duration-200 active:scale-95 shadow-sm"
                        >
                            <span>🔑</span>
                            <span>Masuk Portal</span>
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- 3. MAIN CATALOG CONTAINER -->
        <main class="max-w-7xl w-full mx-auto px-3.5 sm:px-6 lg:px-8 py-5 sm:py-9 space-y-6 sm:space-y-8 flex-1 relative z-10">

            <!-- HERO HEADER & STATS CARDS -->
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5 pb-3 border-b border-slate-800">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 text-[10px] sm:text-xs font-mono font-semibold backdrop-blur-md">
                        <span class="animate-bounce">✨</span>
                        <span>Rilisan Resmi Katalog Baju Distro</span>
                    </div>
                    <h1 class="text-2xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white leading-tight">
                        Eksplorasi Koleksi <br class="hidden sm:inline" />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-amber-400 to-orange-400">
                            Distro & Streetwear
                        </span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 max-w-xl leading-relaxed">
                        Daftar master artikel pakaian distro dengan ketersediaan stok aktual di rak gudang kami.
                    </p>
                </div>

                <!-- 3 KARTU STATISTIK BERENERGI (FROSTED GLASS ELEVATION) -->
                <div class="grid grid-cols-3 gap-2.5 sm:gap-3.5 shrink-0">
                    <div class="bg-slate-800/60 hover:bg-slate-800/90 backdrop-blur-xl rounded-2xl border border-slate-700/60 p-2.5 sm:p-4 text-center transition-all duration-300 hover:-translate-y-1 shadow-lg shadow-black/25">
                        <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400">Artikel</div>
                        <div class="text-base sm:text-2xl font-black text-white mt-0.5 font-mono">{{ stats?.total_products || 0 }}</div>
                    </div>
                    <div class="bg-slate-800/60 hover:bg-slate-800/90 backdrop-blur-xl rounded-2xl border border-slate-700/60 p-2.5 sm:p-4 text-center transition-all duration-300 hover:-translate-y-1 shadow-lg shadow-black/25">
                        <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-amber-400">Kategori</div>
                        <div class="text-base sm:text-2xl font-black text-amber-400 mt-0.5 font-mono">{{ stats?.total_categories || 0 }}</div>
                    </div>
                    <div class="bg-slate-800/60 hover:bg-slate-800/90 backdrop-blur-xl rounded-2xl border border-slate-700/60 p-2.5 sm:p-4 text-center transition-all duration-300 hover:-translate-y-1 shadow-lg shadow-black/25">
                        <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-emerald-400">Ready Pcs</div>
                        <div class="text-base sm:text-2xl font-black text-emerald-400 mt-0.5 font-mono">{{ (stats?.total_pieces || 0).toLocaleString('id-ID') }}</div>
                    </div>
                </div>
            </div>

            <!-- CONTROLS: SEARCH, CATEGORIES & LAYOUT TOGGLE -->
            <div class="space-y-3.5">
                
                <!-- Bilah Pencarian & Sort -->
                <div class="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs">
                            🔍
                        </div>
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Cari nama artikel, kode SKU, hoodie, kaos..."
                            class="w-full text-xs sm:text-sm rounded-2xl border-slate-700/70 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-400 bg-slate-800/70 text-white py-2.5 sm:py-3 pl-10 pr-9 transition placeholder-slate-400 font-medium backdrop-blur-md shadow-inner"
                        />
                        <button
                            v-if="search"
                            type="button"
                            @click="clearSearch"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white text-xs cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Filter Actions: Button Cari, Sort Dropdown & Mobile Grid Switcher -->
                    <div class="flex items-center gap-2 justify-between sm:justify-end">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="px-4 py-2.5 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs transition cursor-pointer active:scale-95 shadow-md shadow-amber-500/25"
                        >
                            Cari
                        </button>

                        <!-- CUSTOM SORT DROPDOWN (KEBAL BUG WHITE-ON-WHITE BROWSER, ELEGAN & KONTRAST TINGGI) -->
                        <div class="relative shrink-0" ref="sortDropdownRef">
                            <button
                                type="button"
                                @click="isSortOpen = !isSortOpen"
                                class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/80 hover:border-amber-400/50 text-slate-200 text-xs font-semibold transition cursor-pointer shadow-sm active:scale-95 backdrop-blur-md"
                                aria-haspopup="listbox"
                                :aria-expanded="isSortOpen"
                            >
                                <span class="text-amber-400 font-bold">⚡</span>
                                <span>{{ currentSortLabel }}</span>
                                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180 text-amber-400': isSortOpen }" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <!-- Menu Popover Dropdown (Slate Gelap Berteks Tajam) -->
                            <Transition
                                enter-active-class="transition duration-150 ease-out transform"
                                enter-from-class="scale-95 opacity-0 -translate-y-1"
                                enter-to-class="scale-100 opacity-100 translate-y-0"
                                leave-active-class="transition duration-100 ease-in transform"
                                leave-from-class="scale-100 opacity-100 translate-y-0"
                                leave-to-class="scale-95 opacity-0 -translate-y-1"
                            >
                                <div
                                    v-if="isSortOpen"
                                    class="absolute right-0 mt-2 w-48 rounded-2xl bg-[#141d33] border border-slate-700 shadow-2xl shadow-black/80 py-1.5 z-50 backdrop-blur-2xl"
                                >
                                    <button
                                        v-for="opt in sortOptions"
                                        :key="opt.value"
                                        type="button"
                                        @click="selectSort(opt.value)"
                                        class="w-full px-3.5 py-2.5 text-left text-xs flex items-center justify-between transition cursor-pointer"
                                        :class="sort === opt.value ? 'bg-amber-500/20 text-amber-300 font-bold' : 'text-slate-200 hover:bg-slate-800/80 hover:text-white'"
                                    >
                                        <span class="flex items-center gap-2">
                                            <span>{{ opt.icon }}</span>
                                            <span>{{ opt.label }}</span>
                                        </span>
                                        <span v-if="sort === opt.value" class="text-amber-400 font-bold">✓</span>
                                    </button>
                                </div>
                            </Transition>
                        </div>

                        <!-- Mobile Grid Switcher (2-Kolom vs 1-Kolom) -->
                        <div class="flex sm:hidden items-center bg-slate-800/80 border border-slate-700/70 rounded-2xl p-1 backdrop-blur-md">
                            <button
                                type="button"
                                @click="mobileViewMode = 'compact'"
                                :class="[
                                    'px-2 py-1.5 rounded-xl text-xs transition cursor-pointer',
                                    mobileViewMode === 'compact' ? 'bg-amber-500 text-slate-950 font-black shadow-sm' : 'text-slate-400 hover:text-white'
                                ]"
                                title="Mode 2 Kolom Ringkas"
                            >
                                ☷
                            </button>
                            <button
                                @click="mobileViewMode = 'spacious'"
                                :class="[
                                    'px-2 py-1.5 rounded-xl text-xs transition cursor-pointer',
                                    mobileViewMode === 'spacious' ? 'bg-amber-500 text-slate-950 font-black shadow-sm' : 'text-slate-400 hover:text-white'
                                ]"
                                title="Mode 1 Kolom Besar"
                            >
                                ▢
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Horizontal Category Filter Chips (Smooth Touch Scroll) -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1.5 scrollbar-none -mx-3.5 px-3.5 sm:mx-0 sm:px-0 touch-pan-x">
                    <button
                        type="button"
                        @click="selectCategory('')"
                        :class="[
                            'px-4 py-2 rounded-2xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0 border backdrop-blur-md',
                            selectedCategory === ''
                                ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 border-amber-400 font-black shadow-md shadow-amber-500/30 scale-[1.02]'
                                : 'bg-slate-850/80 hover:bg-slate-800 text-slate-300 hover:text-white border-slate-700/70'
                        ]"
                    >
                        Semua Kategori ({{ stats?.total_products || 0 }})
                    </button>

                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        @click="selectCategory(cat.id)"
                        :class="[
                            'px-4 py-2 rounded-2xl text-xs font-semibold transition whitespace-nowrap cursor-pointer shrink-0 border flex items-center gap-1.5 backdrop-blur-md',
                            selectedCategory === cat.id
                                ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 border-amber-400 font-black shadow-md shadow-amber-500/30 scale-[1.02]'
                                : 'bg-slate-850/80 hover:bg-slate-800 text-slate-300 hover:text-white border-slate-700/70'
                        ]"
                    >
                        <span>{{ cat.name }}</span>
                        <span class="text-[10px] opacity-75 font-mono">({{ cat.products_count || 0 }})</span>
                    </button>
                </div>
            </div>

            <!-- 4. PRODUCT GRID (FROSTED GLASS CARDS WITH HOVER LIFT & GLOW) -->
            <div v-if="products.data && products.data.length > 0">
                <div
                    :class="[
                        'grid gap-3 sm:gap-5 md:gap-6',
                        mobileViewMode === 'compact'
                            ? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4'
                            : 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4'
                    ]"
                >
                    <div
                        v-for="product in products.data"
                        :key="product.id"
                        class="group bg-slate-900/70 hover:bg-slate-850/90 backdrop-blur-xl rounded-2xl sm:rounded-3xl border border-slate-700/60 hover:border-amber-400/60 hover:shadow-2xl hover:shadow-amber-500/15 transition-all duration-300 flex flex-col justify-between overflow-hidden hover:-translate-y-1.5 active:scale-[0.99]"
                    >
                        <!-- FOTO PRODUK DENGAN ASPECT RATIO 1:1 SQUARE (SEIMBANG & TIDAK OVERSIZE) -->
                        <div>
                            <div
                                class="relative w-full aspect-square bg-gradient-to-b from-slate-800/80 via-slate-850/90 to-slate-900 overflow-hidden flex items-center justify-center cursor-pointer border-b border-slate-700/50"
                                @click="openQuickView(product)"
                            >
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-[1.07] transition-transform duration-500 ease-out"
                                    loading="lazy"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-slate-400 gap-1.5 p-3 text-center">
                                    <span class="text-4xl sm:text-5xl drop-shadow-md group-hover:scale-110 transition-transform duration-300">👕</span>
                                    <span class="text-[9px] font-mono tracking-widest uppercase text-slate-400 font-semibold">Preview Master</span>
                                </div>

                                <!-- Floating Glass Badges di Foto -->
                                <div class="absolute top-2 inset-x-2 flex items-center justify-between gap-1 pointer-events-none">
                                    <span class="px-2 py-0.5 rounded-lg bg-slate-900/80 backdrop-blur-md text-[9px] sm:text-[10px] font-bold text-slate-200 border border-slate-700/70 truncate max-w-[65%] shadow-sm">
                                        {{ product.category?.name || 'Distro' }}
                                    </span>

                                    <!-- Stock Pill -->
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-lg text-[9px] sm:text-[10px] font-bold border backdrop-blur-md flex items-center gap-1 shadow-sm shrink-0',
                                            getStockBadge(getTotalStock(product)).badgeClass
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStockBadge(getTotalStock(product)).dotClass"></span>
                                        <span>{{ getStockBadge(getTotalStock(product)).label }}</span>
                                    </span>
                                </div>

                                <!-- Multi-photo tag -->
                                <div
                                    v-if="product.images && product.images.length > 1"
                                    class="absolute bottom-2 right-2 px-2 py-0.5 rounded-md bg-slate-900/85 backdrop-blur-md text-[9px] font-mono text-slate-300 border border-slate-700/70"
                                >
                                    📸 {{ product.images.length }} Sudut
                                </div>
                            </div>

                            <!-- INFORMASI PRODUK -->
                            <div class="p-3 sm:p-4 space-y-2 sm:space-y-2.5">
                                <div>
                                    <div class="text-[9px] sm:text-[11px] font-mono text-amber-400 font-bold uppercase truncate">
                                        {{ product.code }}
                                    </div>
                                    <h3
                                        @click="openQuickView(product)"
                                        class="text-xs sm:text-sm font-bold text-white group-hover:text-amber-300 transition-colors line-clamp-1 cursor-pointer mt-0.5"
                                        :title="product.name"
                                    >
                                        {{ product.name }}
                                    </h3>
                                </div>

                                <!-- Harga Retail Resmi (Tegas & Kontras Tinggi) -->
                                <div class="text-sm sm:text-base font-black text-amber-300 font-mono tracking-tight">
                                    {{ formatRupiah(product.selling_price) }}
                                </div>

                                <!-- Pilihan Varian Ukuran -->
                                <div class="pt-1.5 border-t border-slate-800">
                                    <div class="flex flex-wrap gap-1 items-center">
                                        <template v-if="product.variants && product.variants.length > 0">
                                            <span
                                                v-for="v in product.variants"
                                                :key="v.id"
                                                :class="[
                                                    'px-1.5 py-0.5 rounded-md text-[9px] sm:text-[10px] font-mono font-bold border transition-colors',
                                                    v.current_stock > 0
                                                        ? 'bg-slate-800/90 hover:bg-amber-500/20 text-slate-200 border-slate-700/80'
                                                        : 'bg-slate-900/60 text-slate-600 border-transparent line-through'
                                                ]"
                                            >
                                                {{ v.size }}
                                            </span>
                                        </template>
                                        <span v-else class="text-[9px] text-slate-500 italic">
                                            -
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOMBOL DETAIL AMBER BERENERGI -->
                        <div class="p-3 sm:p-4 pt-0">
                            <button
                                type="button"
                                @click="openQuickView(product)"
                                class="w-full py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 font-black text-[11px] sm:text-xs transition-all duration-200 cursor-pointer flex items-center justify-center gap-1.5 shadow-md shadow-amber-500/25 active:scale-95 group-hover:shadow-amber-500/40"
                            >
                                <span>👁️</span>
                                <span>Lihat Detail</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 5. PAGINASI RESMI -->
                <div
                    v-if="products.links && products.links.length > 3"
                    class="pt-6 sm:pt-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 border-t border-slate-800"
                >
                    <div class="text-[11px] sm:text-xs text-center sm:text-left">
                        Menampilkan {{ products.from || 0 }} - {{ products.to || 0 }} dari {{ products.total }} produk
                    </div>
                    <div class="flex items-center gap-1 flex-wrap justify-center">
                        <template v-for="(link, i) in products.links" :key="i">
                            <button
                                v-if="link.url"
                                type="button"
                                @click="router.get(link.url, {}, { preserveState: true, preserveScroll: true })"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer',
                                    link.active
                                        ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-slate-950 font-black shadow-md shadow-amber-500/25'
                                        : 'bg-slate-800/70 hover:bg-slate-700/80 text-slate-300 border border-slate-700/70'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-2.5 py-1 text-slate-600 cursor-not-allowed text-xs"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div
                v-else
                class="py-14 sm:py-18 text-center space-y-3.5 bg-slate-900/50 backdrop-blur-xl rounded-3xl border border-slate-700/60 p-8 shadow-xl"
            >
                <div class="text-5xl animate-bounce">👕</div>
                <div class="space-y-1">
                    <h3 class="text-base sm:text-lg font-bold text-white">Tidak ada artikel yang cocok</h3>
                    <p class="text-xs text-slate-400 max-w-xs mx-auto">
                        Coba kata kunci lain atau bersihkan filter pencarian.
                    </p>
                </div>
                <div>
                    <button
                        type="button"
                        @click="resetAllFilters"
                        class="px-5 py-2.5 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs transition cursor-pointer shadow-md shadow-amber-500/20 active:scale-95"
                    >
                        Reset Semua Filter
                    </button>
                </div>
            </div>

        </main>

        <!-- =================== QUICK VIEW MODAL / MOBILE BOTTOM-SHEET =================== -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="previewProduct"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/85 backdrop-blur-md overflow-hidden"
                @click.self="closeQuickView"
                role="dialog"
                aria-modal="true"
                :aria-label="previewProduct.name"
            >
                <!-- Container: Bottom-Sheet di HP, Centered Modal di Desktop -->
                <Transition
                    enter-active-class="transition duration-300 ease-out transform"
                    enter-from-class="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                    enter-to-class="translate-y-0 sm:scale-100 opacity-100"
                    leave-active-class="transition duration-200 ease-in transform"
                    leave-from-class="translate-y-0 sm:scale-100 opacity-100"
                    leave-to-class="translate-y-full sm:translate-y-0 sm:scale-95 opacity-0"
                    appear
                >
                    <div class="bg-[#0f172a] border-t sm:border border-slate-700/80 rounded-t-3xl sm:rounded-3xl w-full sm:max-w-2xl max-h-[90vh] flex flex-col md:flex-row overflow-hidden shadow-2xl relative">
                        
                        <!-- Drag Handle Bar (Khusus Mobile) -->
                        <div class="w-12 h-1 bg-slate-600 rounded-full mx-auto my-2.5 sm:hidden shrink-0"></div>

                        <!-- Bagian Kiri / Atas: Foto Produk & Sudut -->
                        <div class="w-full md:w-1/2 bg-gradient-to-b from-slate-900 to-[#0b101e] p-4 sm:p-5 flex flex-col justify-between border-b md:border-b-0 md:border-r border-slate-700/70 shrink-0">
                            <div class="relative w-full aspect-video sm:aspect-square bg-slate-800/60 rounded-2xl overflow-hidden flex items-center justify-center border border-slate-700/60">
                                <img
                                    v-if="currentPreviewImage"
                                    :src="currentPreviewImage.url"
                                    :alt="previewProduct.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="text-slate-400 text-5xl">👕</div>

                                <!-- Tag Sudut Aktif -->
                                <span
                                    v-if="currentPreviewImage"
                                    class="absolute top-2.5 left-2.5 px-2.5 py-1 rounded-lg bg-slate-900/90 backdrop-blur-md text-[10px] font-mono text-amber-400 border border-slate-700/70 shadow-sm"
                                >
                                    {{ currentPreviewImage.label }}
                                </span>
                            </div>

                            <!-- Thumbnail Selector Multi-Sudut -->
                            <div
                                v-if="previewGallery.length > 1"
                                class="flex items-center gap-2 pt-3 overflow-x-auto scrollbar-none"
                            >
                                <button
                                    v-for="(img, idx) in previewGallery"
                                    :key="idx"
                                    type="button"
                                    @click="activeAngleIndex = idx"
                                    :class="[
                                        'w-12 h-12 rounded-xl overflow-hidden border-2 shrink-0 transition cursor-pointer',
                                        activeAngleIndex === idx ? 'border-amber-400 scale-105' : 'border-slate-700 opacity-60 hover:opacity-100'
                                    ]"
                                    :aria-label="`Pilih sudut ${idx + 1}`"
                                >
                                    <img :src="img.url" class="w-full h-full object-cover" />
                                </button>
                            </div>
                        </div>

                        <!-- Bagian Kanan / Bawah: Detail & Stok -->
                        <div class="w-full md:w-1/2 p-4 sm:p-6 space-y-3.5 overflow-y-auto flex-1 flex flex-col justify-between">
                            <div class="space-y-3.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-[10px] font-bold text-slate-300 border border-slate-700">
                                            {{ previewProduct.category?.name || 'Distro' }}
                                        </span>
                                        <div class="text-[11px] font-mono text-amber-400 font-bold mt-1">
                                            SKU: {{ previewProduct.code }}
                                        </div>
                                        <h2 class="text-base sm:text-xl font-black text-white mt-0.5 leading-tight">
                                            {{ previewProduct.name }}
                                        </h2>
                                    </div>
                                    <!-- Tombol Tutup dengan Touch Target Nyaman (36px x 36px) -->
                                    <button
                                        type="button"
                                        @click="closeQuickView"
                                        class="w-9 h-9 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/80 active:scale-95 text-slate-300 hover:text-white flex items-center justify-center transition cursor-pointer text-sm shrink-0"
                                        aria-label="Tutup Detail Produk"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- Harga Resmi -->
                                <div class="p-3 rounded-2xl bg-slate-800/60 border border-slate-700/70 flex items-center justify-between">
                                    <span class="text-xs font-semibold text-slate-400">Harga Retail</span>
                                    <span class="text-base sm:text-xl font-black text-amber-400 font-mono">
                                        {{ formatRupiah(previewProduct.selling_price) }}
                                    </span>
                                </div>

                                <!-- Deskripsi -->
                                <div class="space-y-1">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        Deskripsi & Spesifikasi
                                    </div>
                                    <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line max-h-24 overflow-y-auto pr-1">
                                        {{ previewProduct.description || 'Tidak ada deskripsi detail untuk artikel ini.' }}
                                    </p>
                                </div>

                                <!-- Ketersediaan Stok per Ukuran -->
                                <div class="space-y-1.5 pt-2 border-t border-slate-800">
                                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                        Stok per Ukuran di Rak Gudang:
                                    </div>
                                    <div class="divide-y divide-slate-800 rounded-xl bg-slate-900/80 border border-slate-700/70 overflow-hidden text-xs">
                                        <div
                                            v-for="v in previewProduct.variants"
                                            :key="v.id"
                                            class="p-2.5 flex items-center justify-between"
                                        >
                                            <div class="flex items-center gap-2">
                                                <span class="w-6 h-6 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center font-bold font-mono text-white text-[11px]">
                                                    {{ v.size }}
                                                </span>
                                                <span class="text-slate-400 font-mono text-[10px]">{{ v.sku }}</span>
                                            </div>
                                            <div class="font-bold text-xs font-mono">
                                                <span v-if="v.current_stock > 0" class="text-emerald-400">
                                                    {{ v.current_stock }} pcs
                                                </span>
                                                <span v-else class="text-rose-400">
                                                    Habis
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Modal Action -->
                            <div class="pt-3.5 border-t border-slate-800 flex items-center justify-between gap-2">
                                <div class="text-[11px] sm:text-xs text-slate-400">
                                    Total: <strong class="text-white">{{ getTotalStock(previewProduct) }} pcs</strong>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Link
                                        v-if="$page.props.auth.user"
                                        :href="route('warehouse.products.edit', previewProduct.id)"
                                        class="px-3.5 py-2 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 border border-purple-500/40 text-[11px] font-bold transition active:scale-95"
                                    >
                                        ✏️ Edit
                                    </Link>
                                    <button
                                        type="button"
                                        @click="closeQuickView"
                                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-bold transition cursor-pointer active:scale-95 border border-slate-700"
                                    >
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>

        <!-- 6. FOOTER RESMI -->
        <footer class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-[11px] text-slate-400 border-t border-white/10 relative z-10">
            © 2026 Distro Operations • Official Apparel Catalog & Real-Time Logistics Feed
        </footer>

    </div>
</template>

<style scoped>
/* Utility untuk menyembunyikan scrollbar native pada chip horizontal filter */
.scrollbar-none::-webkit-scrollbar {
    display: none;
}
.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
