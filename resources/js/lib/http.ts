import { clearAuth, getToken } from './auth';

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly payload: unknown,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

type Json = Record<string, unknown> | unknown[] | string | number | boolean | null;

async function request<TResponse>(
    method: 'GET' | 'POST' | 'PATCH',
    url: string,
    body?: Json,
): Promise<TResponse> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
    };

    const token = getToken();
    if (token) headers.Authorization = `Bearer ${token}`;

    let payload: BodyInit | undefined;
    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    const res = await fetch(url, {
        method,
        headers,
        body: payload,
    });

    if (res.status === 401) {
        clearAuth();
        if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
            window.location.assign('/login');
        }
    }

    const contentType = res.headers.get('content-type') ?? '';
    const data = contentType.includes('application/json') ? await res.json() : await res.text();

    if (!res.ok) {
        const message =
            typeof data === 'object' && data && 'message' in (data as any)
                ? String((data as any).message)
                : `Request failed (${res.status})`;

        throw new ApiError(message, res.status, data);
    }

    return data as TResponse;
}

export const api = {
    get: <T = unknown>(url: string) => request<T>('GET', url),
    post: <T = unknown>(url: string, body?: Json) => request<T>('POST', url, body),
    patch: <T = unknown>(url: string, body?: Json) => request<T>('PATCH', url, body),
};