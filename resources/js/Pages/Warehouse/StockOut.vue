<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    products: Array,
    destinations: Array,
});

const selectedProductId = ref(props.products.length > 0 ? props.products[0].id : null);

const currentProduct = computed(() => {
    return props.products.find(p => p.id === selectedProductId.value);
});

const form = useForm({
    product_id: selectedProductId.value,
    destination: props.destinations[0],
    reference_number: '',
    recipient_name: '',
    notes: '',
    proof_image: null,
    items: [],
});

const setupItems = () => {
    if (currentProduct.value) {
        form.product_id = currentProduct.value.id;
        form.items = currentProduct.value.variants.map(v => ({
            variant_id: v.id,
            size: v.size,
            available: v.current_stock,
            quantity: 0,
        }));
    }
};

// Initial setup
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
    form.post(route('warehouse.out.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Input Barang Keluar - Gudang Distro" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Pengeluaran Barang (Distribusi / Kirim)
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Keluarkan baju dari gudang untuk dikirim ke toko cabang, reseller, atau pesanan online.
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
        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 p-6 sm:p-8 space-y-6 shadow-xl">

                <!-- Error Alert -->
                <div v-if="$page.props.errors.error" class="p-4 rounded-xl bg-rose-950/40 border border-rose-800/80 text-rose-300 text-xs">
                    {{ $page.props.errors.error }}
                </div>

                <!-- 1. PILIH ARTIKEL PRODUK -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Pilih Artikel Baju yang Dikeluarkan <span class="text-amber-400">*</span>
                    </label>
                    <select
                        v-model="selectedProductId"
                        @change="onProductChange"
                        class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-3 px-3.5 transition"
                    >
                        <option v-for="p in products" :key="p.id" :value="p.id">
                            {{ p.name }} (Sisa Stok: {{ p.variants.reduce((a, b) => a + b.current_stock, 0) }} pcs)
                        </option>
                    </select>
                </div>

                <!-- 2. TUJUAN & NOMOR REFERENSI -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Tujuan Pengeluaran <span class="text-amber-400">*</span>
                        </label>
                        <select
                            v-model="form.destination"
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition"
                        >
                            <option v-for="d in destinations" :key="d" :value="d">
                                {{ d }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            No. Surat Jalan / No. Resi / Order ID <span class="text-amber-400">*</span>
                        </label>
                        <input
                            v-model="form.reference_number"
                            type="text"
                            placeholder="Contoh: DO-2026/04 atau RESI-JNE-123"
                            required
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                        />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Nama Penerima / Kurir / Toko Cabang
                    </label>
                    <input
                        v-model="form.recipient_name"
                        type="text"
                        placeholder="Contoh: Mas Agus (Toko Cabang 1) / Kurir SiCepat"
                        class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
                    />
                </div>

                <!-- 3. MATRIKS INPUT JUMLAH PENGELUARAN DENGAN INFO STOK TERSEDIA (LEBAR) -->
                <div class="p-6 rounded-2xl bg-slate-950/60 border border-slate-800/80 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wider text-white">
                                Jumlah Baju yang Dikeluarkan
                            </label>
                            <p class="text-[11px] text-slate-500 mt-0.5">Cek fisik stok rak & jumlah sisa sebelum input.</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-500">Total Baju Keluar: </span>
                            <span class="text-xl font-black text-amber-400 font-mono">{{ totalQuantity }} pcs</span>
                        </div>
                    </div>

                    <!-- Grid Ukuran Box Luas -->
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3.5 pt-1">
                        <div
                            v-for="(item, idx) in form.items"
                            :key="idx"
                            class="bg-slate-900 p-3.5 rounded-xl border border-slate-800 text-center space-y-2 transition"
                            :class="item.quantity > item.available ? 'border-rose-500/80 bg-rose-950/20' : 'focus-within:border-amber-500/60'"
                        >
                            <div class="flex items-center justify-between text-xs px-1">
                                <span class="font-black text-slate-200 text-sm">{{ item.size }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">Sisa: {{ item.available }}</span>
                            </div>
                            <input
                                v-model.number="item.quantity"
                                type="number"
                                min="0"
                                :max="item.available"
                                class="w-full text-center text-base font-bold font-mono rounded-lg border-slate-800 bg-slate-950 text-white focus:ring-amber-500 focus:border-amber-500 py-1.5"
                            />
                            <div v-if="item.quantity > item.available" class="text-[10px] text-rose-400 font-bold">
                                Melebihi stok!
                            </div>
                            <div v-else class="text-[10px] text-slate-500">pcs</div>
                        </div>
                    </div>
                </div>

                <!-- 4. UPLOAD FOTO PAKET / RESI EKSPEDISI -->
                <div class="space-y-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Foto Bukti Paket Diserahkan / Resi (Bukti Fisik)
                    </label>
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <label class="flex flex-col items-center justify-center w-full sm:w-72 h-32 border-2 border-dashed border-slate-800 hover:border-amber-500/50 rounded-xl cursor-pointer bg-slate-950 hover:bg-slate-900 transition p-4 text-center">
                            <svg class="w-8 h-8 text-slate-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span class="text-xs font-medium text-slate-400">Pilih / Jepret Foto Paket</span>
                            <input type="file" accept="image/*" @change="handleImageUpload" class="hidden" />
                        </label>

                        <div v-if="imagePreview" class="relative">
                            <img :src="imagePreview" class="w-32 h-32 object-cover rounded-xl border border-slate-800 shadow-md" />
                            <span class="absolute top-1.5 right-1.5 bg-amber-500 text-slate-950 font-black rounded-full w-5 h-5 flex items-center justify-center text-[11px]">✓</span>
                        </div>
                    </div>
                </div>

                <!-- 5. CATATAN PENGIRIMAN -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">Catatan</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        placeholder="Contoh: Paket dipacking dalam polymailer hitam tebal."
                        class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-slate-100 py-2.5 px-3.5 transition placeholder-slate-600"
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
                        class="px-6 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg shadow-amber-500/20 cursor-pointer transition"
                    >
                        {{ form.processing ? 'Memproses...' : 'Keluarkan Barang & Kurangi Stok' }}
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
