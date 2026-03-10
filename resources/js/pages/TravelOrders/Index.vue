<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { api, ApiError } from '../../lib/http';
import { clearAuth, getToken, getUser } from '../../lib/auth';

type TravelOrder = {
    order_code: string;
    requester_name: string;
    destination: string;
    departure_date: string;
    return_date: string;
    status: 'requested' | 'approved' | 'cancelled' | string;
    created_at?: string;
};

type ListResponse = {
    data: TravelOrder[];
};

const user = getUser();
const loading = ref(true);
const errorMessage = ref<string | null>(null);
const orders = ref<TravelOrder[]>([]);

function statusLabel(s: TravelOrder['status']) {
    if (s === 'requested') return 'Solicitado';
    if (s === 'approved') return 'Aprovado';
    if (s === 'cancelled') return 'Cancelado';
    return s;
}

function statusClasses(s: TravelOrder['status']) {
    if (s === 'approved') return 'border-emerald-900/50 bg-emerald-950/30 text-emerald-200';
    if (s === 'cancelled') return 'border-rose-900/50 bg-rose-950/30 text-rose-200';
    return 'border-sky-900/50 bg-sky-950/30 text-sky-200';
}

async function load() {
    loading.value = true;
    errorMessage.value = null;

    try {
        const res = await api.get<ListResponse>('/api/v1/travel-orders');
        orders.value = res.data ?? [];
    } catch (e) {
        errorMessage.value = e instanceof ApiError ? e.message : 'Não foi possível carregar os pedidos.';
    } finally {
        loading.value = false;
    }
}

function logout() {
    clearAuth();
    router.visit('/login');
}

onMounted(async () => {
    if (!getToken()) {
        router.visit('/login');
        return;
    }
    await load();
});
</script>

<template>
    <Head title="Traveler Manager — Travel Orders" />

    <div class="min-h-screen bg-[#050A16] text-slate-100">
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
            <div
                class="absolute -top-24 left-1/2 h-[520px] w-[520px] -translate-x-1/2 rounded-full blur-3xl"
                style="background: radial-gradient(circle at 30% 30%, rgba(96, 165, 250, 0.18), transparent 60%)"
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
                        <p class="text-sm font-semibold text-slate-100">Travel Orders</p>
                        <p class="text-xs text-slate-400">
                            {{ user?.name ? `Olá, ${user.name}` : 'Listagem' }}
                        </p>
                    </div>
                </div>

                <nav class="flex items-center gap-2">
                    <Link
                        href="/"
                        class="rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-900/50 hover:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                    >
                        Início
                    </Link>
                    <button
                        type="button"
                        @click="logout"
                        class="rounded-lg px-3 py-2 text-sm text-slate-300 transition hover:bg-slate-900/50 hover:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                    >
                        Sair
                    </button>
                </nav>
            </header>

            <main class="mt-8 flex-1">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-semibold text-slate-50">Pedidos de viagem</h1>
                        <p class="mt-1 text-sm text-slate-400">Consome <code>GET /api/v1/travel-orders</code>.</p>
                    </div>

                    <button
                        type="button"
                        @click="load"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-slate-700 hover:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                    >
                        Atualizar
                    </button>
                </div>

                <div
                    v-if="errorMessage"
                    class="mt-5 rounded-2xl border border-rose-900/60 bg-rose-950/30 p-4 text-sm text-rose-200"
                >
                    {{ errorMessage }}
                </div>

                <div class="mt-5 rounded-2xl border border-slate-800 bg-slate-950/50 shadow-sm backdrop-blur">
                    <div class="border-b border-slate-900/70 p-4">
                        <p class="text-sm font-semibold text-slate-100">Lista</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Total: <span class="text-slate-300">{{ orders.length }}</span>
                        </p>
                    </div>

                    <div v-if="loading" class="p-4">
                        <div class="space-y-3">
                            <div class="h-10 rounded-xl bg-slate-900/40" />
                            <div class="h-10 rounded-xl bg-slate-900/30" />
                            <div class="h-10 rounded-xl bg-slate-900/20" />
                        </div>
                    </div>

                    <div v-else class="p-4">
                        <div
                            v-if="orders.length === 0"
                            class="rounded-xl border border-slate-800 bg-slate-950/40 p-4"
                        >
                            <p class="text-sm text-slate-300">Nenhum pedido encontrado.</p>
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="text-xs text-slate-400">
                                    <tr>
                                        <th class="px-3 py-2">Código</th>
                                        <th class="px-3 py-2">Solicitante</th>
                                        <th class="px-3 py-2">Destino</th>
                                        <th class="px-3 py-2">Ida</th>
                                        <th class="px-3 py-2">Volta</th>
                                        <th class="px-3 py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-900/70">
                                    <tr v-for="o in orders" :key="o.order_code">
                                        <td class="px-3 py-3 text-slate-200">{{ o.order_code }}</td>
                                        <td class="px-3 py-3 text-slate-300">{{ o.requester_name }}</td>
                                        <td class="px-3 py-3 text-slate-200">{{ o.destination }}</td>
                                        <td class="px-3 py-3 text-slate-300">{{ o.departure_date }}</td>
                                        <td class="px-3 py-3 text-slate-300">{{ o.return_date }}</td>
                                        <td class="px-3 py-3">
                                            <span
                                                class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
                                                :class="statusClasses(o.status)"
                                            >
                                                {{ statusLabel(o.status) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <footer class="mt-10 border-t border-slate-900/70 pt-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">Traveler Manager • Dark mode</p>
                    <p class="text-xs text-slate-500"><span class="text-slate-300">API:</span> <code>/api/v1</code></p>
                </div>
            </footer>
        </div>
    </div>
</template>