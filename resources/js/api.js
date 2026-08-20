/**
 * Cliente mínimo para la API v1 (tokens Sanctum vía Authorization header).
 * Lanza ApiError con `status`, `message` y `errors` de validación de Laravel.
 */
const BASE = '/api/v1';

export class ApiError extends Error {
    constructor(status, message, errors = null, payload = null) {
        super(message);
        this.status = status;
        this.errors = errors;
        this.payload = payload;
    }
}

async function request(method, path, body = null, params = null) {
    const url = new URL(BASE + path, window.location.origin);
    if (params) {
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                url.searchParams.set(key, value);
            }
        });
    }

    const headers = { Accept: 'application/json' };
    const token = localStorage.getItem('mf_token');
    if (token) headers.Authorization = `Bearer ${token}`;
    if (body) headers['Content-Type'] = 'application/json';

    const response = await fetch(url, {
        method,
        headers,
        body: body ? JSON.stringify(body) : undefined,
    });

    if (response.status === 204) return null;

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        if (response.status === 401) {
            localStorage.removeItem('mf_token');
        }
        throw new ApiError(
            response.status,
            data?.message ?? `Error ${response.status}`,
            data?.errors ?? null,
            data,
        );
    }

    return data;
}

export const api = {
    get: (path, params = null) => request('GET', path, null, params),
    post: (path, body = null) => request('POST', path, body),
    put: (path, body = null) => request('PUT', path, body),
    patch: (path, body = null) => request('PATCH', path, body),
    delete: (path) => request('DELETE', path),
};
