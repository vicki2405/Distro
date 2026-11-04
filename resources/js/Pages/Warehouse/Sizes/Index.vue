<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    sizes: Array,
});

// Form Tambah Ukuran
const createForm = useForm({
    name: '',
    order: '',
});

const submitCreate = () => {
    createForm.post(route('warehouse.sizes.store'), {
        onSuccess: () => createForm.reset(),
    });
};

// State & Form Edit Ukuran
const editingSize = ref(null);
const editForm = useForm({
    name: '',
    order: 0,
});

const openEditModal = (s) => {
    editingSize.value = s;
    editForm.name = s.name;
    editForm.order = s.order;
};

const submitEdit = () => {
    if (!editingSize.value) return;
    editForm.put(route('warehouse.sizes.update', editingSize.value.id), {
        onSuccess: () => {
            editingSize.value = null;
            editForm.reset();
        },
    });
};

const deleteSize = (s) => {
    if (confirm(`Yakin ingin menghapus ukuran '${s.name}' dari Master Data?`)) {
        router.delete(route('warehouse.sizes.destroy', s.id));
    }
};
</script>

<template>
    <Head title="Size - Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    Size
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Kelola daftar ukuran pakaian resmi untuk katalog produk distro.
                </p>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Flash Alert -->
            <div v-if="$page.props.flash?.success" class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800 text-emerald-300 text-xs">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.errors?.error" class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800 text-rose-300 text-xs">
                {{ $page.props.errors.error }}
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- 1. FORM TAMBAH UKURAN (KIRI) -->
                <div class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 sm:p-6 space-y-4 h-fit">
                    <div>
                        <h3 class="font-bold text-white text-base">Tambah Size</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Daftarkan ukuran baru (misal: S, M, L, XL, 3XL, 28, ALL SIZE).</p>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4 pt-1">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Nama Size <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                placeholder="Contoh: 4XL / 28 / OVERSIZE"
                                required
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-white py-2.5 px-3.5 transition uppercase font-mono font-bold"
                            />
                            <div v-if="createForm.errors.name" class="text-xs text-rose-400 font-semibold mt-1">
                                {{ createForm.errors.name }}
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Urutan Tampilan
                            </label>
                            <input
                                v-model.number="createForm.order"
                                type="number"
                                placeholder="Nomor urut (misal: 1, 2, 3)"
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-200 py-2.5 px-3.5 transition font-mono"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="w-full py-2.5 px-4 rounded-xl bg-purple-500 hover:bg-purple-400 text-slate-950 text-xs font-black shadow-lg shadow-purple-500/20 transition cursor-pointer"
                        >
                            {{ createForm.processing ? 'Menyimpan...' : '+ Tambah Size' }}
                        </button>
                    </form>
                </div>

                <!-- 2. TABEL DAFTAR UKURAN (KANAN - 2 KOLOM) -->
                <div class="lg:col-span-2 bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 sm:p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-white text-base">Daftar Size</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Total {{ sizes.length }} size terdaftar.</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-slate-800">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-950 text-slate-400 uppercase font-bold tracking-wider border-b border-slate-800 font-mono">
                                <tr>
                                    <th class="py-3 px-4">Urutan</th>
                                    <th class="py-3 px-4">Nama Size</th>
                                    <th class="py-3 px-4 text-center">Dipakai di Varian</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 bg-slate-950/40 font-mono">
                                <tr v-for="s in sizes" :key="s.id" class="hover:bg-slate-900/40 transition">
                                    <td class="py-3 px-4 text-slate-500 text-xs">
                                        #{{ s.order }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 text-white font-bold text-xs">
                                            Size {{ s.name }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="s.variants_count > 0 ? 'bg-purple-500/10 text-purple-300 border border-purple-500/20' : 'bg-slate-800 text-slate-400'">
                                            {{ s.variants_count }} varian
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right space-x-2">
                                        <button
                                            type="button"
                                            @click="openEditModal(s)"
                                            class="text-xs text-amber-400 hover:text-amber-300 font-semibold cursor-pointer underline font-sans"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteSize(s)"
                                            class="text-xs text-rose-400 hover:text-rose-300 font-semibold cursor-pointer underline font-sans"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="sizes.length === 0">
                                    <td colspan="4" class="py-6 text-center text-slate-500 font-sans">
                                        Belum ada size yang terdaftar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

        <!-- MODAL EDIT UKURAN -->
        <div v-if="editingSize" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-white text-base">Edit Size</h3>
                    <button @click="editingSize = null" class="text-slate-400 hover:text-white cursor-pointer font-bold">✕</button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Nama Size <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-white py-2.5 px-3.5 transition uppercase font-mono font-bold"
                        />
                        <div v-if="editForm.errors.name" class="text-xs text-rose-400 font-semibold mt-1">
                            {{ editForm.errors.name }}
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Urutan Tampilan
                        </label>
                        <input
                            v-model.number="editForm.order"
                            type="number"
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-slate-200 py-2.5 px-3.5 transition font-mono"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="editingSize = null"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2 rounded-xl bg-purple-500 hover:bg-purple-400 text-slate-950 text-xs font-black transition cursor-pointer"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

