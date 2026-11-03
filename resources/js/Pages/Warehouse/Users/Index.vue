<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    users: Object,
    filters: Object,
    stats: Object,
    availableRoles: {
        type: Array,
        default: () => [],
    },
    currentUserId: [Number, String],
});

// State Pencarian & Filter
const search = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');

const handleFilter = () => {
    router.get(
        route('warehouse.users.index'),
        {
            search: search.value || undefined,
            role: roleFilter.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilter = () => {
    search.value = '';
    roleFilter.value = '';
    handleFilter();
};

// Modal Tambah User
const isCreateModalOpen = ref(false);
const createForm = useForm({
    name: '',
    email: '',
    role: 'staff',
    password: '',
    password_confirmation: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('warehouse.users.store'), {
        onSuccess: () => closeCreateModal(),
    });
};

// Modal Edit User
const isEditModalOpen = ref(false);
const editingUser = ref(null);
const editForm = useForm({
    name: '',
    email: '',
    role: 'staff',
    password: '',
    password_confirmation: '',
});

const openEditModal = (user) => {
    editingUser.value = user;
    editForm.reset();
    editForm.clearErrors();
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.roles?.[0]?.name || user.role || 'staff';
    editForm.password = '';
    editForm.password_confirmation = '';
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editingUser.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingUser.value) return;
    editForm.put(route('warehouse.users.update', editingUser.value.id), {
        onSuccess: () => closeEditModal(),
    });
};

// Hapus User
const deleteUser = (user) => {
    if (user.id === props.currentUserId) {
        alert('Anda tidak dapat menghapus akun Anda sendiri.');
        return;
    }
    if (confirm(`Apakah Anda yakin ingin menghapus akun '${user.name}' (${user.email})? Tindakan ini tidak dapat dibatalkan.`)) {
        router.delete(route('warehouse.users.destroy', user.id));
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Kelola Pengguna - Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span>👥</span>
                        <span>Kelola Pengguna & Hak Akses</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Atur siapa saja yang berwenang mengakses operasional gudang dan manajemen distro.
                    </p>
                </div>
                <div>
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition shadow-md shadow-amber-500/20 cursor-pointer active:scale-95"
                    >
                        <span>➕</span>
                        <span>Tambah Pengguna</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Flash & Error Alert -->
            <div
                v-if="$page.props.flash?.success"
                class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800 text-emerald-300 text-xs flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <span>✓</span>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
            </div>
            <div
                v-if="$page.props.flash?.error || $page.props.errors?.error"
                class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800 text-rose-300 text-xs flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ $page.props.flash?.error || $page.props.errors?.error }}</span>
                </div>
            </div>

            <!-- RINGKASAN STATISTIK PENGGUNA -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-slate-900/60 backdrop-blur-md rounded-2xl border border-slate-800/80 p-4 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Pengguna</div>
                        <div class="text-2xl font-black text-white mt-0.5">{{ stats.total }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-slate-800 border border-slate-700/60 flex items-center justify-center text-lg">
                        👥
                    </div>
                </div>

                <div class="bg-slate-900/60 backdrop-blur-md rounded-2xl border border-slate-800/80 p-4 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-amber-400">Akun Owner</div>
                        <div class="text-2xl font-black text-amber-400 mt-0.5">{{ stats.owners }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-lg">
                        👑
                    </div>
                </div>

                <div class="bg-slate-900/60 backdrop-blur-md rounded-2xl border border-slate-800/80 p-4 flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-sky-400">Pegawai Gudang</div>
                        <div class="text-2xl font-black text-sky-400 mt-0.5">{{ stats.staff }}</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-lg">
                        📦
                    </div>
                </div>
            </div>

            <!-- FILTER & PENCARIAN -->
            <div class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 p-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="flex-1 w-full sm:w-auto flex flex-col sm:flex-row gap-2">
                    <div class="relative flex-1">
                        <input
                            v-model="search"
                            @keyup.enter="handleFilter"
                            type="text"
                            placeholder="Cari berdasarkan nama atau email..."
                            class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2 pl-3 pr-8 transition placeholder-slate-600 font-medium"
                        />
                        <button
                            v-if="search"
                            @click="search = ''; handleFilter()"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white text-xs"
                        >
                            ✕
                        </button>
                    </div>

                    <select
                        v-model="roleFilter"
                        @change="handleFilter"
                        class="text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2 px-3 transition cursor-pointer font-medium capitalize"
                    >
                        <option value="">Semua Peran (Role)</option>
                        <option v-for="r in availableRoles" :key="r.id" :value="r.name">
                            {{ r.name.toUpperCase() }}
                        </option>
                    </select>
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button
                        @click="handleFilter"
                        class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs transition cursor-pointer"
                    >
                        Filter
                    </button>
                    <button
                        v-if="search || roleFilter"
                        @click="resetFilter"
                        class="px-3 py-2 rounded-xl bg-slate-950 hover:bg-slate-900 border border-slate-800 text-slate-400 hover:text-white text-xs transition cursor-pointer"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- TABEL DAFTAR PENGGUNA -->
            <div class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 bg-slate-950/50 text-[10px] font-black uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-4">Pengguna</th>
                                <th class="py-3 px-4">Alamat Email</th>
                                <th class="py-3 px-4">Peran (Role)</th>
                                <th class="py-3 px-4">Terdaftar</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-xs">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                :class="[
                                    'transition hover:bg-slate-800/30',
                                    user.id === currentUserId ? 'bg-amber-500/5' : ''
                                ]"
                            >
                                <!-- Nama & Avatar -->
                                <td class="py-3.5 px-4 font-bold text-white">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs shrink-0"
                                            :class="user.role === 'owner' ? 'bg-amber-500/20 text-amber-400 border border-amber-500/40' : 'bg-slate-800 text-sky-400 border border-slate-700'"
                                        >
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-1.5">
                                                <span>{{ user.name }}</span>
                                                <span
                                                    v-if="user.id === currentUserId"
                                                    class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30"
                                                >
                                                    Anda
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="py-3.5 px-4 text-slate-300 font-mono text-[11px]">
                                    {{ user.email }}
                                </td>

                                <!-- Role Badge -->
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-wrap gap-1">
                                        <template v-if="user.roles && user.roles.length > 0">
                                            <span
                                                v-for="r in user.roles"
                                                :key="r.id"
                                                :class="[
                                                    'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wide uppercase',
                                                    r.name === 'owner'
                                                        ? 'bg-amber-500/20 text-amber-400 border border-amber-500/40'
                                                        : 'bg-sky-500/20 text-sky-400 border border-sky-500/40'
                                                ]"
                                            >
                                                <span v-if="r.name === 'owner'">👑</span>
                                                <span v-else>🛡️</span>
                                                <span>{{ r.name }}</span>
                                            </span>
                                        </template>
                                        <span
                                            v-else
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wide uppercase',
                                                user.role === 'owner'
                                                    ? 'bg-amber-500/20 text-amber-400 border border-amber-500/40'
                                                    : 'bg-sky-500/20 text-sky-400 border border-sky-500/40'
                                            ]"
                                        >
                                            <span v-if="user.role === 'owner'">👑</span>
                                            <span v-else>📦</span>
                                            <span>{{ user.role }}</span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Tanggal Terdaftar -->
                                <td class="py-3.5 px-4 text-slate-400 text-[11px]">
                                    {{ formatDate(user.created_at) }}
                                </td>

                                <!-- Tombol Aksi -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openEditModal(user)"
                                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-semibold transition cursor-pointer"
                                            title="Edit Data / Reset Password"
                                        >
                                            ✏️ Edit
                                        </button>
                                        <button
                                            v-if="user.id !== currentUserId"
                                            @click="deleteUser(user)"
                                            class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-semibold transition cursor-pointer"
                                            title="Hapus Akun Pengguna"
                                        >
                                            🗑️ Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-500 text-xs">
                                    Tidak ada pengguna yang cocok dengan kriteria pencarian.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="users.links && users.links.length > 3"
                    class="p-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400"
                >
                    <div>
                        Menampilkan {{ users.from || 0 }} - {{ users.to || 0 }} dari {{ users.total }} pengguna
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, i) in users.links" :key="i">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url, {}, { preserveState: true })"
                                :class="[
                                    'px-2.5 py-1 rounded-lg font-semibold transition',
                                    link.active ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                                ]"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-2.5 py-1 text-slate-600 cursor-not-allowed"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <!-- =================== MODAL TAMBAH PENGGUNA =================== -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="font-bold text-white text-base flex items-center gap-2">
                        <span>➕</span>
                        <span>Tambah Pengguna Baru</span>
                    </h3>
                    <button @click="closeCreateModal" class="text-slate-400 hover:text-white p-1">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Nama Lengkap <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Contoh: Budi Pratama"
                            required
                            class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition placeholder-slate-600 font-semibold"
                        />
                        <div v-if="createForm.errors.name" class="text-[11px] text-rose-400 font-semibold mt-1">
                            {{ createForm.errors.name }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Alamat Email <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="createForm.email"
                            type="email"
                            placeholder="Contoh: budi@distro.com"
                            required
                            class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition placeholder-slate-600 font-semibold"
                        />
                        <div v-if="createForm.errors.email" class="text-[11px] text-rose-400 font-semibold mt-1">
                            {{ createForm.errors.email }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Hak Akses / Peran (Role) <span class="text-rose-400">*</span>
                        </label>
                        <select
                            v-model="createForm.role"
                            class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition font-semibold capitalize"
                        >
                            <option v-for="r in availableRoles" :key="r.id" :value="r.name">
                                {{ r.name.toUpperCase() }}
                            </option>
                        </select>
                        <div v-if="createForm.errors.role" class="text-[11px] text-rose-400 font-semibold mt-1">
                            {{ createForm.errors.role }}
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Kata Sandi <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="createForm.password"
                                type="password"
                                placeholder="Minimal 8 karakter"
                                required
                                class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition placeholder-slate-600 font-semibold"
                            />
                            <div v-if="createForm.errors.password" class="text-[11px] text-rose-400 font-semibold mt-1">
                                {{ createForm.errors.password }}
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                                Konfirmasi Sandi <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="createForm.password_confirmation"
                                type="password"
                                placeholder="Ulangi kata sandi"
                                required
                                class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition placeholder-slate-600 font-semibold"
                            />
                            <div v-if="createForm.errors.password_confirmation" class="text-[11px] text-rose-400 font-semibold mt-1">
                                {{ createForm.errors.password_confirmation }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan Pengguna' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =================== MODAL EDIT PENGGUNA =================== -->
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 space-y-5 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="font-bold text-white text-base flex items-center gap-2">
                        <span>✏️</span>
                        <span>Edit Pengguna: {{ editingUser?.name }}</span>
                    </h3>
                    <button @click="closeEditModal" class="text-slate-400 hover:text-white p-1">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Nama Lengkap <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition font-semibold"
                        />
                        <div v-if="editForm.errors.name" class="text-[11px] text-rose-400 font-semibold mt-1">
                            {{ editForm.errors.name }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Alamat Email <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="editForm.email"
                            type="email"
                            required
                            class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition font-semibold"
                        />
                        <div v-if="editForm.errors.email" class="text-[11px] text-rose-400 font-semibold mt-1">
                            {{ editForm.errors.email }}
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Hak Akses / Peran (Role) <span class="text-rose-400">*</span>
                        </label>
                        <select
                            v-model="editForm.role"
                            :disabled="editingUser?.id === currentUserId"
                            class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition font-semibold capitalize disabled:opacity-50"
                        >
                            <option v-for="r in availableRoles" :key="r.id" :value="r.name">
                                {{ r.name.toUpperCase() }}
                            </option>
                        </select>
                        <div v-if="editingUser?.id === currentUserId" class="text-[10px] text-amber-400 mt-1">
                            * Anda tidak dapat menurunkan peran akun Anda sendiri.
                        </div>
                        <div v-if="editForm.errors.role" class="text-[11px] text-rose-400 font-semibold mt-1">
                            {{ editForm.errors.role }}
                        </div>
                    </div>

                    <!-- OPSIONAL: GANTI PASSWORD -->
                    <div class="pt-2 border-t border-slate-800/80 space-y-3">
                        <div>
                            <div class="text-xs font-bold text-white">Reset Kata Sandi (Opsional)</div>
                            <div class="text-[11px] text-slate-400">Kosongkan jika tidak ingin mengubah kata sandi pengguna ini.</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-300">Kata Sandi Baru</label>
                                <input
                                    v-model="editForm.password"
                                    type="password"
                                    placeholder="Biarkan kosong jika tidak diubah"
                                    class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2 px-3 transition placeholder-slate-600"
                                />
                                <div v-if="editForm.errors.password" class="text-[11px] text-rose-400 font-semibold mt-1">
                                    {{ editForm.errors.password }}
                                </div>
                            </div>

                            <div class="space-y-1">
                                <label class="block text-[11px] font-bold text-slate-300">Konfirmasi Sandi Baru</label>
                                <input
                                    v-model="editForm.password_confirmation"
                                    type="password"
                                    placeholder="Ulangi sandi baru"
                                    class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2 px-3 transition placeholder-slate-600"
                                />
                                <div v-if="editForm.errors.password_confirmation" class="text-[11px] text-rose-400 font-semibold mt-1">
                                    {{ editForm.errors.password_confirmation }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition cursor-pointer"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition cursor-pointer disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'Menyimpan...' : 'Perbarui Pengguna' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

