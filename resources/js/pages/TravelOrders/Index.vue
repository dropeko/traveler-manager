<!-- filepath: c:\Users\dropeko\Desktop\dev\traveler-manager\resources\js\pages\TravelOrders\Index.vue -->
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { api, ApiError } from '../../lib/http';
import { clearAuth, getToken, getUser } from '../../lib/auth';
import CreateTravelOrderModal from './components/CreateTravelOrderModal.vue';
import ToastHost from '../../components/ToastHost.vue';
import Spinner from '../../components/Spinner.vue';
import { toast } from '../../lib/toast';

type TravelOrderStatus = 'requested' | 'approved' | 'rejected' | 'cancelled' | string;

type TravelOrder = {
    order_code?: string;
    requester_name?: string;
    destination?: string;
    departure_date?: string;
    return_date?: string;
    status?: TravelOrderStatus;
    created_at?: string;
};

type ListResponse = {
    data: TravelOrder[];
};

type StatusFilter = 'all' | 'requested' | 'approved' | 'rejected' | 'cancelled';

type UpdateAction = 'approve' | 'reject' | 'cancel';

const user = getUser();

const isAdmin = computed(() => {
    const role = user?.role;
    if (typeof role === 'string' && role.toLowerCase() === 'admin') return true;
    if (user?.is_admin === true) return true;
    return false;
});

const createOpen = ref(false);

const loading = ref(true);
const refreshing = ref(false);
const errorMessage = ref<string | null>(null);
const orders = ref<TravelOrder[]>([]);

// Filters
const status = ref<StatusFilter>('all');

const activeFilterLabel = computed(() => {
    switch (status.value) {
        case 'requested':
            return 'Solicitado';
        case 'approved':
            return 'Aprovado';
        case 'rejected':
            return 'Rejeitado';
        case 'cancelled':
            return 'Cancelado';
        default:
            return 'Todos';
    }
});

const updatingActionByCode = ref<Record<string, UpdateAction | undefined>>({});

function isUpdating(code?: string) {
    if (!code) return false;
    return !!updatingActionByCode.value[code];
}

function setUpdating(code: string, action?: UpdateAction) {
    updatingActionByCode.value = { ...updatingActionByCode.value, [code]: action };
}

function openCreateModal() {
    createOpen.value = true;
}

function statusLabel(s: TravelOrder['status']) {
    if (s === 'requested') return 'Solicitado';
    if (s === 'approved') return 'Aprovado';
    if (s === 'rejected') return 'Rejeitado';
    if (s === 'cancelled') return 'Cancelado';
    return s ?? '—';
}

function statusClasses(s: TravelOrder['status']) {
    if (s === 'approved') return 'border-emerald-900/50 bg-emerald-950/30 text-emerald-200';
    if (s === 'rejected') return 'border-rose-900/50 bg-rose-950/30 text-rose-200';
    if (s === 'cancelled') return 'border-slate-800 bg-slate-950/40 text-slate-200';
    return 'border-sky-900/50 bg-sky-950/30 text-sky-200';
}

function buildApiUrl(): string {
    const params = new URLSearchParams();

    if (status.value !== 'all') {
        params.set('status', status.value);
    }

    const qs = params.toString();
    return qs ? `/api/v1/travel-orders?${qs}` : '/api/v1/travel-orders';
}

function syncFiltersToUrl(): void {
    if (typeof window === 'undefined') return;

    const params = new URLSearchParams(window.location.search);

    if (status.value === 'all') params.delete('status');
    else params.set('status', status.value);

    const next = `${window.location.pathname}${params.toString() ? `?${params.toString()}` : ''}`;
    window.history.replaceState({}, '', next);
}

function initFiltersFromUrl(): void {
    if (typeof window === 'undefined') return;

    const params = new URLSearchParams(window.location.search);
    const s = params.get('status') as StatusFilter | null;

    if (s === 'requested' || s === 'approved' || s === 'rejected' || s === 'cancelled' || s === 'all') {
        status.value = s ?? 'all';
    } else {
        status.value = 'all';
    }
}

let reloadTimer: number | undefined;

async function load() {
    const hasDataOnScreen = orders.value.length > 0;
    if (hasDataOnScreen) refreshing.value = true;
    else loading.value = true;

    errorMessage.value = null;

    try {
        const res = await api.get<ListResponse>(buildApiUrl());
        orders.value = res.data ?? [];
    } catch (e) {
        const msg = e instanceof ApiError ? e.message : 'Não foi possível carregar os pedidos.';
        errorMessage.value = msg;
        toast.error(msg, { title: 'Erro ao carregar' });
    } finally {
        loading.value = false;
        refreshing.value = false;
    }
}

