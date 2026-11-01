<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk Sistem Gudang" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center items-center p-4 sm:p-6 selection:bg-amber-500 selection:text-white relative overflow-hidden">
        
        <!-- Ambient Decorative Glow -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-full max-w-md relative z-10 space-y-6">
            
            <!-- Logo & Brand Header -->
            <div class="text-center space-y-2">
                <Link href="/" class="inline-flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-xl bg-amber-500 flex items-center justify-center font-black text-slate-950 text-lg shadow-lg shadow-amber-500/20">
                        D
                    </span>
                    <span class="font-black tracking-tight text-white text-xl">
                        DISTRO <span class="text-amber-500 font-normal">OPERATIONS</span>
                    </span>
                </Link>
                <p class="text-xs text-slate-400">
                    Autentikasi Akses Sistem & Pengawasan Gudang
                </p>
            </div>

            <!-- Login Box Card -->
            <div class="bg-slate-900/80 backdrop-blur-md border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
                
                <!-- Status Message -->
                <div v-if="status" class="p-3 rounded-xl bg-emerald-950/60 border border-emerald-800 text-emerald-400 text-xs">
                    {{ status }}
                </div>

                <!-- Error Flash Alert -->
                <div v-if="form.errors.email || form.errors.password" class="p-3.5 rounded-xl bg-rose-950/60 border border-rose-800 text-rose-400 text-xs space-y-0.5">
                    <div v-if="form.errors.email">{{ form.errors.email }}</div>
                    <div v-if="form.errors.password">{{ form.errors.password }}</div>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email Input -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                            Email Akun
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="nama@distro.com"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full px-4 py-3 rounded-xl bg-slate-950/70 border border-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm text-white placeholder-slate-600 transition"
                        />
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                                Password
                            </label>
                        </div>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                                class="w-full px-4 py-3 pr-11 rounded-xl bg-slate-950/70 border border-slate-800 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm text-white placeholder-slate-600 transition"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 text-xs cursor-pointer"
                            >
                                {{ showPassword ? '🙈' : '👁️' }}
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-500/20"
                            />
                            <span class="text-xs text-slate-400 select-none">Ingat sesi saya</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-slate-950 font-black text-sm tracking-wide shadow-lg shadow-amber-500/20 transition cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span>{{ form.processing ? 'Memverifikasi...' : 'Masuk ke Sistem Gudang' }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>

            </div>

            <!-- Back to Home Link -->
            <div class="text-center">
                <Link href="/" class="text-xs text-slate-500 hover:text-slate-300 transition inline-flex items-center gap-1">
                    ← Kembali ke Halaman Utama
                </Link>
            </div>

        </div>
    </div>
</template>
