<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    movements: Object,
    filters: Object,
});

const filterType = ref(props.filters.type || '');
const search = ref(props.filters.search || '');

const selectedProof = ref(null);

const applyFilters = () => {
    router.get(route('warehouse.movements'), {
        type: filterType.value,
        search: search.value,
    }, { preserveState: true, replace: true });
};

const formatTime = (isoString) => {
    const d = new Date(isoString);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <Head title="Buku Besar Riwayat Mutasi Stok - Gudang Distro" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Buku Besar Mutasi Stok (Audit Ledger)
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Jejak abadi seluruh pergerakan barang keluar-masuk. Data ini tidak dapat diubah atau dihapus.
                    </p>
                </div>
                <Link
                    :href="route('warehouse.dashboard')"
                    class="text-xs font-semibold text-slate-400 hover:text-white bg-slate-900 border border-slate-800 hover:border-slate-700 px-4 py-2 rounded-xl transition"
                >
                    ← Kembali
                </Link>
            </div>
        </template>

        <!-- CONTAINER TABEL MUTASI LEBAR & LEGA -->
        <div class="space-y-5">

            <!-- FILTERS BAR -->
            <div class="bg-slate-900/70 backdrop-blur-md p-4 sm:p-5 rounded-2xl border border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <button
                        @click="filterType = ''; applyFilters()"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer',
                            filterType === ''
                                ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                                : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800 hover:bg-slate-800'
                        ]"
                    >
                        Semua Mutasi
                    </button>
                    <button
                        @click="filterType = 'in'; applyFilters()"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer',
                            filterType === 'in'
                                ? 'bg-emerald-500 text-slate-950 font-black shadow-xs'
                                : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800 hover:bg-slate-800'
                        ]"
                    >
                        Barang Masuk (IN)
                    </button>
                    <button
                        @click="filterType = 'out'; applyFilters()"
                        :class="[
                            'px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer',
                            filterType === 'out'
                                ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                                : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800 hover:bg-slate-800'
                        ]"
                    >
                        Barang Keluar (OUT)
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari artikel, SKU, nota, tujuan..."
                        class="text-xs px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-500 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 w-full sm:w-72 transition"
                    />
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold cursor-pointer border border-slate-700 transition"
                    >
                        Cari
                    </button>
                </div>
            </div>

            <!-- TABLE MUTASI LEBAR -->
            <div class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="p-4 sm:p-5">Waktu</th>
                                <th class="p-4 sm:p-5">Tipe</th>
                                <th class="p-4 sm:p-5">Artikel & Ukuran</th>
                                <th class="p-4 sm:p-5 text-center">Jumlah</th>
                                <th class="p-4 sm:p-5">Perubahan Stok</th>
                                <th class="p-4 sm:p-5">Referensi / Tujuan</th>
                                <th class="p-4 sm:p-5">Operator</th>
                                <th class="p-4 sm:p-5 text-center">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-for="m in movements.data" :key="m.id" class="hover:bg-slate-800/30 transition">
                                <td class="p-4 sm:p-5 whitespace-nowrap text-slate-400 font-mono text-xs">
                                    {{ formatTime(m.created_at) }}
                                </td>
                                <td class="p-4 sm:p-5 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider',
                                            m.type === 'in' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' :
                                            m.type === 'out' ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'bg-sky-500/15 text-sky-400 border border-sky-500/30'
                                        ]"
                                    >
                                        {{ m.type }}
                                    </span>
                                </td>
                                <td class="p-4 sm:p-5">
                                    <div class="font-bold text-white text-sm">
                                        {{ m.variant?.product?.name }}
                                    </div>
                                    <div class="text-xs text-slate-400 font-mono mt-0.5">
                                        Ukuran {{ m.variant?.size }}
                                    </div>
                                </td>
                                <td class="p-4 sm:p-5 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            'font-mono font-extrabold text-sm',
                                            m.type === 'in' ? 'text-emerald-400' : 'text-amber-400'
                                        ]"
                                    >
                                        {{ m.type === 'in' ? '+' : '-' }}{{ m.quantity }} pcs
                                    </span>
                                </td>
                                <td class="p-4 sm:p-5 whitespace-nowrap font-mono text-slate-400 text-xs">
                                    {{ m.stock_before }} ➔ <strong class="text-white font-bold">{{ m.stock_after }}</strong>
                                </td>
                                <td class="p-4 sm:p-5">
                                    <div class="font-semibold text-slate-200">
                                        {{ m.reference_number || '-' }}
                                    </div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        {{ m.destination || '-' }}
                                    </div>
                                    <div v-if="m.notes" class="text-[11px] text-slate-500 italic mt-0.5">
                                        "{{ m.notes }}"
                                    </div>
                                </td>
                                <td class="p-4 sm:p-5 whitespace-nowrap text-slate-400">
                                    {{ m.user?.name }}
                                </td>
                                <td class="p-4 sm:p-5 text-center whitespace-nowrap">
                                    <button
                                        v-if="m.proof_image"
                                        @click="selectedProof = '/storage/' + m.proof_image"
                                        class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-400 text-xs font-semibold cursor-pointer border border-slate-700 transition"
                                    >
                                        📷 Foto
                                    </button>
                                    <span v-else class="text-slate-600">-</span>
                                </td>
                            </tr>

                            <tr v-if="movements.data.length === 0">
                                <td colspan="8" class="p-10 text-center text-slate-500">
                                    Belum ada riwayat mutasi stok.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="movements.links && movements.links.length > 3" class="p-4 sm:p-5 border-t border-slate-800/80 flex items-center justify-between">
                    <div class="text-xs text-slate-400">
                        Menampilkan {{ movements.from || 0 }} - {{ movements.to || 0 }} dari {{ movements.total }} mutasi
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Link
                            v-for="(link, i) in movements.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 rounded-lg text-xs font-semibold transition',
                                link.active ? 'bg-amber-500 text-slate-950 font-black' :
                                !link.url ? 'text-slate-600 pointer-events-none' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>

        <!-- MODAL FOTO BUKTI -->
        <div
            v-if="selectedProof"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
            @click.self="selectedProof = null"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h4 class="font-bold text-white text-sm">Bukti Fisik Surat Jalan / Nota</h4>
                    <button @click="selectedProof = null" class="text-slate-400 hover:text-white text-lg p-1 cursor-pointer">✕</button>
                </div>
                <div class="bg-slate-950 rounded-2xl p-2 border border-slate-800 flex items-center justify-center">
                    <img :src="selectedProof" class="w-full max-h-96 object-contain rounded-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