function applyFilters() {
    syncFiltersToUrl();
    load();
}

function clearFilters() {
    status.value = 'all';
    applyFilters();
}

function logout() {
    clearAuth();
    router.visit('/login');
}

function canApproveOrReject(o: TravelOrder) {
    return o.status === 'requested';
}

function canCancel(o: TravelOrder) {
    return o.status === 'requested' || o.status === 'approved';
}

function statusEndpoint(orderCode: string) {
    return `/api/v1/travel-orders/${encodeURIComponent(orderCode)}/status`;
}

function cancelEndpoint(orderCode: string) {
    return `/api/v1/travel-orders/${encodeURIComponent(orderCode)}/cancel`;
}

async function updateStatus(order: TravelOrder, nextStatus: 'approved' | 'rejected' | 'cancelled', action: UpdateAction) {
    const code = order.order_code;

    if (!code) {
        toast.error('Não foi possível atualizar: pedido sem código.', { title: 'Erro' });
        return;
    }
    if (isUpdating(code)) return;

    if ((nextStatus === 'approved' || nextStatus === 'rejected') && !isAdmin.value) {
        toast.error('Apenas administradores podem aprovar ou rejeitar pedidos.', { title: 'Acesso negado' });
        return;
    }

    const prev = order.status ?? 'requested';

    setUpdating(code, action);
    order.status = nextStatus;

    try {
        if (nextStatus === 'cancelled') {
            await api.patch(cancelEndpoint(code));
        } else {
            await api.patch(statusEndpoint(code), { status: nextStatus });
        }

        const msg =
            nextStatus === 'approved'
                ? 'Pedido aprovado com sucesso.'
                : nextStatus === 'rejected'
                  ? 'Pedido rejeitado com sucesso.'
                  : 'Pedido cancelado com sucesso.';

        toast.success(msg, { title: 'Sucesso' });
    } catch (e) {
        order.status = prev;

        const msg = e instanceof ApiError ? e.message : 'Não foi possível atualizar o pedido.';
        toast.error(msg, { title: 'Erro ao atualizar' });
    } finally {
        setUpdating(code, undefined);
    }
}

function onCreated(created: TravelOrder) {
    const createdStatus = (created.status ?? 'requested') as StatusFilter | string;
    const matchesFilter = status.value === 'all' || createdStatus === status.value;

    if (created.order_code) {
        orders.value = orders.value.filter((o) => o.order_code !== created.order_code);
    }

    if (matchesFilter) {
        orders.value = [created, ...orders.value];
        toast.success('Pedido criado com sucesso.', { title: 'Sucesso' });
    } else {
        toast.success('Pedido criado com sucesso (não aparece devido ao filtro atual).', {
            title: 'Sucesso',
            timeoutMs: 4200,
        });
    }
}

watch(
    () => status.value,
    () => {
        if (reloadTimer) window.clearTimeout(reloadTimer);
        reloadTimer = window.setTimeout(() => {
            applyFilters();
        }, 200);
    },
);

onMounted(async () => {
    if (!getToken()) {
        router.visit('/login');
        return;
    }

    initFiltersFromUrl();
    await load();
});
</script>

