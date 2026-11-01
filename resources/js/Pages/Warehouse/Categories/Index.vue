<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    categories: Array,
});

// Form Tambah Kategori
const createForm = useForm({
    name: '',
});

const submitCreate = () => {
    createForm.post(route('warehouse.categories.store'), {
        onSuccess: () => createForm.reset(),
    });
};

// State & Form Edit Kategori
const editingCategory = ref(null);
const editForm = useForm({
    name: '',
});

const openEditModal = (cat) => {
    editingCategory.value = cat;
    editForm.name = cat.name;
};

const submitEdit = () => {
    if (!editingCategory.value) return;
    editForm.put(route('warehouse.categories.update', editingCategory.value.id), {
        onSuccess: () => {
            editingCategory.value = null;
            editForm.reset();
        },
    });
};

const deleteCategory = (cat) => {
    if (confirm(`Yakin ingin menghapus kategori '${cat.name}'?`)) {
        router.delete(route('warehouse.categories.destroy', cat.id));
    }
};
</script>

<template>
    <Head title="Kategori Produk - Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                    Kategori Produk
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Kelola nama-nama kategori untuk pengelompokan produk distro.
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

                <!-- 1. FORM TAMBAH KATEGORI (KIRI) -->
                <div class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 sm:p-6 space-y-4 h-fit">
                    <div>
                        <h3 class="font-bold text-white text-base">Tambah Kategori</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Ketik nama kategori baru yang ingin ditambahkan.</p>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4 pt-1">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Nama Kategori <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                placeholder="Contoh: Kaos, Celana, Jaket, Topi"
                                required
                                class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-white py-2.5 px-3.5 transition placeholder-slate-600 font-semibold"
                            />
                            <div v-if="createForm.errors.name" class="text-xs text-rose-400 font-semibold mt-1">
                                {{ createForm.errors.name }}
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="createForm.processing || !createForm.name.trim()"
                            class="w-full py-2.5 px-4 rounded-xl bg-purple-500 hover:bg-purple-400 disabled:opacity-50 text-slate-950 text-xs font-black shadow-lg shadow-purple-500/20 transition cursor-pointer"
                        >
                            {{ createForm.processing ? 'Menyimpan...' : '+ Simpan Kategori' }}
                        </button>
                    </form>
                </div>

                <!-- 2. TABEL DAFTAR KATEGORI (KANAN) -->
                <div class="lg:col-span-2 bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 overflow-hidden shadow-xl">
                    <div class="p-5 sm:p-6 border-b border-slate-800/80">
                        <h3 class="font-bold text-white text-base">Daftar Kategori</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Total {{ categories.length }} kategori aktif di sistem.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-950/60 text-slate-400 border-b border-slate-800 uppercase tracking-wider font-semibold">
                                <tr>
                                    <th class="p-4 sm:p-5 w-16 text-center">No</th>
                                    <th class="p-4 sm:p-5">Nama Kategori</th>
                                    <th class="p-4 sm:p-5 text-center w-36">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                                <tr v-for="(cat, idx) in categories" :key="cat.id" class="hover:bg-slate-800/30 transition">
                                    <td class="p-4 sm:p-5 text-center text-slate-500 font-mono text-xs">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="p-4 sm:p-5 font-bold text-white text-sm">
                                        {{ cat.name }}
                                    </td>
                                    <td class="p-4 sm:p-5 text-center space-x-2 whitespace-nowrap">
                                        <button
                                            @click="openEditModal(cat)"
                                            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition cursor-pointer"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteCategory(cat)"
                                            class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-rose-500/15 hover:text-rose-400 text-slate-500 text-xs font-semibold border border-slate-800 hover:border-rose-500/30 transition cursor-pointer"
                                            title="Hapus Kategori"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="categories.length === 0">
                                    <td colspan="3" class="p-8 text-center text-slate-500">
                                        Belum ada kategori yang dibuat.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

        <!-- MODAL EDIT KATEGORI -->
        <div
            v-if="editingCategory"
            class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
            @click.self="editingCategory = null"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h4 class="font-bold text-white text-sm">Edit Nama Kategori</h4>
                    <button @click="editingCategory = null" class="text-slate-400 hover:text-white cursor-pointer p-1">✕</button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Nama Kategori
                        </label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full text-sm rounded-xl border-slate-800 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 bg-slate-950 text-white py-2.5 px-3.5 transition font-semibold"
                        />
                        <div v-if="editForm.errors.name" class="text-xs text-rose-400 font-semibold mt-1">
                            {{ editForm.errors.name }}
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                        <button
                            type="button"
                            @click="editingCategory = null"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing || !editForm.name.trim()"
                            class="px-5 py-2 rounded-xl bg-purple-500 hover:bg-purple-400 disabled:opacity-50 text-slate-950 text-xs font-black shadow-md transition cursor-pointer"
                        >
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
