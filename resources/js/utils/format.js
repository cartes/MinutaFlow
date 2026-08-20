const clpFormatter = new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
});

export function clp(amount) {
    return clpFormatter.format(amount ?? 0);
}

export function toIsoDate(date) {
    const d = new Date(date);
    const offset = d.getTimezoneOffset();
    return new Date(d.getTime() - offset * 60000).toISOString().slice(0, 10);
}

/** "2026-08-19" → Date local (evita el corrimiento UTC de new Date('YYYY-MM-DD')). */
export function parseIsoDate(iso) {
    const [year, month, day] = String(iso).slice(0, 10).split('-').map(Number);
    return new Date(year, month - 1, day);
}

export function longDate(date) {
    const d = typeof date === 'string' ? parseIsoDate(date) : date;
    return d.toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long' });
}

export function shortDay(date) {
    const d = typeof date === 'string' ? parseIsoDate(date) : date;
    return d.toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric' });
}

export function capitalize(text) {
    return text ? text.charAt(0).toUpperCase() + text.slice(1) : '';
}
