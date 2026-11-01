<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
    defaultSizes: Array,
});

const form = useForm({
    name: '',
    category_id: props.categories.length > 0 ? props.categories[0].id : '',
    cost_price: '',
    selling_price: '',
    description: '',
    sizes: [...props.defaultSizes],
});

const availableSizes = ref([...props.defaultSizes, '3XL', 'ALL_SIZE']);
const customSizeInput = ref('');

const toggleSize = (size) => {
    const idx = form.sizes.indexOf(size);
    if (idx > -1) {
        if (form.sizes.length > 1) {
            form.sizes.splice(idx, 1);
        }
    } else {
        form.sizes.push(size);
    }
};

const addCustomSize = () => {
    const s = customSizeInput.value.trim().toUpperCase();
    if (s && !availableSizes.value.includes(s)) {
        availableSizes.value.push(s);
        form.sizes.push(s);
        customSizeInput.value = '';
    }
};

const submit = () => {
    form.post(route('warehouse.products.store'));
};
</script>

<template>
    <Head title="Tambah Master Artikel Baju - Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Pendaftaran Artikel Baju Baru (Master Data)
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Daftarkan nama artikel baju, kategori, harga resmi, dan varian ukuran ke dalam katalog distro.
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
                                placeholder="Contoh: Oversize Heavy Cotton 'Tokyo Night'"
                                required
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-white py-3 px-3.5 transition placeholder-slate-600 font-semibold"
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
                                Modal HPP (Rp) <span class="text-slate-500 text-[10px] font-normal">(Opsional - Khusus Owner)</span>
                            </label>
                            <input
                                v-model.number="form.cost_price"
                                type="number"
                                min="0"
                                placeholder="Contoh: 55000"
                                class="w-full text-sm font-mono rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-emerald-400 py-3 px-3.5 transition placeholder-slate-600"
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
                                placeholder="Contoh: 145000"
                                required
                                class="w-full text-sm font-mono rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-amber-400 font-bold py-3 px-3.5 transition placeholder-slate-600"
                            />
                            <div v-if="form.errors.selling_price" class="text-xs text-rose-400 font-semibold mt-1">
                                {{ form.errors.selling_price }}
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Deskripsi / Karakter Bahan (Opsional)
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Contoh: 100% Cotton Combed 24s, sablon discharge, jahitan rantai rapi."
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                        ></textarea>
                    </div>
                </div>

                <!-- 2. PEMILIHAN VARIAN UKURAN RESMI -->
                <div class="p-6 rounded-2xl bg-slate-950/70 border border-slate-800/80 space-y-4">
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-purple-400">
                            2. Struktur Varian Ukuran Resmi
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                            Pilih ukuran apa saja yang diproduksi untuk artikel ini.
                        </p>
                    </div>

                    <!-- Pills Checkbox Ukuran -->
                    <div class="flex flex-wrap gap-2.5">
                        <button
                            v-for="s in availableSizes"
                            :key="s"
                            type="button"
                            @click="toggleSize(s)"
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-black font-mono transition cursor-pointer flex items-center gap-2 border',
                                form.sizes.includes(s)
                                    ? 'bg-purple-500 text-slate-950 border-purple-400 shadow-md shadow-purple-500/20'
                                    : 'bg-slate-900 text-slate-400 border-slate-800 hover:border-slate-700 hover:text-white'
                            ]"
                        >
                            <span>{{ form.sizes.includes(s) ? '✓' : '+' }}</span>
                            <span>Size {{ s }}</span>
                        </button>
                    </div>

                    <!-- Tambah Ukuran Kustom -->
                    <div class="flex items-center gap-2 pt-2 border-t border-slate-800/60">
                        <span class="text-xs text-slate-400">Tambah ukuran non-standar:</span>
                        <input
                            v-model="customSizeInput"
                            @keyup.enter="addCustomSize"
                            type="text"
                            placeholder="Contoh: 4XL / ALL"
                            class="text-xs px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white w-36 uppercase font-mono"
                        />
                        <button
                            type="button"
                            @click="addCustomSize"
                            class="text-xs px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 cursor-pointer font-bold"
                        >
                            + Tambahkan
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
                        :disabled="form.processing || form.sizes.length === 0"
                        class="px-6 py-2.5 rounded-xl bg-purple-500 hover:bg-purple-400 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg shadow-purple-500/20 cursor-pointer transition"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan ke Master Data Produk' }}
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
