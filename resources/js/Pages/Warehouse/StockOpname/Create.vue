<script setup>
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    racks: Array,
    categories: Array,
    variants: Array,
    filters: Object,
    isOwner: Boolean,
});

// Filter Server-side (Rak Gudang & Kategori Produk)
const selectedRack = ref(props.filters?.rack || '');
const selectedCategory = ref(props.filters?.category_id || '');

const applyServerFilter = () => {
    router.get(
        route('warehouse.opnames.create'),
        {
            rack: selectedRack.value || undefined,
            category_id: selectedCategory.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetServerFilter = () => {
    selectedRack.value = '';
    selectedCategory.value = '';
    instantSearch.value = '';
    applyServerFilter();
};

// Filter Client-side Cepat
const instantSearch = ref('');
const statusFilter = ref('all'); // 'all' | 'filled' | 'unfilled'

// Form State Management
const form = useForm({
    items: [],
});

const initFormItems = () => {
    form.items = (props.variants || []).map((v) => ({
        product_variant_id: v.id,
        name: v.product?.name || 'Produk Distro',
        category: v.product?.category?.name || 'Kategori Umum',
        rack: v.product?.rack_location || null,
        size: v.size,
        physical_stock: '',
    }));
};

initFormItems();
watch(() => props.variants, () => initFormItems());

// Filtered Items untuk Tampilan Tabel
const filteredItems = computed(() => {
    let list = form.items;

    if (statusFilter.value === 'filled') {
        list = list.filter((i) => i.physical_stock !== '' && i.physical_stock !== null);
    } else if (statusFilter.value === 'unfilled') {
        list = list.filter((i) => i.physical_stock === '' || i.physical_stock === null);
    }

    if (instantSearch.value.trim()) {
        const q = instantSearch.value.toLowerCase().trim();
        list = list.filter((i) =>
            i.name.toLowerCase().includes(q) ||
            i.size.toLowerCase().includes(q) ||
            i.category.toLowerCase().includes(q) ||
            (i.rack && i.rack.toLowerCase().includes(q))
        );
    }

    return list;
});

// Metrik Audit
const totalCount = computed(() => form.items.length);
const filledCount = computed(() => form.items.filter((i) => i.physical_stock !== '' && i.physical_stock !== null).length);
const unfilledCount = computed(() => totalCount.value - filledCount.value);
const progressPercent = computed(() => {
    if (totalCount.value === 0) return 0;
    return Math.round((filledCount.value / totalCount.value) * 100);
});

// Navigasi Keyboard Cepat (Enter / Panah Bawah / Panah Atas)
const handleKeyDown = (e, index) => {
    if (e.key === 'Enter' || e.key === 'ArrowDown') {
        e.preventDefault();
        const nextInput = document.getElementById(`physical-input-${index + 1}`);
        if (nextInput) {
            nextInput.focus();
            nextInput.select();
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        const prevInput = document.getElementById(`physical-input-${index - 1}`);
        if (prevInput) {
            prevInput.focus();
            prevInput.select();
        }
    }
};

// Kosongkan kuantiti baris
const clearRow = (item) => {
    item.physical_stock = '';
};

// Simpan Hasil Audit Hitung Fisik
const submit = () => {
    const filledItems = form.items.filter((i) => i.physical_stock !== '' && i.physical_stock !== null && !isNaN(i.physical_stock));

    if (filledItems.length === 0) {
        alert('Mohon masukkan kuantiti fisik minimal untuk 1 artikel pakaian sebelum menyimpan.');
        return;
    }

    const payload = useForm({
        items: filledItems.map((i) => ({
            product_variant_id: i.product_variant_id,
            physical_stock: parseInt(i.physical_stock, 10),
            notes: null,
        })),
    });

    payload.post(route('warehouse.opnames.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Input Hasil SO — Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-indigo-500/15 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                            Spreadsheet Terminal
                        </span>
                        <span class="text-xs text-slate-400 font-mono">Stock Opname Massal</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight mt-1">
                        Input Hasil Hitung Fisik
                    </h2>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('warehouse.opnames.index')"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700/80 text-slate-300 hover:text-white font-bold text-xs transition active:scale-95 shadow-sm"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Daftar Audit SO</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-4 pb-28">
            <!-- 1. COMPACT HUD SUMMARY & PROGRESS BAR -->
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 p-4 shadow-lg backdrop-blur-md">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider font-mono">Progress Audit Fisik</span>
                                <span v-if="filledCount > 0" class="px-2 py-0.2 rounded text-[9px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    {{ progressPercent }}%
                                </span>
                            </div>
                            <div class="text-base font-black text-white font-mono flex items-baseline gap-2">
                                <span class="text-indigo-400">{{ filledCount }}</span>
                                <span class="text-xs font-medium text-slate-400 font-sans">dari {{ totalCount }} varian terisi</span>
                            </div>
                        </div>
                    </div>

                    <!-- Keyboard Quick Guide -->
                    <div class="flex items-center gap-2 text-[11px] text-slate-400 bg-slate-950/80 px-3 py-1.5 rounded-lg border border-slate-800 self-start sm:self-auto font-mono">
                        <span class="text-indigo-400 font-bold">Tip:</span> Tekan <kbd class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-200 border border-slate-700 text-[10px]">Enter</kbd> / <kbd class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-200 border border-slate-700 text-[10px]">↓</kbd> untuk pindah baris ke bawah
                    </div>
                </div>

                <!-- Progress Meter -->
                <div class="mt-3 w-full bg-slate-950 h-2 rounded-full overflow-hidden border border-slate-800">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-sky-400 to-emerald-400 transition-all duration-300 shadow-sm shadow-emerald-500/30"
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>
            </div>

            <!-- 2. TOOLBAR FILTER & SEARCH -->
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 p-3 shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Instant Live Search Bar -->
                    <div class="relative w-full sm:w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-xs">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            v-model="instantSearch"
                            placeholder="Cari nama artikel, ukuran, rak..."
                            class="w-full pl-8 pr-7 py-1.5 text-xs rounded-lg bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                        />
                        <button
                            v-if="instantSearch"
                            @click="instantSearch = ''"
                            type="button"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-500 hover:text-white text-xs cursor-pointer"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Filter Server: Rak Gudang -->
                    <select
                        v-model="selectedRack"
                        @change="applyServerFilter"
                        class="py-1.5 px-2.5 text-xs rounded-lg bg-slate-950 border border-slate-800 text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                    >
                        <option value="">Semua Lokasi Rak</option>
                        <option v-for="r in racks" :key="r" :value="r">
                            📍 Rak {{ r }}
                        </option>
                    </select>

                    <!-- Filter Server: Kategori Produk -->
                    <select
                        v-model="selectedCategory"
                        @change="applyServerFilter"
                        class="py-1.5 px-2.5 text-xs rounded-lg bg-slate-950 border border-slate-800 text-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 cursor-pointer"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>

                    <button
                        v-if="selectedRack || selectedCategory || instantSearch"
                        type="button"
                        @click="resetServerFilter"
                        class="text-xs text-rose-400 hover:text-rose-300 font-bold px-1.5 cursor-pointer transition"
                    >
                        Reset Filter
                    </button>
                </div>

                <!-- Tab Status Pengisian -->
                <div class="flex items-center gap-1 bg-slate-950 p-1 rounded-lg border border-slate-800 self-start md:self-auto">
                    <button
                        type="button"
                        @click="statusFilter = 'all'"
                        :class="[
                            'px-2.5 py-1 rounded text-xs font-semibold transition cursor-pointer',
                            statusFilter === 'all'
                                ? 'bg-slate-800 text-white font-bold shadow-xs'
                                : 'text-slate-400 hover:text-white'
                        ]"
                    >
                        Semua ({{ totalCount }})
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'unfilled'"
                        :class="[
                            'px-2.5 py-1 rounded text-xs font-semibold transition cursor-pointer',
                            statusFilter === 'unfilled'
                                ? 'bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30'
                                : 'text-slate-400 hover:text-white'
                        ]"
                    >
                        Belum Diisi ({{ unfilledCount }})
                    </button>

                    <button
                        type="button"
                        @click="statusFilter = 'filled'"
                        :class="[
                            'px-2.5 py-1 rounded text-xs font-semibold transition cursor-pointer',
                            statusFilter === 'filled'
                                ? 'bg-emerald-500/20 text-emerald-300 font-bold border border-emerald-500/30'
                                : 'text-slate-400 hover:text-white'
                        ]"
                    >
                        ✓ Sudah ({{ filledCount }})
                    </button>
                </div>
            </div>

            <!-- 3. SPREADSHEET DATA GRID TABLE (BERSIH & PROPORSI SEMPURNA) -->
            <div class="rounded-xl bg-slate-900/90 border border-slate-800 shadow-xl overflow-hidden backdrop-blur-md">
                <div class="overflow-x-auto max-h-[620px] overflow-y-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <!-- Table Head Sticky dengan Lebar Kolom Proporsional (Tanpa Kolom SKU) -->
                        <thead class="bg-slate-950 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800 text-[10px] sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="py-2.5 px-3 w-12 text-center">No</th>
                                <th class="py-2.5 px-3 w-28 text-center">Lokasi Rak</th>
                                <th class="py-2.5 px-4 min-w-[260px]">Artikel Pakaian & Kategori</th>
                                <th class="py-2.5 px-3 w-24 text-center">Ukuran</th>
                                <th class="py-2.5 px-4 w-44 text-right">Hitung Fisik <span class="text-rose-400">*</span></th>
                                <th class="py-2.5 px-3 w-28 text-center">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            <tr
                                v-for="(item, idx) in filteredItems"
                                :key="item.product_variant_id"
                                :class="[
                                    'transition-colors duration-150',
                                    item.physical_stock !== '' && item.physical_stock !== null
                                        ? 'bg-emerald-500/5 hover:bg-emerald-500/10'
                                        : 'hover:bg-slate-800/40'
                                ]"
                            >
                                <!-- No -->
                                <td class="py-2.5 px-3 text-center text-slate-500 text-[11px]">
                                    {{ idx + 1 }}
                                </td>

                                <!-- Rak -->
                                <td class="py-2.5 px-3 text-center">
                                    <span v-if="item.rack" class="inline-block px-2.5 py-0.5 rounded text-[10px] font-bold bg-slate-800/90 text-amber-300 border border-amber-500/30">
                                        📍 {{ item.rack }}
                                    </span>
                                    <span v-else class="text-slate-600 text-[10px]">
                                        —
                                    </span>
                                </td>

                                <!-- Nama Artikel & Kategori -->
                                <td class="py-2.5 px-4 font-sans">
                                    <div class="font-bold text-white text-xs tracking-tight truncate max-w-md" :title="item.name">
                                        {{ item.name }}
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-medium mt-0.5">
                                        {{ item.category }}
                                    </div>
                                </td>

                                <!-- Ukuran -->
                                <td class="py-2.5 px-3 text-center font-sans">
                                    <span class="inline-block min-w-[32px] px-2.5 py-0.5 font-black text-xs text-white bg-slate-800 rounded-md border border-slate-700 shadow-xs">
                                        {{ item.size }}
                                    </span>
                                </td>

                                <!-- Input Hitung Fisik -->
                                <td class="py-2.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <input
                                            :id="`physical-input-${idx}`"
                                            type="number"
                                            min="0"
                                            v-model="item.physical_stock"
                                            @keydown="handleKeyDown($event, idx)"
                                            @focus="$event.target.select()"
                                            placeholder="0"
                                            class="w-24 h-8 text-right text-xs font-mono font-black rounded-lg bg-slate-950 border border-slate-700 text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 py-1 px-2.5 placeholder-slate-600 transition shadow-inner"
                                        />
                                        <span class="text-[11px] text-slate-400 font-sans w-6 text-left">pcs</span>
                                        <button
                                            v-if="item.physical_stock !== '' && item.physical_stock !== null"
                                            type="button"
                                            @click="clearRow(item)"
                                            title="Kosongkan angka"
                                            class="text-slate-500 hover:text-rose-400 text-xs p-1 transition cursor-pointer"
                                        >
                                            ✕
                                        </button>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-2.5 px-3 text-center font-sans">
                                    <span
                                        v-if="item.physical_stock !== '' && item.physical_stock !== null"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30"
                                    >
                                        ✓ Terisi
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-slate-800/60 text-slate-500 border border-slate-700/40"
                                    >
                                        Belum
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="filteredItems.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-500 font-sans text-xs">
                                    Tidak ada pakaian yang sesuai dengan pencarian atau filter yang dipilih.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. STICKY BOTTOM ACTION BAR -->
        <div class="fixed bottom-4 left-4 right-4 md:left-64 z-30">
            <div class="bg-slate-950/95 backdrop-blur-2xl border border-slate-700/80 rounded-2xl p-3.5 sm:px-6 shadow-2xl shadow-indigo-950/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <div class="text-xs text-slate-300">
                        <span class="font-black text-emerald-400 font-mono text-sm">{{ filledCount }}</span>
                        dari <span class="font-bold text-white font-mono">{{ totalCount }}</span> varian terisi
                        <span v-if="filledCount > 0" class="text-slate-400">({{ progressPercent }}%)</span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 self-end sm:self-auto">
                    <Link
                        :href="route('warehouse.opnames.index')"
                        class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white bg-slate-900 hover:bg-slate-800 rounded-xl border border-slate-800 transition cursor-pointer"
                    >
                        Batal
                    </Link>

                    <button
                        type="button"
                        @click="submit"
                        :disabled="filledCount === 0 || form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-black text-xs transition active:scale-95 cursor-pointer shadow-lg shadow-indigo-600/30 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan...' : `Simpan Hasil Hitung (${filledCount} Item)` }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
