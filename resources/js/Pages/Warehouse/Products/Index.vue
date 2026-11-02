<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
    isOwner: Boolean,
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || '');
const showHpp = ref(false);

const applyFilter = () => {
    router.get(route('warehouse.products.index'), {
        search: search.value,
        category: category.value,
    }, { preserveState: true, replace: true });
};

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);
};

const deleteProduct = (product) => {
    if (confirm(`Yakin ingin menghapus artikel '${product.name}' dari master data?`)) {
        router.delete(route('warehouse.products.destroy', product.id));
    }
};
</script>

<template>
    <Head title="Master Data Produk - Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Master Data Artikel & Produk
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Katalog resmi artikel baju distro, penentuan harga jual/modal HPP, dan struktur varian ukuran.
                    </p>
                </div>
                <Link
                    :href="route('warehouse.products.create')"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-purple-500 hover:bg-purple-400 text-slate-950 text-xs font-black shadow-lg shadow-purple-500/20 transition cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    + Tambah Artikel Baru
                </Link>
            </div>
        </template>

        <div class="space-y-5">

            <!-- Flash Success / Error Message -->
            <div v-if="$page.props.flash?.success" class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800 text-emerald-300 text-xs flex items-center justify-between">
                <span>{{ $page.props.flash.success }}</span>
            </div>
            <div v-if="$page.props.errors?.error" class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800 text-rose-300 text-xs flex items-center justify-between">
                <span>{{ $page.props.errors.error }}</span>
            </div>

            <!-- FILTERS BAR -->
            <div class="bg-slate-900/70 backdrop-blur-md p-4 sm:p-5 rounded-2xl border border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <select
                        v-model="category"
                        @change="applyFilter"
                        class="text-xs px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>

                    <button
                        v-if="isOwner"
                        @click="showHpp = !showHpp"
                        class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 hover:border-slate-700 text-slate-400 hover:text-amber-400 text-xs font-semibold transition cursor-pointer flex items-center gap-1.5"
                        title="Tampilkan / Sembunyikan Modal HPP"
                    >
                        <span>{{ showHpp ? '👁️' : '🙈' }}</span>
                        <span>{{ showHpp ? 'Sembunyikan HPP' : 'Buka HPP' }}</span>
                    </button>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <input
                        v-model="search"
                        @keyup.enter="applyFilter"
                        type="text"
                        placeholder="Cari nama artikel baju..."
                        class="text-xs px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 placeholder-slate-500 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 w-full sm:w-72 transition"
                    />
                    <button
                        @click="applyFilter"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold cursor-pointer border border-slate-700 transition"
                    >
                        Cari
                    </button>
                </div>
            </div>

            <!-- TABLE MASTER PRODUK -->
            <div class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="p-4 sm:p-5">Produk & Artikel</th>
                                <th class="p-4 sm:p-5">Kategori</th>
                                <th class="p-4 sm:p-5">Varian Ukuran</th>
                                <th class="p-4 sm:p-5 text-center">Total Stok</th>
                                <th v-if="isOwner" class="p-4 sm:p-5 text-right">Modal (HPP)</th>
                                <th class="p-4 sm:p-5 text-right">Harga Retail</th>
                                <th class="p-4 sm:p-5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            <tr v-for="p in products.data" :key="p.id" class="hover:bg-slate-800/30 transition">
                                <td class="p-4 sm:p-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-center shrink-0 overflow-hidden shadow-inner">
                                            <img
                                                v-if="p.image_url"
                                                :src="p.image_url"
                                                :alt="p.name"
                                                class="w-full h-full object-cover"
                                            />
                                            <span v-else class="text-slate-600 font-mono text-[9px] font-bold">
                                                NO FOTO
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-bold text-white text-sm">{{ p.name }}</div>
                                            <div v-if="p.description" class="text-[11px] text-slate-500 font-normal line-clamp-1 mt-0.5">
                                                {{ p.description }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 sm:p-5 whitespace-nowrap">
                                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700/60">
                                        {{ p.category?.name }}
                                    </span>
                                </td>
                                <td class="p-4 sm:p-5">
                                    <div class="flex flex-wrap gap-1.5 max-w-xs">
                                        <span
                                            v-for="v in p.variants"
                                            :key="v.id"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-950 border border-slate-800 text-[11px]"
                                            :class="v.current_stock <= 5 ? 'text-rose-400 font-bold border-rose-900/50' : 'text-slate-300'"
                                        >
                                            <strong>{{ v.size }}:</strong> {{ v.current_stock }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4 sm:p-5 text-center whitespace-nowrap font-mono font-extrabold text-sm text-white">
                                    {{ p.variants.reduce((acc, curr) => acc + curr.current_stock, 0) }} pcs
                                </td>
                                <td v-if="isOwner" class="p-4 sm:p-5 text-right whitespace-nowrap font-mono text-xs text-emerald-400">
                                    {{ showHpp ? formatRupiah(p.cost_price) : 'Rp ••••••••' }}
                                </td>
                                <td class="p-4 sm:p-5 text-right whitespace-nowrap font-mono font-bold text-sm text-white">
                                    {{ formatRupiah(p.selling_price) }}
                                </td>
                                <td class="p-4 sm:p-5 text-center whitespace-nowrap space-x-2">
                                    <Link
                                        :href="route('warehouse.products.edit', p.id)"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition"
                                    >
                                        ✏️ Edit
                                    </Link>
                                    <button
                                        @click="deleteProduct(p)"
                                        class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-rose-500/15 hover:text-rose-400 text-slate-500 text-xs font-semibold border border-slate-800 hover:border-rose-500/30 transition cursor-pointer"
                                    >
                                        🗑️
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="products.data.length === 0">
                                <td :colspan="isOwner ? 7 : 6" class="p-10 text-center text-slate-500">
                                    Belum ada artikel produk yang cocok dengan pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="products.links && products.links.length > 3" class="p-4 sm:p-5 border-t border-slate-800/80 flex items-center justify-between">
                    <div class="text-xs text-slate-400">
                        Menampilkan {{ products.from || 0 }} - {{ products.to || 0 }} dari {{ products.total }} artikel
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Link
                            v-for="(link, i) in products.links"
                            :key="i"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 rounded-lg text-xs font-semibold transition',
                                link.active ? 'bg-purple-500 text-slate-950 font-black' :
                                !link.url ? 'text-slate-600 pointer-events-none' : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
