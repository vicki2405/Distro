<script setup>
import { ref } from 'vue';

const count = ref(0);
const increment = () => count.value++;
const decrement = () => {
    if (count.value > 0) count.value--;
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col items-center justify-center p-6">
        <div class="max-w-md w-full bg-white border border-slate-200 rounded-2xl shadow-xl p-8 space-y-6 text-center">
            <!-- Header Badges -->
            <div class="flex items-center justify-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Vue 3 Aktif
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 border border-sky-300">
                    Tailwind v4 Aktif
                </span>
            </div>

            <!-- Title & Description -->
            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight">Laravel + Vue 3 + Tailwind CSS</h1>
                <p class="text-sm text-slate-500">
                    Konfigurasi berhasil diperbaiki. Komponen Vue Single-File Component (SFC) dan utility Tailwind CSS siap digunakan.
                </p>
            </div>

            <!-- Interactive Counter Box -->
            <div class="p-6 bg-slate-50 rounded-xl border border-slate-100 space-y-4">
                <p class="text-xs uppercase tracking-wider font-semibold text-slate-400">
                    Uji Reaktivitas Vue
                </p>
                <div class="text-5xl font-black text-slate-900 tabular-nums">
                    {{ count }}
                </div>
                <div class="flex items-center justify-center gap-3 pt-2">
                    <button
                        @click="decrement"
                        class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium transition cursor-pointer"
                    >
                        - Kurang
                    </button>
                    <button
                        @click="increment"
                        class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-medium shadow-md shadow-emerald-600/20 transition cursor-pointer"
                    >
                        + Tambah
                    </button>
                </div>
            </div>

            <!-- Footer Info -->
            <div class="pt-2 text-xs text-slate-400">
                File komponen: <code class="px-1.5 py-0.5 rounded bg-slate-100 font-mono text-[11px] text-slate-600">resources/js/App.vue</code>
            </div>
        </div>
    </div>
</template>

