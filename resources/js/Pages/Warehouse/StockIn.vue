<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    products: Array,
    categories: Array,
});

const defaultSizes = ['S', 'M', 'L', 'XL', 'XXL'];

const selectedProductId = ref(props.products.length > 0 ? props.products[0].id : null);

const form = useForm({
    product_id: selectedProductId.value,
    reference_number: '',
    supplier_name: '',
    notes: '',
    proof_image: null,
    items: [],
});

const setupItems = () => {
    form.product_id = selectedProductId.value;
    const prod = props.products.find(p => p.id === selectedProductId.value);
    if (prod && prod.variants && prod.variants.length > 0) {
        form.items = prod.variants.map(v => ({ size: v.size, quantity: 0 }));
    } else {
        form.items = defaultSizes.map(s => ({ size: s, quantity: 0 }));
    }
};

// Inisialisasi awal
setupItems();

const onProductChange = () => {
    setupItems();
};

const totalQuantity = computed(() => {
    return form.items.reduce((acc, curr) => acc + (parseInt(curr.quantity) || 0), 0);
});

const imagePreview = ref(null);
const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.proof_image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('warehouse.in.store'), {
        onSuccess: () => {
            form.reset('reference_number', 'supplier_name', 'notes', 'proof_image');
            form.items.forEach(item => item.quantity = 0);
            imagePreview.value = null;
        },
    });
};
</script>

<template>
    <Head title="Input Barang Masuk - Gudang Distro" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Penerimaan Barang Masuk (Restock Konveksi)
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Catat barang restock yang datang dari konveksi ke stok gudang berdasarkan artikel master data yang valid.
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

        <!-- CONTAINER KERJA LEBAR & NYAMAN -->
        <div class="max-w-4xl mx-auto space-y-5">

            <!-- BANNER NOTIFIKASI STANDARD INDUSTRI: TAMBAH ARTIKEL BARU -->
            <div class="p-4 rounded-2xl bg-purple-950/40 border border-purple-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5 text-purple-300">
                    <span class="text-base">👕</span>
                    <span>Barang yang baru tiba merupakan <strong>model/artikel baru</strong> yang belum pernah ada di katalog?</span>
                </div>
                <Link
                    :href="route('warehouse.products.create')"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-purple-500 hover:bg-purple-400 text-slate-950 font-bold text-xs whitespace-nowrap transition cursor-pointer"
                >
                    + Daftarkan di Master Produk →
                </Link>
            </div>

            <!-- FORM UTAMA BARANG MASUK (TRANSAKSIONAL BERSIH) -->
            <form @submit.prevent="submit" class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 p-6 sm:p-8 space-y-6 shadow-xl">

                <!-- Error Flash Alert -->
                <div v-if="$page.props.errors.error" class="p-4 rounded-xl bg-rose-950/40 border border-rose-800/80 text-rose-300 text-xs">
                    {{ $page.props.errors.error }}
                </div>

                <!-- 1. PILIH ARTIKEL PRODUK -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Pilih Artikel Baju yang Masuk <span class="text-emerald-400">*</span>
                        </label>
                        <Link :href="route('warehouse.products.index')" class="text-[11px] text-purple-400 hover:text-purple-300">
                            Kelola Master Produk
                        </Link>
                    </div>
                    <select
                        v-model="selectedProductId"
                        @change="onProductChange"
                        required
                        class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-950 text-slate-100 py-3 px-3.5 transition"
                    >
                        <option v-for="p in products" :key="p.id" :value="p.id">
                            {{ p.name }} ({{ p.category?.name }})
                        </option>
                    </select>
                    <div v-if="products.length === 0" class="text-xs text-rose-400 mt-1">
                        Belum ada produk di Master Data. Silakan daftarkan produk terlebih dahulu.
                    </div>
                </div>

                <!-- 2. DATA DOKUMEN / NOTA KONVEKSI -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            No. Surat Jalan / Nota Konveksi <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="form.reference_number"
                            type="text"
                            placeholder="Contoh: SJ-2026/11/04"
                            required
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600 font-mono"
                        />
                        <div v-if="form.errors.reference_number" class="text-xs text-rose-400">{{ form.errors.reference_number }}</div>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Nama Konveksi / Supplier
                        </label>
                        <input
                            v-model="form.supplier_name"
                            type="text"
                            placeholder="Contoh: Konveksi Berkah Bandung"
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                        />
                    </div>
                </div>

                <!-- 3. MATRIKS INPUT JUMLAH PER UKURAN -->
                <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-white">
                                Jumlah Baju Masuk per Ukuran
                            </label>
                            <p class="text-[11px] text-slate-500 mt-0.5">Masukkan jumlah pcs fisik yang diterima dari konveksi.</p>
                        </div>
                        <div class="text-left sm:text-right">
                            <span class="text-xs text-slate-500">Total Baju Masuk: </span>
                            <span class="text-xl font-black text-emerald-400 font-mono">{{ totalQuantity }} pcs</span>
                        </div>
                    </div>

                    <!-- Grid Ukuran Box -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3.5 pt-1">
                        <div
                            v-for="(item, idx) in form.items"
                            :key="idx"
                            class="bg-slate-900 p-3.5 rounded-xl border border-slate-800 text-center space-y-2 focus-within:border-emerald-500/60 transition"
                        >
                            <div class="text-sm font-black text-slate-200">{{ item.size }}</div>
                            <input
                                v-model.number="item.quantity"
                                type="number"
                                min="0"
                                class="w-full text-center text-base font-bold font-mono rounded-lg border-slate-800 bg-slate-950 text-white focus:ring-emerald-500 focus:border-emerald-500 py-1.5"
                            />
                            <div class="text-[10px] text-slate-500">pcs</div>
                        </div>
                    </div>
                </div>

                <!-- 4. UPLOAD FOTO BUKTI NOTA / SURAT JALAN -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Foto Fisik Nota / Surat Jalan (Wajib untuk Verifikasi Owner)
                    </label>
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <label class="flex flex-col items-center justify-center w-full sm:w-72 h-32 border-2 border-dashed border-slate-800 hover:border-emerald-500/50 rounded-xl cursor-pointer bg-slate-950 hover:bg-slate-900 transition p-4 text-center">
                            <svg class="w-8 h-8 text-slate-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="text-xs font-medium text-slate-400">Pilih / Jepret Foto Nota</span>
                            <input type="file" accept="image/*" @change="handleImageUpload" class="hidden" />
                        </label>

                        <div v-if="imagePreview" class="relative">
                            <img :src="imagePreview" class="w-32 h-32 object-cover rounded-xl border border-slate-800 shadow-md" />
                            <span class="absolute top-1.5 right-1.5 bg-emerald-500 text-slate-950 font-black rounded-full w-5 h-5 flex items-center justify-center text-[11px]">✓</span>
                        </div>
                    </div>
                </div>

                <!-- 5. CATATAN TAMBAHAN -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">Catatan Tambahan</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        placeholder="Contoh: Baju diterima dalam 2 karung kardus utuh, jahitan rapi."
                        class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                    ></textarea>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <Link
                        :href="route('warehouse.dashboard')"
                        class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || totalQuantity === 0"
                        class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg shadow-emerald-500/20 cursor-pointer transition"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan & Tambah ke Stok Gudang' }}
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
