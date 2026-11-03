<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    roles: Array,
    permissionsGrouped: Object,
});

// Helper labels permissions
const permissionLabels = {
    'products.view': 'Lihat Katalog Produk',
    'products.create': 'Tambah Produk Baru',
    'products.edit': 'Ubah Data Produk & Harga',
    'products.delete': 'Hapus Produk',
    'categories.view': 'Lihat Kategori',
    'categories.manage': 'Kelola Kategori',
    'stock.view': 'Lihat Riwayat & Level Stok',
    'stock.in': 'Input Barang Masuk',
    'stock.out': 'Input Barang Keluar',
    'stock.opname': 'Audit Stock Opname',
    'users.manage': 'Kelola Akun Pengguna',
    'roles.manage': 'Kelola Role & Hak Akses',
};

// Modal Tambah Role
const isCreateModalOpen = ref(false);
const createForm = useForm({
    name: '',
    permissions: [],
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.permissions = [];
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('warehouse.roles.store'), {
        onSuccess: () => closeCreateModal(),
    });
};

// Modal Edit Role
const isEditModalOpen = ref(false);
const editingRole = ref(null);
const editForm = useForm({
    name: '',
    permissions: [],
});

const openEditModal = (role) => {
    editingRole.value = role;
    editForm.reset();
    editForm.clearErrors();
    editForm.name = role.name;
    editForm.permissions = role.permissions ? role.permissions.map((p) => p.name) : [];
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editingRole.value = null;
    editForm.reset();
};

const submitEdit = () => {
    if (!editingRole.value) return;
    editForm.put(route('warehouse.roles.update', editingRole.value.id), {
        onSuccess: () => closeEditModal(),
    });
};

const deleteRole = (role) => {
    if (role.name === 'owner' || role.name === 'staff') {
        alert(`Peran '${role.name}' adalah peran sistem bawaan dan tidak dapat dihapus.`);
        return;
    }
    if (role.users_count > 0) {
        alert(`Peran '${role.name}' masih digunakan oleh ${role.users_count} pengguna. Pindahkan pengguna terlebih dahulu.`);
        return;
    }
    if (confirm(`Apakah Anda yakin ingin menghapus peran '${role.name}'? Tindakan ini tidak dapat dibatalkan.`)) {
        router.delete(route('warehouse.roles.destroy', role.id));
    }
};

// Toggle all permissions in a group for Create
const toggleGroupCreate = (groupPerms) => {
    const permNames = groupPerms.map((p) => p.name);
    const allSelected = permNames.every((p) => createForm.permissions.includes(p));
    if (allSelected) {
        createForm.permissions = createForm.permissions.filter((p) => !permNames.includes(p));
    } else {
        createForm.permissions = Array.from(new Set([...createForm.permissions, ...permNames]));
    }
};

// Toggle all permissions in a group for Edit
const toggleGroupEdit = (groupPerms) => {
    const permNames = groupPerms.map((p) => p.name);
    const allSelected = permNames.every((p) => editForm.permissions.includes(p));
    if (allSelected) {
        editForm.permissions = editForm.permissions.filter((p) => !permNames.includes(p));
    } else {
        editForm.permissions = Array.from(new Set([...editForm.permissions, ...permNames]));
    }
};
</script>

