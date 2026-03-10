import { reactive } from 'vue';

export type ToastType = 'success' | 'error' | 'info';

export type Toast = {
    id: string;
    type: ToastType;
    message: string;
    title?: string;
    timeoutMs: number;
};

export const toastState = reactive({
    items: [] as Toast[],
});

function uid(): string {
    // crypto.randomUUID quando disponível; fallback seguro
    return typeof crypto !== 'undefined' && 'randomUUID' in crypto
        ? (crypto as Crypto).randomUUID()
        : `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export function removeToast(id: string) {
    const idx = toastState.items.findIndex((t) => t.id === id);
    if (idx >= 0) toastState.items.splice(idx, 1);
}

export function pushToast(type: ToastType, message: string, opts?: { title?: string; timeoutMs?: number }) {
    const toast: Toast = {
        id: uid(),
        type,
        message,
        title: opts?.title,
        timeoutMs: opts?.timeoutMs ?? 3200,
    };

    toastState.items.push(toast);

    window.setTimeout(() => removeToast(toast.id), toast.timeoutMs);
}

export const toast = {
    success: (message: string, opts?: { title?: string; timeoutMs?: number }) => pushToast('success', message, opts),
    error: (message: string, opts?: { title?: string; timeoutMs?: number }) => pushToast('error', message, opts),
    info: (message: string, opts?: { title?: string; timeoutMs?: number }) => pushToast('info', message, opts),
};