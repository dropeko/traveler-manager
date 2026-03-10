export type AuthUser = {
    id: number;
    name: string;
    email: string;
    role?: string;
    is_admin?: boolean;
};

const TOKEN_KEY = 'tm.access_token';
const USER_KEY = 'tm.user';

export function getToken(): string | null {
    if (typeof window === 'undefined') return null;
    return window.localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string): void {
    window.localStorage.setItem(TOKEN_KEY, token);
}

export function clearAuth(): void {
    if (typeof window === 'undefined') return;
    window.localStorage.removeItem(TOKEN_KEY);
    window.localStorage.removeItem(USER_KEY);
}

export function getUser(): AuthUser | null {
    if (typeof window === 'undefined') return null;

    const raw = window.localStorage.getItem(USER_KEY);
    if (!raw) return null;

    try {
        return JSON.parse(raw) as AuthUser;
    } catch {
        return null;
    }
}

export function setUser(user: AuthUser): void {
    window.localStorage.setItem(USER_KEY, JSON.stringify(user));
}