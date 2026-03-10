<script setup lang="ts">
import { computed } from 'vue';
import { removeToast, toastState, type Toast } from '../lib/toast';

const items = computed(() => toastState.items);

function tone(t: Toast) {
    if (t.type === 'success') return 'border-emerald-900/50 bg-emerald-950/40 text-emerald-100';
    if (t.type === 'error') return 'border-rose-900/50 bg-rose-950/40 text-rose-100';
    return 'border-sky-900/50 bg-sky-950/40 text-sky-100';
}
</script>

<template>
    <teleport to="body">
        <div class="fixed right-6 top-6 z-[60] w-[min(420px,calc(100vw-3rem))] space-y-3">
            <div
                v-for="t in items"
                :key="t.id"
                class="rounded-2xl border p-4 shadow-sm backdrop-blur"
                :class="tone(t)"
                role="status"
                aria-live="polite"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p v-if="t.title" class="text-sm font-semibold text-slate-50">
                            {{ t.title }}
                        </p>
                        <p class="text-sm leading-relaxed">
                            {{ t.message }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="shrink-0 rounded-lg px-2 py-1 text-xs text-slate-200/80 transition hover:bg-slate-900/40 hover:text-slate-50 focus:outline-none focus:ring-2 focus:ring-sky-400/60"
                        @click="removeToast(t.id)"
                        aria-label="Fechar notificação"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>