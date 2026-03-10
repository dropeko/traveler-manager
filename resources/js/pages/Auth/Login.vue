<!-- filepath: c:\Users\dropeko\Desktop\dev\traveler-manager\resources\js\pages\Auth\Login.vue -->
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { api, ApiError } from '../../lib/http';
import { setToken, setUser, type AuthUser } from '../../lib/auth';

type LoginResponse = {
    access_token: string;
    expires_in: number;
    user: AuthUser;
};

const form = ref({
    email: '',
    password: '',
});

const loading = ref(false);
const errorMessage = ref<string | null>(null);

const canSubmit = computed(() => form.value.email.trim().length > 0 && form.value.password.length > 0);

async function submit() {
    if (!canSubmit.value || loading.value) return;

    loading.value = true;
    errorMessage.value = null;

    try {
        const res = await api.post<LoginResponse>('/api/v1/login', {
            email: form.value.email,
            password: form.value.password,
        });

        setToken(res.access_token);
        setUser(res.user);

        router.visit('/travel-orders');
    } catch (e) {
        errorMessage.value = e instanceof ApiError ? e.message : 'Não foi possível entrar. Tente novamente.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Head title="Traveler Manager — Login">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-[#050A16] text-slate-100">
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
            <div
                class="absolute -top-24 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full blur-3xl"
                style="background: radial-gradient(circle at 30% 30%, rgba(96, 165, 250, 0.22), transparent 60%)"
            />
            <div
                class="absolute -bottom-28 right-[-140px] h-[520px] w-[520px] rounded-full blur-3xl"
                style="background: radial-gradient(circle at 30% 30%, rgba(30, 58, 138, 0.32), transparent 60%)"
            />
            <div class="absolute inset-0 bg-[linear-gradient(to_bottom,rgba(2,6,23,0.0),rgba(2,6,23,0.75))]" />
        </div>

        <div class="relative mx-auto flex min-h-screen max-w-6xl flex-col px-6 py-10 lg:py-16">
            <header class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="grid h-10 w-10 place-items-center rounded-xl border border-slate-800 bg-slate-950/60">
                        <span class="text-sm font-semibold tracking-tight text-sky-300">TM</span>
                    </div>
                    <div class="leading-tight">
                        <p class="text-sm font-semibold text-slate-100">Traveler Manager</p>
                        <p class="text-xs text-slate-400">Acesso à plataforma</p>
                    </div>
                </div>

                <nav class="flex items-center gap-2">
                    <Link
                        href="/"
                        class="rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-900/50 hover:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                    >
                        Voltar
                    </Link>
                </nav>
            </header>

            <main class="flex flex-1 items-center justify-center py-10">
                <div class="w-full max-w-md">
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-6 shadow-sm backdrop-blur">
                        <h1 class="text-xl font-semibold tracking-tight text-slate-50">Entrar</h1>
                        <p class="mt-2 text-sm text-slate-400">
                            Seed: <code>admin@example.com</code> / <code>123</code>
                        </p>

                        <div
                            v-if="errorMessage"
                            class="mt-4 rounded-xl border border-rose-900/60 bg-rose-950/30 p-3 text-sm text-rose-200"
                        >
                            {{ errorMessage }}
                        </div>

                        <form class="mt-5 space-y-4" @submit.prevent="submit">
                            <div>
                                <label class="text-sm font-medium text-slate-200" for="email">E-mail</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="email"
                                    class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                                    placeholder="voce@empresa.com"
                                />
                            </div>

                            <div>
                                <label class="text-sm font-medium text-slate-200" for="password">Senha</label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    autocomplete="current-password"
                                    class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                                    placeholder="Sua senha"
                                />
                            </div>

                            <button
                                type="submit"
                                :disabled="!canSubmit || loading"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-sky-300/70"
                            >
                                {{ loading ? 'Entrando...' : 'Entrar' }}
                            </button>

                            <p class="text-center text-xs text-slate-500">
                                O token JWT é salvo no navegador e usado automaticamente nas chamadas da API.
                            </p>
                        </form>
                    </div>
                </div>
            </main>

            <footer class="border-t border-slate-900/70 pt-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">Traveler Manager • Dark mode</p>
                    <p class="text-xs text-slate-500"><span class="text-slate-300">API:</span> <code>/api/v1</code></p>
                </div>
            </footer>
        </div>
    </div>
</template>