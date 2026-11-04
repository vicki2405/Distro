<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    setting: Object,
});

// State Tab Pengaturan
const activeTab = ref('identity'); // 'identity' | 'hero' | 'footer'

// Form Inertia
const form = useForm({
    store_name: props.setting?.store_name || 'DISTRO CATALOG',
    store_tagline: props.setting?.store_tagline || 'APPAREL & STREETWEAR',
    logo: null,
    remove_logo: false,
    hero_badge: props.setting?.hero_badge || 'Rilisan Resmi Katalog Baju Distro',
    hero_title_prefix: props.setting?.hero_title_prefix || 'Eksplorasi Koleksi',
    hero_title_highlight: props.setting?.hero_title_highlight || 'Distro & Streetwear',
    hero_description: props.setting?.hero_description || 'Daftar master artikel pakaian distro dengan ketersediaan stok aktual di rak gudang kami.',
    live_feed_text: props.setting?.live_feed_text || 'LIVE STOCK FEED',
    footer_text: props.setting?.footer_text || '© 2026 Distro Operations • Official Apparel Catalog & Real-Time Logistics Feed',
    whatsapp_number: props.setting?.whatsapp_number || '',
});

// Logo Preview State
const logoFileInput = ref(null);
const localLogoPreview = ref(null);

const onLogoChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        form.remove_logo = false;
        localLogoPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    form.remove_logo = true;
    localLogoPreview.value = null;
    if (logoFileInput.value) {
        logoFileInput.value.value = '';
    }
};

const activeLogoUrl = computed(() => {
    if (form.remove_logo) return null;
    if (localLogoPreview.value) return localLogoPreview.value;
    return props.setting?.logo_url || null;
});

const submit = () => {
    form.post(route('warehouse.settings.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            localLogoPreview.value = null;
        },
    });
};
</script>

