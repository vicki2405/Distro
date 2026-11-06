<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    racks: Array,
    categories: Array,
    previewVariants: Array,
    totalVariants: Number,
    filters: Object,
});

const selectedRack = ref(props.filters?.rack || '');
const selectedCategory = ref(props.filters?.category_id || '');

const applyFilter = () => {
    router.get(
        route('warehouse.opnames.sheet'),
        {
            rack: selectedRack.value || undefined,
            category_id: selectedCategory.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilter = () => {
    selectedRack.value = '';
    selectedCategory.value = '';
    applyFilter();
};

const downloadPdf = () => {
    const params = new URLSearchParams();
    if (selectedRack.value) params.append('rack', selectedRack.value);
    if (selectedCategory.value) params.append('category_id', selectedCategory.value);

    window.open(`${route('warehouse.opnames.sheet.download')}?${params.toString()}`, '_blank');
};
</script>

<template>
    <Head title="Cetak Berkas SO — Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span>Cetak Berkas Lembar Hitung Lapangan</span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30 uppercase">
                            Formulir Fisik
                        </span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Cetak formulir kertas A4 per rak/kategori untuk dibawa staf gudang mencatat hitungan fisik menggunakan pulpen.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('warehouse.opnames.index')"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 hover:border-slate-600 text-slate-300 hover:text-white font-semibold text-xs transition active:scale-95 shadow-sm"
                    >
                        <span>←</span>
                        <span>Hasil Rekonsiliasi</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- PANEL KONTROL TARGET & TOMBOL CETAK BESAR -->
            <div class="bg-slate-900/80 backdrop-blur-md border border-slate-800/80 rounded-2xl p-6 shadow-xl space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-end">
                    
                    <!-- Filter Rak -->
                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">
                            Pilih Lokasi Rak Gudang
                        </label>
                        <select
                            v-model="selectedRack"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl bg-slate-950 border border-slate-800 text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 py-3 px-3.5 transition"
                        >
                            <option value="">Semua Lokasi Rak Gudang</option>
                            <option v-for="rack in racks" :key="rack" :value="rack">
                                📍 Rak {{ rack }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Kategori -->
                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold text-slate-300 uppercase mb-1.5">
                            Pilih Kategori Produk
                        </label>
                        <select
                            v-model="selectedCategory"
                            @change="applyFilter"
                            class="w-full text-xs rounded-xl bg-slate-950 border border-slate-800 text-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500 py-3 px-3.5 transition"
                        >
                            <option value="">Semua Kategori Produk</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Tombol Reset & Status -->
                    <div class="md:col-span-4 flex items-center justify-between sm:justify-end gap-3">
                        <button
                            v-if="selectedRack || selectedCategory"
                            type="button"
                            @click="resetFilter"
                            class="text-xs text-rose-400 hover:underline font-bold px-2 cursor-pointer"
                        >
                            Reset Pilihan
                        </button>

                        <button
                            type="button"
                            @click="downloadPdf"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black text-xs transition active:scale-95 cursor-pointer shadow-lg shadow-amber-500/20"
                        >
                            <span>🖨️</span>
                            <span>Unduh Berkas PDF (A4)</span>
                        </button>
                    </div>
                </div>

                <!-- Info Banner Jumlah Baris yang Akan Dicetak -->
                <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center font-bold text-lg">
                            📄
                        </div>
                        <div>
                            <div class="text-xs font-bold text-white">
                                Target Cetak: {{ totalVariants }} Varian Ukuran Pakaian
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ selectedRack ? `Khusus Rak ${selectedRack}` : 'Seluruh Rak Gudang' }}
                                {{ selectedCategory ? ` • Kategori ID ${selectedCategory}` : '' }}
                            </div>
                        </div>
                    </div>

                    <div class="text-[11px] text-amber-300 font-semibold bg-amber-500/10 border border-amber-500/25 px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                        <span>🔒</span>
                        <span>Stok sistem otomatis disamarkan (Blind Count Sheet)</span>
                    </div>
                </div>
            </div>

            <!-- TABEL PRATINJAU DOKUMEN CETAK -->
            <div class="bg-slate-900/70 backdrop-blur-md border border-slate-800/80 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-4 border-b border-slate-800/80 bg-slate-900/50 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">
                        Pratinjau Lembar Kerja Fisik (Maksimal 50 baris pertama)
                    </h3>
                    <span class="text-xs font-mono text-slate-400">
                        Format Cetak: A4 Standar
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/80 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800 text-[10px]">
                            <tr>
                                <th class="p-3.5 w-14 text-center">No</th>
                                <th class="p-3.5 w-32 text-center">Lokasi Rak</th>
                                <th class="p-3.5">Nama Artikel Produk</th>
                                <th class="p-3.5 w-24 text-center">Ukuran</th>
                                <th class="p-3.5 w-48 text-center">Kolom Hitung Fisik (Kertas)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            <tr
                                v-for="(v, idx) in previewVariants"
                                :key="v.id"
                                class="hover:bg-slate-800/30 transition text-slate-300"
                            >
                                <td class="p-3.5 text-center text-slate-500">{{ idx + 1 }}</td>
                                <td class="p-3.5 text-center">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-200 border border-slate-700">
                                        📍 {{ v.product?.rack_location || 'Tanpa Rak' }}
                                    </span>
                                </td>
                                <td class="p-3.5 font-sans">
                                    <div class="font-bold text-white">{{ v.product?.name }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                        {{ v.product?.category?.name || 'Kategori Umum' }}
                                    </div>
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="px-2 py-0.5 font-black text-white bg-slate-800 rounded border border-slate-700">
                                        {{ v.size }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <div class="w-24 h-6 mx-auto rounded border border-dashed border-slate-700 bg-slate-950 flex items-center justify-center text-[10px] text-slate-600">
                                        [ . . . . . . . . ]
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="previewVariants.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-500 italic font-sans">
                                    Tidak ada varian pakaian yang sesuai dengan filter lokasi rak ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

