<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    product: Object,
    categories: Array,
});

const form = useForm({
    name: props.product.name,
    category_id: props.product.category_id,
    cost_price: props.product.cost_price,
    selling_price: props.product.selling_price,
    description: props.product.description || '',
    new_sizes: [],
});

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
    form.put(route('warehouse.products.update', props.product.id));
};
</script>

<template>
    <Head :title="`Edit ${product.name} - Distro Ops`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Edit Master Artikel: {{ product.name }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Perbarui nama produk, kategori, harga jual retail, atau tambahkan varian ukuran baru.
                    </p>
                </div>
                <Link
                    :href="route('warehouse.products.index')"
                    class="text-xs font-semibold text-slate-400 hover:text-white bg-slate-900 border border-slate-800 hover:border-slate-700 px-4 py-2 rounded-xl transition"
                >
                    ← Kembali
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 p-6 sm:p-8 space-y-6 shadow-xl">

                <!-- 1. IDENTITAS UTAMA ARTIKEL -->
                <div class="space-y-4">
                    <h3 class="text-xs font-black uppercase tracking-wider text-purple-400">
                        1. Informasi Artikel Baju
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Nama Model / Artikel Baju <span class="text-rose-400">*</span>
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

                <!-- 2. DAFTAR VARIAN UKURAN AKTIF & TAMBAH UKURAN BARU -->
                <div class="p-6 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-4">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-purple-400">
                            2. Varian Ukuran Artikel
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
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <Link
                        :href="route('warehouse.products.index')"
                        class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 rounded-xl bg-purple-500 hover:bg-purple-400 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg shadow-purple-500/20 cursor-pointer transition"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui Master Artikel' }}
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
