<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    opnames: Object,
    racks: Array,
    stats: Object,
    filters: Object,
    isOwner: Boolean,
});

// State Filter Rekapitulasi
const search = ref(props.filters?.search || '');
const selectedRack = ref(props.filters?.rack || '');
const selectedStatus = ref(props.filters?.status || '');

const applyFilters = () => {
    router.get(
        route('warehouse.opnames.index'),
        {
            search: search.value || undefined,
            rack: selectedRack.value || undefined,
            status: selectedStatus.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    selectedRack.value = '';
    selectedStatus.value = '';
    applyFilters();
};

// Modal Ekspor Berita Acara Rekonsiliasi (BAST)
const isExportModalOpen = ref(false);

const exportReport = (format) => {
    const params = new URLSearchParams();
    params.append('format', format);
    if (selectedRack.value) params.append('rack', selectedRack.value);
    if (selectedStatus.value) params.append('status', selectedStatus.value);

    window.open(`${route('warehouse.opnames.export')}?${params.toString()}`, '_blank');
    isExportModalOpen.value = false;
};

// Konfirmasi Sinkronisasi Stok Sistem (Owner Only)
const adjustingOpname = ref(null);
const isAdjustModalOpen = ref(false);

const openAdjustModal = (opname) => {
    adjustingOpname.value = opname;
    isAdjustModalOpen.value = true;
};

const closeAdjustModal = () => {
    adjustingOpname.value = null;
    isAdjustModalOpen.value = false;
};

const confirmAdjust = () => {
    if (!adjustingOpname.value) return;
    router.post(
        route('warehouse.opnames.adjust', adjustingOpname.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => closeAdjustModal(),
        }
    );
};

const deleteOpname = (opname) => {
    const itemName = opname.variant?.product?.name || 'Artikel';
    const itemSize = opname.variant?.size ? `(Ukuran ${opname.variant.size})` : '';
    if (confirm(`Hapus catatan audit untuk ${itemName} ${itemSize}?`)) {
        router.delete(route('warehouse.opnames.destroy', opname.id), {
            preserveScroll: true,
        });
    }
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};
</script>

<template>
    <Head title="Hasil & Rekonsiliasi Stock Opname — Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span>Hasil & Rekonsiliasi Stock Opname</span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-sky-500/15 text-sky-400 border border-sky-500/30 uppercase">
                            Pencocokan Sistem
                        </span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Sistem secara otomatis mencocokkan hasil hitungan fisik dari berkas lapangan dengan stok sistem dan menghitung selisih ketersediaan.
                    </p>
                </div>

                <!-- Tombol Aksi Cepat ke Alur SO Lainnya -->
                <div class="flex items-center gap-2.5">
                    <Link
                        :href="route('warehouse.opnames.sheet')"
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700 font-bold text-xs transition active:scale-95 shadow-sm"
                    >
                        <span>🖨️</span>
                        <span>Cetak Berkas SO</span>
                    </Link>

                    <Link
                        :href="route('warehouse.opnames.create')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-500 hover:bg-indigo-400 text-white font-black text-xs transition active:scale-95 shadow-lg shadow-indigo-500/20"
                    >
                        <span>✍️</span>
                        <span>Input Hasil SO</span>
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- INDIKATOR RINGKASAN REKONSILIASI (KPI CARDS DARK GLASSMORPHIC) -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-4 shadow-xl flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold tracking-wider uppercase text-slate-400">Total Audit</div>
                        <div class="text-2xl font-black text-white mt-1 font-mono">
                            {{ stats?.total_audits ?? 0 }}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Pemeriksaan fisik</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-slate-800/80 border border-slate-700 flex items-center justify-center text-slate-300 text-lg">
                        📋
                    </div>
                </div>

                <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-4 shadow-xl flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold tracking-wider uppercase text-emerald-400">Stok Klop / Sesuai</div>
                        <div class="text-2xl font-black text-emerald-400 mt-1 font-mono">
                            {{ stats?.matched_count ?? 0 }}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Selisih nol (Akurat)</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-400 text-lg">
                        ✅
                    </div>
                </div>

                <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-4 shadow-xl flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold tracking-wider uppercase text-rose-400">Selisih Minus</div>
                        <div class="text-2xl font-black text-rose-400 mt-1 font-mono">
                            {{ stats?.minus_count ?? 0 }}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Fisik lebih sedikit</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/25 flex items-center justify-center text-rose-400 text-lg">
                        📉
                    </div>
                </div>

                <div class="bg-slate-900/80 backdrop-blur-md rounded-2xl border border-slate-800/80 p-4 shadow-xl flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-semibold tracking-wider uppercase text-sky-400">Selisih Plus</div>
                        <div class="text-2xl font-black text-sky-400 mt-1 font-mono">
                            {{ stats?.plus_count ?? 0 }}
                        </div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Fisik lebih banyak</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-500/25 flex items-center justify-center text-sky-400 text-lg">
                        📈
                    </div>
                </div>

                <!-- Valuasi Selisih HPP (Khusus Owner) -->
                <div v-if="isOwner" class="col-span-2 sm:col-span-2 lg:col-span-1 bg-gradient-to-br from-amber-500/15 via-slate-900/90 to-slate-900 border border-amber-500/30 rounded-2xl p-4 shadow-xl flex items-center justify-between">
                    <div class="truncate pr-2">
                        <div class="text-[11px] font-semibold tracking-wider uppercase text-amber-300">Valuasi Selisih HPP</div>
                        <div :class="[
                            'text-lg font-black mt-1 font-mono truncate',
                            (stats?.total_discrepancy_value ?? 0) < 0 ? 'text-rose-400' : ((stats?.total_discrepancy_value ?? 0) > 0 ? 'text-emerald-400' : 'text-amber-200')
                        ]">
                            {{ formatRupiah(stats?.total_discrepancy_value ?? 0) }}
                        </div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Khusus akun Owner</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg shrink-0">
                        💰
                    </div>
                </div>
            </div>

            <!-- Toolbar Filter & Cetak Berita Acara (BAST) -->
            <div class="bg-slate-900/70 backdrop-blur-md border border-slate-800/80 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-4 border-b border-slate-800/80 bg-slate-900/50 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 text-xs">
                                🔍
                            </span>
                            <input
                                type="text"
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Cari artikel, lokasi rak..."
                                class="pl-8 pr-3 py-2 text-xs rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 w-52 sm:w-60"
                            />
                        </div>

                        <!-- Filter Rak -->
                        <select
                            v-model="selectedRack"
                            @change="applyFilters"
                            class="py-2 px-3 text-xs rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                        >
                            <option value="">Semua Lokasi Rak</option>
                            <option v-for="rack in racks" :key="rack" :value="rack">
                                📍 Rak {{ rack }}
                            </option>
                        </select>

                        <!-- Filter Status -->
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="py-2 px-3 text-xs rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="matched">Stok Klop (Cocok)</option>
                            <option value="minus">Selisih Minus (Kurang)</option>
                            <option value="plus">Selisih Plus (Lebih)</option>
                        </select>

                        <button
                            type="button"
                            @click="applyFilters"
                            class="px-3.5 py-2 text-xs font-bold bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 rounded-xl transition cursor-pointer"
                        >
                            Filter
                        </button>

                        <button
                            v-if="search || selectedRack || selectedStatus"
                            type="button"
                            @click="resetFilters"
                            class="text-xs text-rose-400 hover:underline font-bold px-2 cursor-pointer"
                        >
                            Reset
                        </button>
                    </div>

                    <!-- Tombol Cetak Dokumen BAST -->
                    <div>
                        <button
                            type="button"
                            @click="isExportModalOpen = true"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700 font-bold text-xs transition active:scale-95 cursor-pointer shadow-sm"
                        >
                            <span>📄</span>
                            <span>Unduh Berita Acara Rekonsiliasi (BAST)</span>
                        </button>
                    </div>
                </div>

                <!-- Tabel Komparasi Hasil Pencocokan -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/80 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800 text-[10px]">
                            <tr>
                                <th class="p-3.5">Waktu Entry</th>
                                <th class="p-3.5">Nama Artikel & Lokasi Rak</th>
                                <th class="p-3.5 text-center">Ukuran</th>
                                <th class="p-3.5 text-right">Fisik Lapangan</th>
                                <th class="p-3.5 text-right">Stok Sistem</th>
                                <th class="p-3.5 text-right">Selisih</th>
                                <th class="p-3.5">Petugas Input</th>
                                <th class="p-3.5">Catatan</th>
                                <th v-if="isOwner" class="p-3.5 text-center">Aksi Owner</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr
                                v-for="op in opnames.data"
                                :key="op.id"
                                class="hover:bg-slate-800/40 transition"
                            >
                                <td class="p-3.5 font-mono text-slate-400 whitespace-nowrap">
                                    {{ new Date(op.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) }}
                                </td>
                                <td class="p-3.5">
                                    <div class="font-bold text-white">
                                        {{ op.variant?.product?.name || 'Produk Dihapus' }}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span v-if="op.variant?.product?.rack_location" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700 font-mono">
                                            📍 Rak {{ op.variant?.product?.rack_location }}
                                        </span>
                                        <span class="text-[11px] text-slate-500 font-mono">
                                            {{ op.variant?.product?.category?.name || '-' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="px-2 py-0.5 font-mono font-black text-white bg-slate-800 rounded border border-slate-700">
                                        {{ op.variant?.size || '-' }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-right font-mono font-bold text-white">
                                    {{ op.physical_stock }} pcs
                                </td>
                                <td class="p-3.5 text-right font-mono">
                                    <span v-if="isOwner" class="text-slate-300 font-medium">
                                        {{ op.system_stock }} pcs
                                    </span>
                                    <span v-else class="text-slate-500 italic text-[11px]">
                                        🔒 Blind Count
                                    </span>
                                </td>
                                <td class="p-3.5 text-right font-mono font-bold">
                                    <template v-if="isOwner">
                                        <span v-if="op.difference === 0" class="text-emerald-300 bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 rounded">
                                            Klop (0)
                                        </span>
                                        <span v-else-if="op.difference < 0" class="text-rose-300 bg-rose-500/15 border border-rose-500/30 px-2 py-0.5 rounded">
                                            {{ op.difference }} pcs
                                        </span>
                                        <span v-else class="text-sky-300 bg-sky-500/15 border border-sky-500/30 px-2 py-0.5 rounded">
                                            +{{ op.difference }} pcs
                                        </span>
                                    </template>
                                    <template v-else>
                                        <span class="text-slate-500 italic text-[11px]">
                                            🔒 Tersembunyi
                                        </span>
                                    </template>
                                </td>
                                <td class="p-3.5 text-slate-300">
                                    {{ op.user?.name || '-' }}
                                </td>
                                <td class="p-3.5 text-slate-400 max-w-xs truncate">
                                    {{ op.notes || '-' }}
                                </td>
                                <td v-if="isOwner" class="p-3.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            v-if="op.difference !== 0 && !op.is_adjusted"
                                            type="button"
                                            @click="openAdjustModal(op)"
                                            title="Sinkronkan Stok Sistem dengan Hasil Fisik"
                                            class="px-2.5 py-1 text-[11px] font-black text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-lg transition shadow-md shadow-emerald-500/20 cursor-pointer active:scale-95"
                                        >
                                            ⚡ Sinkronkan
                                        </button>
                                        <span
                                            v-else-if="op.is_adjusted"
                                            class="px-2 py-0.5 text-[10px] font-bold text-emerald-300 bg-emerald-500/15 border border-emerald-500/30 rounded"
                                        >
                                            ✓ Disinkronkan
                                        </span>
                                        <button
                                            type="button"
                                            @click="deleteOpname(op)"
                                            title="Hapus Catatan Audit"
                                            class="p-1 text-slate-500 hover:text-rose-400 transition cursor-pointer"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="opnames.data.length === 0">
                                <td :colspan="isOwner ? 9 : 8" class="p-8 text-center text-slate-500 italic">
                                    Belum ada data pencocokan stock opname. Silakan cetak lembar kerja fisik dan input hasil auditnya.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="opnames.links && opnames.links.length > 3" class="p-4 border-t border-slate-800 flex items-center justify-between">
                    <div class="text-xs text-slate-400">
                        Menampilkan {{ opnames.from || 0 }} - {{ opnames.to || 0 }} dari {{ opnames.total }} catatan pencocokan
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in opnames.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                v-html="link.label"
                                :class="[
                                    'px-2.5 py-1 text-xs rounded-lg transition font-medium cursor-pointer',
                                    link.active
                                        ? 'bg-sky-500 text-slate-950 font-bold'
                                        : 'bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700'
                                ]"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-2.5 py-1 text-xs text-slate-600"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL KONFIRMASI SINKRONISASI STOK SISTEM (OWNER ONLY) -->
        <div v-if="isAdjustModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
            <div class="bg-slate-900 border border-slate-800 w-full max-w-md rounded-2xl p-6 shadow-2xl space-y-4 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 flex items-center justify-center text-lg font-bold">
                        ⚡
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-white">Sinkronkan Stok Sistem</h4>
                        <p class="text-xs text-slate-400">Pembaruan data inventaris aktual ke database</p>
                    </div>
                </div>

                <div class="p-3.5 bg-slate-950/80 border border-slate-800 rounded-xl space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Nama Artikel:</span>
                        <span class="font-bold text-white">{{ adjustingOpname?.variant?.product?.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Ukuran:</span>
                        <span class="font-mono font-bold text-white">{{ adjustingOpname?.variant?.size }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Stok Sistem Saat Ini:</span>
                        <span class="font-mono text-slate-300">{{ adjustingOpname?.system_stock }} pcs</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-800 pt-1.5">
                        <span class="text-slate-300 font-bold">Stok Fisik Baru (Hasil SO):</span>
                        <span class="font-mono font-black text-emerald-400">{{ adjustingOpname?.physical_stock }} pcs</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Penyesuaian (Selisih):</span>
                        <span class="font-mono font-bold" :class="adjustingOpname?.difference < 0 ? 'text-rose-400' : 'text-sky-400'">
                            {{ adjustingOpname?.difference > 0 ? '+'+adjustingOpname?.difference : adjustingOpname?.difference }} pcs
                        </span>
                    </div>
                </div>

                <p class="text-xs text-slate-400 leading-relaxed">
                    Stok sistem untuk varian ukuran ini akan langsung diubah menjadi <strong>{{ adjustingOpname?.physical_stock }} pcs</strong> dan mutasi penyesuaian (Adjustment) akan dicatat resmi ke sistem.
                </p>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        type="button"
                        @click="closeAdjustModal"
                        class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmAdjust"
                        class="px-4 py-2 text-xs font-black text-slate-950 bg-emerald-400 hover:bg-emerald-300 rounded-xl shadow-lg shadow-emerald-500/20 transition cursor-pointer"
                    >
                        Ya, Sinkronkan Stok
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL UNDUH BERITA ACARA REKONSILIASI (BAST) -->
        <div v-if="isExportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
            <div class="bg-slate-900 border border-slate-800 w-full max-w-sm rounded-2xl p-6 shadow-2xl space-y-4 text-white">
                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                    <h4 class="text-sm font-bold text-white">Unduh Berita Acara (BAST)</h4>
                    <button type="button" @click="isExportModalOpen = false" class="text-slate-400 hover:text-white text-sm cursor-pointer">
                        ✕
                    </button>
                </div>

                <p class="text-xs text-slate-400">
                    Pilih format dokumen resmi Berita Acara Rekonsiliasi Hasil Opname untuk arsip fisik atau analisis excel:
                </p>

                <div class="space-y-2 pt-1">
                    <button
                        type="button"
                        @click="exportReport('pdf')"
                        class="w-full flex items-center justify-between p-3 rounded-xl border border-rose-500/30 bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 transition text-xs font-bold cursor-pointer"
                    >
                        <span class="flex items-center gap-2">
                            <span>📄</span>
                            <span>Unduh PDF Berita Acara Resmi (A4)</span>
                        </span>
                        <span>&rarr;</span>
                    </button>

                    <button
                        type="button"
                        @click="exportReport('excel')"
                        class="w-full flex items-center justify-between p-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 transition text-xs font-bold cursor-pointer"
                    >
                        <span class="flex items-center gap-2">
                            <span>📊</span>
                            <span>Unduh Excel Spreadsheet (XLSX)</span>
                        </span>
                        <span>&rarr;</span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