<template>
    <Head title="Pengaturan Tampilan & Toko — Distro Ops" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- HEADER HALAMAN -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-800">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-xs font-mono font-semibold mb-2">
                        <span>⚙️ BRANDING & TAMPILAN</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">
                        Pengaturan Tampilan & Dashboard Utama
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Sesuaikan nama toko, logo, teks sambutan, serta seluruh tulisan di halaman depan katalog secara instan tanpa perlu bantuan teknis programmer.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <a
                        :href="route('welcome')"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition active:scale-95 shadow-sm"
                    >
                        <span>Lihat Katalog Depan</span>
                        <span>↗</span>
                    </a>
                </div>
            </div>

            <!-- FLASH MESSAGE SUKSES -->
            <div
                v-if="$page.props.flash?.success"
                class="p-4 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 flex items-center justify-between text-xs sm:text-sm"
            >
                <div class="flex items-center gap-2.5">
                    <span class="text-base">✅</span>
                    <span>{{ $page.props.flash.success }}</span>
                </div>
            </div>

            <!-- LAYOUT UTAMA: FORM DI KIRI & LIVE PREVIEW DI KANAN -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- KOLOM FORM PENGATURAN (7 KOLOM) -->
                <div class="lg:col-span-7 space-y-6">
                    <form @submit.prevent="submit" class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 sm:p-6 space-y-6 shadow-xl">
                        
                        <!-- TAB NAVIGASI CEPAT -->
                        <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-950/70 border border-slate-800 text-xs font-semibold">
                            <button
                                type="button"
                                @click="activeTab = 'identity'"
                                :class="[
                                    'flex-1 py-2 px-3 rounded-lg transition cursor-pointer text-center',
                                    activeTab === 'identity'
                                        ? 'bg-amber-500 text-slate-950 font-bold shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                ]"
                            >
                                🏪 Nama & Logo
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'hero'"
                                :class="[
                                    'flex-1 py-2 px-3 rounded-lg transition cursor-pointer text-center',
                                    activeTab === 'hero'
                                        ? 'bg-amber-500 text-slate-950 font-bold shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                ]"
                            >
                                🎯 Teks Banner Hero
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'footer'"
                                :class="[
                                    'flex-1 py-2 px-3 rounded-lg transition cursor-pointer text-center',
                                    activeTab === 'footer'
                                        ? 'bg-amber-500 text-slate-950 font-bold shadow-sm'
                                        : 'text-slate-400 hover:text-white'
                                ]"
                            >
                                📱 Status & Footer
                            </button>
                        </div>

                        <!-- 1. TAB IDENTITAS & LOGO -->
                        <div v-show="activeTab === 'identity'" class="space-y-4">
                            <div class="border-b border-slate-800 pb-2">
                                <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                                    Identitas Toko & Brand
                                </h2>
                                <p class="text-xs text-slate-400">
                                    Informasi ini muncul di navbar atas, judul tab browser, serta identitas katalog resmi.
                                </p>
                            </div>

                            <!-- Nama Toko / Brand -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Nama Brand / Toko <span class="text-rose-400">*</span>
                                </label>
                                <input
                                    v-model="form.store_name"
                                    type="text"
                                    placeholder="Contoh: DISTRO CATALOG"
                                    class="w-full text-xs sm:text-sm rounded-xl bg-slate-950 border border-slate-800 text-white px-3.5 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                                <div v-if="form.errors.store_name" class="text-rose-400 text-[11px]">
                                    {{ form.errors.store_name }}
                                </div>
                            </div>

                            <!-- Tagline / Subtitle -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Slogan / Tagline Subtitle
                                </label>
                                <input
                                    v-model="form.store_tagline"
                                    type="text"
                                    placeholder="Contoh: APPAREL & STREETWEAR"
                                    class="w-full text-xs sm:text-sm rounded-xl bg-slate-950 border border-slate-800 text-white px-3.5 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                                <p class="text-[11px] text-slate-500">
                                    Teks kecil di bawah nama brand pada navbar utama.
                                </p>
                                <div v-if="form.errors.store_tagline" class="text-rose-400 text-[11px]">
                                    {{ form.errors.store_tagline }}
                                </div>
                            </div>

                            <!-- Logo Toko Upload -->
                            <div class="space-y-2 pt-2">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Logo Gambar Brand
                                </label>
                                
                                <div class="flex items-center gap-4 p-3.5 rounded-xl bg-slate-950 border border-slate-800">
                                    <!-- Preview Logo -->
                                    <div class="w-14 h-14 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center overflow-hidden shrink-0">
                                        <img
                                            v-if="activeLogoUrl"
                                            :src="activeLogoUrl"
                                            alt="Logo Preview"
                                            class="w-full h-full object-contain p-1"
                                        />
                                        <span v-else class="text-xl font-black text-amber-400">
                                            {{ (form.store_name || 'D').charAt(0).toUpperCase() }}
                                        </span>
                                    </div>

                                    <!-- Upload & Reset Actions -->
                                    <div class="space-y-1.5 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <input
                                                ref="logoFileInput"
                                                type="file"
                                                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                                @change="onLogoChange"
                                                class="text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-amber-400 hover:file:bg-slate-700 file:cursor-pointer"
                                            />
                                            <button
                                                v-if="activeLogoUrl"
                                                type="button"
                                                @click="removeLogo"
                                                class="px-2.5 py-1 rounded-lg bg-rose-500/15 hover:bg-rose-500/25 text-rose-400 border border-rose-500/30 text-xs font-medium transition cursor-pointer"
                                            >
                                                Hapus Logo (Gunakan Inisial)
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-slate-500">
                                            Format yang didukung: PNG, JPG, SVG, WEBP (Maksimal 3MB). Jika kosong, sistem otomatis memakai monogram huruf depan toko.
                                        </p>
                                        <div v-if="form.errors.logo" class="text-rose-400 text-[11px]">
                                            {{ form.errors.logo }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. TAB TEKS HERO DASHBOARD -->
                        <div v-show="activeTab === 'hero'" class="space-y-4">
                            <div class="border-b border-slate-800 pb-2">
                                <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                                    Teks Banner Hero Utama
                                </h2>
                                <p class="text-xs text-slate-400">
                                    Semua tulisan sambutan dan penawaran yang pertama kali dilihat pengunjung katalog.
                                </p>
                            </div>

                            <!-- Badge Promosi -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Teks Badge Promosi (Atas Judul)
                                </label>
                                <input
                                    v-model="form.hero_badge"
                                    type="text"
                                    placeholder="Contoh: Rilisan Resmi Katalog Baju Distro"
                                    class="w-full text-xs sm:text-sm rounded-xl bg-slate-950 border border-slate-800 text-white px-3.5 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                                <div v-if="form.errors.hero_badge" class="text-rose-400 text-[11px]">
                                    {{ form.errors.hero_badge }}
                                </div>
                            </div>

                            <!-- Judul Baris 1 -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Judul Utama (Baris 1) <span class="text-rose-400">*</span>
                                </label>
                                <input
                                    v-model="form.hero_title_prefix"
                                    type="text"
                                    placeholder="Contoh: Eksplorasi Koleksi"
                                    class="w-full text-xs sm:text-sm rounded-xl bg-slate-950 border border-slate-800 text-white px-3.5 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                                <div v-if="form.errors.hero_title_prefix" class="text-rose-400 text-[11px]">
                                    {{ form.errors.hero_title_prefix }}
                                </div>
                            </div>

                            <!-- Judul Highlight Berwarna -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Judul Highlight Berwarna (Baris 2) <span class="text-rose-400">*</span>
                                </label>
                                <input
                                    v-model="form.hero_title_highlight"
                                    type="text"
                                    placeholder="Contoh: Distro & Streetwear"
                                    class="w-full text-xs sm:text-sm rounded-xl bg-slate-950 border border-slate-800 text-white px-3.5 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                                <p class="text-[11px] text-slate-500">
                                    Bagian ini otomatis memiliki efek warna gradien amber/oranye yang memikat mata.
                                </p>
                                <div v-if="form.errors.hero_title_highlight" class="text-rose-400 text-[11px]">
                                    {{ form.errors.hero_title_highlight }}
                                </div>
                            </div>

                            <!-- Deskripsi Singkat Hero -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Deskripsi Sambutan Singkat
                                </label>
                                <textarea
                                    v-model="form.hero_description"
                                    rows="3"
                                    placeholder="Tuliskan deskripsi ringkas tentang katalog atau toko Anda..."
                                    class="w-full text-xs sm:text-sm rounded-xl bg-slate-950 border border-slate-800 text-white px-3.5 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 leading-relaxed"
                                ></textarea>
                                <div v-if="form.errors.hero_description" class="text-rose-400 text-[11px]">
                                    {{ form.errors.hero_description }}
                                </div>
                            </div>
                        </div>

                        <!-- 3. TAB STATUS & FOOTER -->
                        <div v-show="activeTab === 'footer'" class="space-y-4">
                            <div class="border-b border-slate-800 pb-2">
                                <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                                    Status Bilah & Informasi Kontak
                                </h2>
                                <p class="text-xs text-slate-400">
                                    Label radar live feed di navbar, nomor WhatsApp order, dan teks hak cipta footer.
                                </p>
                            </div>

                            <!-- Teks Live Feed -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Teks Indikator Live Feed Navbar
                                </label>
                                <input
                                    v-model="form.live_feed_text"
                                    type="text"
                                    placeholder="Contoh: LIVE STOCK FEED"
                                    class="w-full text-xs sm:text-sm rounded-xl bg-slate-950 border border-slate-800 text-white px-3.5 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                                <div v-if="form.errors.live_feed_text" class="text-rose-400 text-[11px]">
                                    {{ form.errors.live_feed_text }}
                                </div>
                            </div>

                            <!-- Nomor WhatsApp -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Nomor WhatsApp Kontak / Order (Opsional)
                                </label>
                                <input
                                    v-model="form.whatsapp_number"
                                    type="text"
                                    placeholder="Contoh: 6281234567890 (Gunakan format 62...)"
                                    class="w-full text-xs sm:text-sm rounded-xl bg-slate-950 border border-slate-800 text-white px-3.5 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 font-mono"
                                />
                                <p class="text-[11px] text-slate-500">
                                    Pelanggan dapat langsung menghubungi WhatsApp ini untuk menanyakan produk.
                                </p>
                                <div v-if="form.errors.whatsapp_number" class="text-rose-400 text-[11px]">
                                    {{ form.errors.whatsapp_number }}
                                </div>
                            </div>

                            <!-- Teks Hak Cipta Footer -->
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-300">
                                    Teks Footer Hak Cipta
                                </label>
                                <input
                                    v-model="form.footer_text"
                                    type="text"
                                    placeholder="Contoh: © 2026 Distro Operations • Official Apparel Catalog"
                                    class="w-full text-xs sm:text-sm rounded-xl bg-slate-950 border border-slate-800 text-white px-3.5 py-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                                />
                                <div v-if="form.errors.footer_text" class="text-rose-400 text-[11px]">
                                    {{ form.errors.footer_text }}
                                </div>
                            </div>
                        </div>

                        <!-- TOMBOL SUBMIT SIMPAN -->
                        <div class="pt-4 border-t border-slate-800 flex items-center justify-between gap-3">
                            <span class="text-xs text-slate-500">
                                Perubahan akan langsung tercermin di dashboard katalog.
                            </span>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-slate-950 font-black text-xs transition shadow-lg shadow-amber-500/25 active:scale-95 disabled:opacity-50 cursor-pointer"
                            >
                                <span v-if="form.processing" class="animate-spin text-sm">⏳</span>
                                <span v-else>💾</span>
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                            </button>
                        </div>

                    </form>
                </div>

                <!-- KOLOM LIVE PREVIEW (5 KOLOM) -->
                <div class="lg:col-span-5 space-y-4 lg:sticky lg:top-4">
                    <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 shadow-xl space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <h3 class="text-xs font-bold text-white uppercase tracking-wider font-mono">
                                    Simulasi Pratinjau Tampilan
                                </h3>
                            </div>
                            <span class="text-[10px] text-amber-400 font-mono">Real-Time Sync</span>
                        </div>

                        <!-- SIMULASI NAVBAR -->
                        <div class="space-y-1.5">
                            <div class="text-[10px] uppercase font-bold text-slate-400">
                                Pratinjau Navbar Header
                            </div>
                            <div class="p-3 rounded-xl bg-[#152136] border border-slate-700/70 flex items-center justify-between gap-2 shadow-inner">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center font-black text-slate-950 text-xs overflow-hidden shrink-0 shadow">
                                        <img
                                            v-if="activeLogoUrl"
                                            :src="activeLogoUrl"
                                            class="w-full h-full object-contain p-0.5"
                                        />
                                        <span v-else>
                                            {{ (form.store_name || 'D').charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-black text-xs text-white leading-tight">
                                            {{ form.store_name || 'DISTRO CATALOG' }}
                                        </div>
                                        <div class="text-[8px] text-slate-300 font-mono">
                                            {{ form.store_tagline || 'APPAREL & STREETWEAR' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-800 text-[9px] font-mono text-slate-200 border border-slate-650">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                    <span>{{ form.live_feed_text || 'LIVE STOCK FEED' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- SIMULASI HERO BANNER -->
                        <div class="space-y-1.5">
                            <div class="text-[10px] uppercase font-bold text-slate-400">
                                Pratinjau Hero Banner Utama
                            </div>
                            <div class="p-4 rounded-xl bg-gradient-to-br from-[#131d2e] via-[#1a273f] to-[#121c2d] border border-slate-700/60 space-y-2.5">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/15 border border-amber-500/35 text-amber-300 text-[9px] font-mono font-semibold">
                                    <span>✨</span>
                                    <span>{{ form.hero_badge || 'Rilisan Resmi Katalog Baju Distro' }}</span>
                                </div>
                                <div>
                                    <h4 class="text-base font-black text-white leading-snug">
                                        {{ form.hero_title_prefix || 'Eksplorasi Koleksi' }} <br />
                                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-amber-400 to-orange-400">
                                            {{ form.hero_title_highlight || 'Distro & Streetwear' }}
                                        </span>
                                    </h4>
                                    <p class="text-[11px] text-slate-300 mt-1 leading-relaxed line-clamp-3">
                                        {{ form.hero_description || 'Daftar master artikel pakaian distro dengan ketersediaan stok aktual di rak gudang kami.' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- SIMULASI FOOTER -->
                        <div class="space-y-1.5">
                            <div class="text-[10px] uppercase font-bold text-slate-400">
                                Pratinjau Footer
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-950/80 border border-slate-800 text-center text-[10px] text-slate-400">
                                {{ form.footer_text || '© 2026 Distro Operations • Official Apparel Catalog & Real-Time Logistics Feed' }}
                            </div>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>