<template>
    <Head title="Traveler Manager — Dashboard" />

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
                        <p class="text-sm font-semibold text-slate-100">Dashboard</p>
                        <p class="text-xs text-slate-400">
                            {{ user?.name ? `Olá, ${user.name}` : 'Pedidos de viagem' }}
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
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-slate-50">Pedidos de viagem</h1>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <button
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-slate-700 hover:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                        >
                            Novo pedido
                        </button>

                        <div class="rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2">
                            <label class="block text-[11px] font-semibold text-slate-400" for="status">
                                Status
                            </label>
                            <select
                                id="status"
                                v-model="status"
                                class="mt-1 w-[220px] rounded-lg border border-slate-800 bg-slate-950/40 px-2 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                            >
                                <option value="all">Todos</option>
                                <option value="requested">Solicitado</option>
                                <option value="approved">Aprovado</option>
                                <option value="rejected">Rejeitado</option>
                                <option value="cancelled">Cancelado</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            @click="clearFilters"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-slate-700 hover:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                        >
                            Limpar
                        </button>

                        <button
                            type="button"
                            @click="load"
                            :disabled="loading || refreshing"
                            class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-sky-300/70"
                        >
                            <Spinner v-if="refreshing" :size="16" class="text-slate-950" />
                            {{ refreshing ? 'Atualizando...' : 'Atualizar' }}
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                    <span class="inline-flex items-center rounded-full border border-slate-800 bg-slate-950/40 px-2.5 py-1">
                        Status: <span class="ml-1 font-semibold text-slate-200">{{ activeFilterLabel }}</span>
                    </span>

                    <span class="inline-flex items-center rounded-full border border-slate-800 bg-slate-950/40 px-2.5 py-1">
                        Total: <span class="ml-1 font-semibold text-slate-200">{{ orders.length }}</span>
                    </span>
                </div>

                <div
                    v-if="errorMessage"
                    class="mt-5 rounded-2xl border border-rose-900/60 bg-rose-950/30 p-4 text-sm text-rose-200"
                >
                    {{ errorMessage }}
                </div>

                <div class="mt-5 rounded-2xl border border-slate-800 bg-slate-950/50 shadow-sm backdrop-blur">
                    <div class="border-b border-slate-900/70 p-4">
                        <p class="text-sm font-semibold text-slate-100">Tabela</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Mostrando resultados conforme o filtro selecionado.
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
                            <p class="text-sm text-slate-300">Nenhum pedido encontrado para este filtro.</p>
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
                                        <th v-if="isAdmin" class="px-3 py-2">Ações</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-900/70">
                                    <tr v-for="(o, i) in orders" :key="o.order_code ?? `row-${i}`">
                                        <td class="px-3 py-3 text-slate-200">{{ o.order_code ?? '—' }}</td>
                                        <td class="px-3 py-3 text-slate-300">{{ o.requester_name ?? '—' }}</td>
                                        <td class="px-3 py-3 text-slate-200">{{ o.destination ?? '—' }}</td>
                                        <td class="px-3 py-3 text-slate-300">{{ o.departure_date ?? '—' }}</td>
                                        <td class="px-3 py-3 text-slate-300">{{ o.return_date ?? '—' }}</td>

                                        <!-- Status (com Cancelar para usuário comum, sem coluna Ações) -->
                                        <td class="px-3 py-3">
                                            <div class="flex flex-col gap-2">
                                                <span
                                                    class="inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-xs font-semibold"
                                                    :class="statusClasses(o.status)"
                                                >
                                                    {{ statusLabel(o.status) }}
                                                </span>

                                                <button
                                                    v-if="!isAdmin && canCancel(o)"
                                                    type="button"
                                                    :disabled="isUpdating(o.order_code)"
                                                    class="inline-flex w-fit items-center justify-center gap-2 rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-1.5 text-xs font-semibold text-slate-100 transition hover:bg-slate-900/45 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-sky-400/40"
                                                    @click="updateStatus(o, 'cancelled', 'cancel')"
                                                >
                                                    <Spinner
                                                        v-if="o.order_code && updatingActionByCode[o.order_code] === 'cancel'"
                                                        :size="14"
                                                        class="text-slate-100"
                                                    />
                                                    Cancelar
                                                </button>
                                            </div>
                                        </td>

                                        <!-- Ações (somente admin) -->
                                        <td v-if="isAdmin" class="px-3 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-if="canApproveOrReject(o)"
                                                    type="button"
                                                    :disabled="isUpdating(o.order_code)"
                                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-900/40 bg-emerald-950/20 px-3 py-1.5 text-xs font-semibold text-emerald-100 transition hover:bg-emerald-950/35 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-emerald-400/30"
                                                    @click="updateStatus(o, 'approved', 'approve')"
                                                >
                                                    <Spinner
                                                        v-if="o.order_code && updatingActionByCode[o.order_code] === 'approve'"
                                                        :size="14"
                                                        class="text-emerald-100"
                                                    />
                                                    Aprovar
                                                </button>

                                                <button
                                                    v-if="canCancel(o)"
                                                    type="button"
                                                    :disabled="isUpdating(o.order_code)"
                                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-rose-900/40 bg-rose-950/20 px-3 py-1.5 text-xs font-semibold text-rose-100 transition hover:bg-rose-950/35 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-rose-400/30"
                                                    @click="updateStatus(o, 'cancelled', 'cancel')"
                                                >
                                                    <Spinner
                                                        v-if="o.order_code && updatingActionByCode[o.order_code] === 'reject'"
                                                        :size="14"
                                                        class="text-rose-100"
                                                    />
                                                    Rejeitar
                                                </button>
                                            </div>
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

        <CreateTravelOrderModal v-model="createOpen" @created="onCreated" />
        <ToastHost />
    </div>
</template>