<template>
    <Head title="Role & Hak Akses - Distro Ops" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span>🛡️</span>
                        <span>Role & Hak Akses (Permissions)</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Definisikan peran staf dan atur hak izin operasional gudang secara terperinci.
                    </p>
                </div>
                <div>
                    <button
                        @click="openCreateModal"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs transition shadow-md shadow-amber-500/20 cursor-pointer active:scale-95"
                    >
                        <span>➕</span>
                        <span>Tambah Peran Baru</span>
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">

            <!-- Flash Alert -->
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

            <!-- DAFTAR KARTU ROLE -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="role in roles"
                    :key="role.id"
                    class="bg-slate-900/70 backdrop-blur-md rounded-2xl border border-slate-800/80 p-5 flex flex-col justify-between space-y-4 hover:border-slate-700/80 transition shadow-xl"
                >
                    <div>
                        <!-- Header Role Card -->
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">{{ role.name === 'owner' ? '👑' : '🛡️' }}</span>
                                    <h3 class="text-base font-black text-white capitalize tracking-tight">
                                        {{ role.name }}
                                    </h3>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-1">
                                    {{ role.users_count }} pengguna terdaftar
                                </div>
                            </div>
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wide uppercase',
                                    role.name === 'owner'
                                        ? 'bg-amber-500/20 text-amber-400 border border-amber-500/40'
                                        : 'bg-sky-500/20 text-sky-400 border border-sky-500/40'
                                ]"
                            >
                                {{ role.name }}
                            </span>
                        </div>

                        <!-- Izin Akses Summary -->
                        <div class="mt-4 pt-3 border-t border-slate-800/80 space-y-2">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                <span>Hak Izin Terpasang</span>
                                <span class="text-amber-400 font-mono">
                                    {{ role.name === 'owner' ? 'Akses Penuh (All)' : `${role.permissions?.length || 0} Izin` }}
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-1.5 max-h-40 overflow-y-auto pr-1">
                                <template v-if="role.name === 'owner'">
                                    <span class="px-2 py-0.5 rounded-lg bg-amber-500/10 text-amber-300 text-[10px] font-semibold border border-amber-500/20">
                                        ⭐ Seluruh Akses Gudang & Sistem
                                    </span>
                                </template>
                                <template v-else-if="role.permissions && role.permissions.length > 0">
                                    <span
                                        v-for="p in role.permissions"
                                        :key="p.id"
                                        class="px-2 py-0.5 rounded-lg bg-slate-800/80 text-slate-300 text-[10px] font-medium border border-slate-700/60"
                                        :title="p.name"
                                    >
                                        {{ permissionLabels[p.name] || p.name }}
                                    </span>
                                </template>
                                <template v-else>
                                    <span class="text-[11px] text-slate-500 italic">
                                        Belum ada hak izin yang diberikan.
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Bawah Card -->
                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-end gap-2">
                        <button
                            @click="openEditModal(role)"
                            class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs transition cursor-pointer flex items-center gap-1.5"
                        >
                            <span>✏️</span>
                            <span>Atur Hak Akses</span>
                        </button>
                        <button
                            v-if="role.name !== 'owner' && role.name !== 'staff'"
                            @click="deleteRole(role)"
                            class="px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-semibold text-xs transition cursor-pointer flex items-center gap-1"
                        >
                            <span>🗑️</span>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <!-- =================== MODAL TAMBAH ROLE =================== -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs overflow-y-auto"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl p-6 space-y-5 shadow-2xl my-8 max-h-[90vh] flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="font-bold text-white text-base flex items-center gap-2">
                        <span>➕</span>
                        <span>Tambah Peran & Matriks Hak Akses</span>
                    </h3>
                    <button @click="closeCreateModal" class="text-slate-400 hover:text-white p-1">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-5 overflow-y-auto pr-1">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Nama Peran (Role) <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="createForm.name"
                            type="text"
                            placeholder="Contoh: Kepala Gudang, Kasir Toko, Auditor"
                            required
                            class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition placeholder-slate-600 font-semibold"
                        />
                        <div v-if="createForm.errors.name" class="text-[11px] text-rose-400 font-semibold mt-1">
                            {{ createForm.errors.name }}
                        </div>
                    </div>

                    <!-- MATRIKS CHECKBOX PERMISSIONS PER GRUP -->
                    <div class="space-y-4 pt-2 border-t border-slate-800">
                        <div class="text-xs font-bold uppercase tracking-wider text-amber-400">
                            Pilih Hak Izin Akses (Permissions)
                        </div>

                        <div
                            v-for="(perms, groupName) in permissionsGrouped"
                            :key="groupName"
                            class="bg-slate-950/60 rounded-xl border border-slate-800/80 p-3.5 space-y-2.5"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-white">{{ groupName }}</span>
                                <button
                                    type="button"
                                    @click="toggleGroupCreate(perms)"
                                    class="text-[10px] text-amber-400 hover:text-amber-300 font-bold transition cursor-pointer"
                                >
                                    Pilih / Batalkan Semua
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                <label
                                    v-for="p in perms"
                                    :key="p.id"
                                    class="flex items-center gap-2 p-2 rounded-lg bg-slate-900/60 hover:bg-slate-800/50 border border-slate-800/50 cursor-pointer transition text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="p.name"
                                        v-model="createForm.permissions"
                                        class="rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-amber-500 w-4 h-4 cursor-pointer"
                                    />
                                    <div class="truncate">
                                        <div class="font-semibold text-slate-200 text-[11px]">
                                            {{ permissionLabels[p.name] || p.name }}
                                        </div>
                                        <div class="text-[9px] text-slate-500 font-mono truncate">
                                            {{ p.name }}
                                        </div>
                                    </div>
                                </label>
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
                            {{ createForm.processing ? 'Menyimpan...' : 'Simpan Peran' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =================== MODAL EDIT ROLE =================== -->
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs overflow-y-auto"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl p-6 space-y-5 shadow-2xl my-8 max-h-[90vh] flex flex-col justify-between">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="font-bold text-white text-base flex items-center gap-2">
                        <span>✏️</span>
                        <span>Atur Hak Akses: {{ editingRole?.name }}</span>
                    </h3>
                    <button @click="closeEditModal" class="text-slate-400 hover:text-white p-1">
                        ✕
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-5 overflow-y-auto pr-1">
                    <div class="space-y-1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Nama Peran (Role) <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            :disabled="editingRole?.name === 'owner'"
                            required
                            class="w-full text-xs rounded-xl border-slate-800 focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-slate-950 text-white py-2.5 px-3.5 transition font-semibold disabled:opacity-50"
                        />
                        <div v-if="editingRole?.name === 'owner'" class="text-[10px] text-amber-400 mt-1">
                            * Nama peran Owner adalah peran inti sistem dan tidak dapat diubah.
                        </div>
                        <div v-if="editForm.errors.name" class="text-[11px] text-rose-400 font-semibold mt-1">
                            {{ editForm.errors.name }}
                        </div>
                    </div>

                    <!-- MATRIKS CHECKBOX PERMISSIONS PER GRUP -->
                    <div class="space-y-4 pt-2 border-t border-slate-800">
                        <div class="text-xs font-bold uppercase tracking-wider text-amber-400">
                            Sesuaikan Hak Izin Akses (Permissions)
                        </div>

                        <div v-if="editingRole?.name === 'owner'" class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs">
                            👑 Peran Owner secara otomatis memiliki seluruh hak izin di sistem.
                        </div>

                        <div
                            v-else
                            v-for="(perms, groupName) in permissionsGrouped"
                            :key="groupName"
                            class="bg-slate-950/60 rounded-xl border border-slate-800/80 p-3.5 space-y-2.5"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-white">{{ groupName }}</span>
                                <button
                                    type="button"
                                    @click="toggleGroupEdit(perms)"
                                    class="text-[10px] text-amber-400 hover:text-amber-300 font-bold transition cursor-pointer"
                                >
                                    Pilih / Batalkan Semua
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                                <label
                                    v-for="p in perms"
                                    :key="p.id"
                                    class="flex items-center gap-2 p-2 rounded-lg bg-slate-900/60 hover:bg-slate-800/50 border border-slate-800/50 cursor-pointer transition text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="p.name"
                                        v-model="editForm.permissions"
                                        class="rounded bg-slate-950 border-slate-700 text-amber-500 focus:ring-amber-500 w-4 h-4 cursor-pointer"
                                    />
                                    <div class="truncate">
                                        <div class="font-semibold text-slate-200 text-[11px]">
                                            {{ permissionLabels[p.name] || p.name }}
                                        </div>
                                        <div class="text-[9px] text-slate-500 font-mono truncate">
                                            {{ p.name }}
                                        </div>
                                    </div>
                                </label>
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
                            {{ editForm.processing ? 'Menyimpan...' : 'Perbarui Hak Akses' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

