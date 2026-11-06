<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'in', // 'in' | 'out'
    },
    exportUrl: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['close']);

// Format tanggal hari ini (YYYY-MM-DD)
const todayStr = new Date().toISOString().split('T')[0];
const currentMonth = new Date().getMonth() + 1;
const currentYear = new Date().getFullYear();

// State pilihan periode: 'daily' | 'weekly' | 'monthly' | 'custom'
const period = ref('monthly');
const selectedDate = ref(todayStr);
const selectedMonth = ref(currentMonth);
const selectedYear = ref(currentYear);
const startDate = ref(new Date(new Date().setDate(1)).toISOString().split('T')[0]);
const endDate = ref(todayStr);

const months = [
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' },
];

const years = computed(() => {
    const list = [];
    for (let y = currentYear; y >= currentYear - 4; y--) {
        list.push(y);
    }
    return list;
});

const modalTitle = computed(() => {
    return props.type === 'in'
        ? 'Cetak Laporan Barang Masuk (Restock)'
        : 'Cetak Laporan Barang Keluar (Outbound)';
});

const downloadReport = (format) => {
    const params = new URLSearchParams({
        format: format,
        period: period.value,
    });

    if (period.value === 'daily') {
        params.append('date', selectedDate.value);
    } else if (period.value === 'monthly') {
        params.append('month', selectedMonth.value.toString());
        params.append('year', selectedYear.value.toString());
    } else if (period.value === 'custom') {
        params.append('start_date', startDate.value);
        params.append('end_date', endDate.value);
    }

    const fullUrl = `${props.exportUrl}?${params.toString()}`;
    window.open(fullUrl, '_blank');
    emit('close');
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
        @click.self="emit('close')"
    >
        <div class="relative w-full max-w-lg rounded-2xl bg-slate-900 border border-slate-700/80 shadow-2xl p-6 sm:p-7 text-slate-100 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Background Accent Glow -->
            <div class="absolute -top-16 -right-16 w-40 h-40 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>

            <!-- HEADER MODAL -->
            <div class="flex items-start justify-between gap-3 mb-5 border-b border-slate-800 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl">{{ type === 'in' ? '📥' : '📤' }}</span>
                        <h3 class="text-base sm:text-lg font-black text-white tracking-tight">
                            {{ modalTitle }}
                        </h3>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        Pilih rentang periode waktu transaksi yang ingin direkapitulasi ke dalam dokumen.
                    </p>
                </div>
                <button
                    type="button"
                    @click="emit('close')"
                    class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer text-sm font-bold"
                >
                    ✕
                </button>
            </div>

            <!-- BODY MODAL -->
            <div class="space-y-5">
                
                <!-- 1. PILIHAN PERIODE (TAB KOTAK) -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        1. Pilih Jangka Waktu Periode:
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <!-- Harian -->
                        <button
                            type="button"
                            @click="period = 'daily'"
                            :class="[
                                'flex flex-col items-center justify-center p-2.5 rounded-xl border text-xs font-bold transition cursor-pointer',
                                period === 'daily'
                                    ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow-sm'
                                    : 'bg-slate-800/80 border-slate-700 text-slate-400 hover:text-white hover:bg-slate-800'
                            ]"
                        >
                            <span class="text-base mb-0.5">📅</span>
                            <span>Harian</span>
                        </button>

                        <!-- Mingguan -->
                        <button
                            type="button"
                            @click="period = 'weekly'"
                            :class="[
                                'flex flex-col items-center justify-center p-2.5 rounded-xl border text-xs font-bold transition cursor-pointer',
                                period === 'weekly'
                                    ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow-sm'
                                    : 'bg-slate-800/80 border-slate-700 text-slate-400 hover:text-white hover:bg-slate-800'
                            ]"
                        >
                            <span class="text-base mb-0.5">📆</span>
                            <span>Mingguan</span>
                        </button>

                        <!-- Bulanan -->
                        <button
                            type="button"
                            @click="period = 'monthly'"
                            :class="[
                                'flex flex-col items-center justify-center p-2.5 rounded-xl border text-xs font-bold transition cursor-pointer',
                                period === 'monthly'
                                    ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow-sm'
                                    : 'bg-slate-800/80 border-slate-700 text-slate-400 hover:text-white hover:bg-slate-800'
                            ]"
                        >
                            <span class="text-base mb-0.5">🗓️</span>
                            <span>Bulanan</span>
                        </button>

                        <!-- Kustom -->
                        <button
                            type="button"
                            @click="period = 'custom'"
                            :class="[
                                'flex flex-col items-center justify-center p-2.5 rounded-xl border text-xs font-bold transition cursor-pointer',
                                period === 'custom'
                                    ? 'bg-amber-500/20 border-amber-500 text-amber-300 shadow-sm'
                                    : 'bg-slate-800/80 border-slate-700 text-slate-400 hover:text-white hover:bg-slate-800'
                            ]"
                        >
                            <span class="text-base mb-0.5">⏳</span>
                            <span>Kustom</span>
                        </button>
                    </div>
                </div>

                <!-- 2. SUB-INPUT SESUAI PERIODE TERPILIH -->
                <div class="bg-slate-950/60 p-4 rounded-xl border border-slate-800/90 space-y-3">
                    
                    <!-- Form Harian -->
                    <div v-if="period === 'daily'">
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Pilih Tanggal Transaksi:
                        </label>
                        <input
                            v-model="selectedDate"
                            type="date"
                            class="w-full text-xs rounded-xl bg-slate-900 border border-slate-700 text-white px-3.5 py-2.5 focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                        />
                        <p class="text-[11px] text-slate-400 mt-1.5">
                            Menghasilkan seluruh pergerakan barang pada tanggal yang dipilih.
                        </p>
                    </div>

                    <!-- Form Mingguan -->
                    <div v-else-if="period === 'weekly'">
                        <div class="text-xs text-slate-300 font-semibold mb-1">
                            Periode 7 Hari Terakhir
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Mencakup seluruh transaksi barang selama 7 hari kalender terakhir terhitung sampai hari ini secara otomatis.
                        </p>
                    </div>

                    <!-- Form Bulanan -->
                    <div v-else-if="period === 'monthly'" class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Bulan:
                            </label>
                            <select
                                v-model="selectedMonth"
                                class="w-full text-xs rounded-xl bg-slate-900 border border-slate-700 text-white px-3.5 py-2.5 focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                            >
                                <option v-for="m in months" :key="m.value" :value="m.value">
                                    {{ m.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Tahun:
                            </label>
                            <select
                                v-model="selectedYear"
                                class="w-full text-xs rounded-xl bg-slate-900 border border-slate-700 text-white px-3.5 py-2.5 focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                            >
                                <option v-for="y in years" :key="y" :value="y">
                                    {{ y }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Form Kustom -->
                    <div v-else-if="period === 'custom'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Dari Tanggal (Mulai):
                            </label>
                            <input
                                v-model="startDate"
                                type="date"
                                class="w-full text-xs rounded-xl bg-slate-900 border border-slate-700 text-white px-3.5 py-2.5 focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Sampai Tanggal (Selesai):
                            </label>
                            <input
                                v-model="endDate"
                                type="date"
                                class="w-full text-xs rounded-xl bg-slate-900 border border-slate-700 text-white px-3.5 py-2.5 focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                            />
                        </div>
                    </div>

                </div>

                <!-- 3. TOMBOL DOWNLOAD AKSI (PDF & EXCEL) -->
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                        2. Pilih Format Dokumen Unduhan:
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        
                        <!-- Unduh PDF -->
                        <button
                            type="button"
                            @click="downloadReport('pdf')"
                            class="flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white font-bold text-xs transition shadow-lg shadow-rose-600/25 active:scale-95 cursor-pointer"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span>Unduh Laporan PDF</span>
                        </button>

                        <!-- Unduh Excel -->
                        <button
                            type="button"
                            @click="downloadReport('excel')"
                            class="flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold text-xs transition shadow-lg shadow-emerald-600/25 active:scale-95 cursor-pointer"
                        >
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Unduh Spreadsheet Excel</span>
                        </button>

                    </div>
                </div>

            </div>

            <!-- FOOTER MODAL -->
            <div class="mt-6 pt-4 border-t border-slate-800 flex justify-end">
                <button
                    type="button"
                    @click="emit('close')"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>

