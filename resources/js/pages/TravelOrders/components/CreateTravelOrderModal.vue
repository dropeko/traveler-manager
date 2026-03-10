<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { api, ApiError } from '../../../lib/http';
import Spinner from '../../../components/Spinner.vue';

type CreatePayload = {
    destination: string;
    departure_date: string;
    return_date: string;
};

export type CreatedTravelOrder = {
    order_code?: string;
    requester_name?: string;
    destination?: string;
    departure_date?: string;
    return_date?: string;
    status?: string;
    created_at?: string;
};

type CreateResponse = {
    data: CreatedTravelOrder;
};

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void;
    (e: 'created', order: CreatedTravelOrder): void;
}>();

const open = computed(() => props.modelValue);
const loading = ref(false);
const errorMessage = ref<string | null>(null);
const fieldErrors = ref<Partial<Record<keyof CreatePayload, string>>>({});

const form = ref<CreatePayload>({
    destination: '',
    departure_date: '',
    return_date: '',
});

const destinationRef = ref<HTMLInputElement | null>(null);

const canSubmit = computed(() => {
    return (
        form.value.destination.trim().length > 0 &&
        !!form.value.departure_date &&
        !!form.value.return_date &&
        form.value.return_date >= form.value.departure_date
    );
});

function close() {
    emit('update:modelValue', false);
}

function reset() {
    form.value = { destination: '', departure_date: '', return_date: '' };
    errorMessage.value = null;
    fieldErrors.value = {};
}

function onBackdropClick() {
    if (!loading.value) close();
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && !loading.value) close();
}

function setValidationErrors(payload: unknown) {
    fieldErrors.value = {};
    errorMessage.value = null;

    const errors = (payload as any)?.errors as Record<string, string[]> | undefined;

    if (errors) {
        for (const key of ['destination', 'departure_date', 'return_date'] as const) {
            const msg = errors[key]?.[0];
            if (msg) fieldErrors.value[key] = msg;
        }
        errorMessage.value = (payload as any)?.message ?? 'Verifique os campos e tente novamente.';
        return;
    }

    errorMessage.value = (payload as any)?.message ?? 'Não foi possível criar o pedido. Tente novamente.';
}

async function submit() {
    if (!canSubmit.value || loading.value) return;

    loading.value = true;
    errorMessage.value = null;
    fieldErrors.value = {};

    try {
        const res = await api.post<CreateResponse>('/api/v1/travel-orders', {
            destination: form.value.destination.trim(),
            departure_date: form.value.departure_date,
            return_date: form.value.return_date,
        });

        emit('created', res.data);

        close();
        reset();
    } catch (e) {
        if (e instanceof ApiError) setValidationErrors(e.payload);
        else errorMessage.value = 'Não foi possível criar o pedido. Tente novamente.';
    } finally {
        loading.value = false;
    }
}

watch(
    () => open.value,
    async (isOpen) => {
        if (!isOpen) return;
        await nextTick();
        destinationRef.value?.focus();
    },
);
</script>

<template>
    <teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50" @keydown="onKeydown">
            <div
                class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"
                @click="onBackdropClick"
                aria-hidden="true"
            />

            <div class="relative mx-auto flex min-h-screen max-w-6xl items-center justify-center px-6 py-10">
                <div
                    class="w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-950/60 p-6 shadow-sm"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Criar pedido de viagem"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold tracking-tight text-slate-50">Novo pedido de viagem</h2>
                            <p class="mt-1 text-sm text-slate-400">Preencha os dados para criar um novo travel order.</p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-sm text-slate-300 transition hover:bg-slate-900/50 hover:text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                            :disabled="loading"
                            @click="close"
                        >
                            Fechar
                        </button>
                    </div>

                    <div
                        v-if="errorMessage"
                        class="mt-4 rounded-xl border border-rose-900/60 bg-rose-950/30 p-3 text-sm text-rose-200"
                    >
                        {{ errorMessage }}
                    </div>

                    <form class="mt-5 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="text-sm font-medium text-slate-200" for="destination">Destino</label>
                            <input
                                id="destination"
                                ref="destinationRef"
                                v-model="form.destination"
                                type="text"
                                class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                                placeholder="Ex.: São Paulo"
                            />
                            <p v-if="fieldErrors.destination" class="mt-2 text-xs text-rose-200">
                                {{ fieldErrors.destination }}
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium text-slate-200" for="departure_date">Data de ida</label>
                                <input
                                    id="departure_date"
                                    v-model="form.departure_date"
                                    type="date"
                                    class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2.5 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                                />
                                <p v-if="fieldErrors.departure_date" class="mt-2 text-xs text-rose-200">
                                    {{ fieldErrors.departure_date }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-slate-200" for="return_date">Data de volta</label>
                                <input
                                    id="return_date"
                                    v-model="form.return_date"
                                    type="date"
                                    :min="form.departure_date || undefined"
                                    class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2.5 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                                />
                                <p v-if="fieldErrors.return_date" class="mt-2 text-xs text-rose-200">
                                    {{ fieldErrors.return_date }}
                                </p>
                                <p
                                    v-if="form.departure_date && form.return_date && form.return_date < form.departure_date"
                                    class="mt-2 text-xs text-rose-200"
                                >
                                    A data de volta não pode ser anterior à data de ida.
                                </p>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-2.5 text-sm font-semibold text-slate-100 transition hover:border-slate-700 hover:bg-slate-900/50 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                                :disabled="loading"
                                @click="close"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-sky-400 disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-sky-300/70"
                                :disabled="!canSubmit || loading"
                            >
                                <Spinner v-if="loading" :size="16" class="text-slate-950" />
                                {{ loading ? 'Criando...' : 'Criar pedido' }}
                            </button>
                        </div>

                        <p class="text-xs text-slate-500">
                            Criação via <code>POST /api/v1/travel-orders</code>.
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </teleport>
</template>