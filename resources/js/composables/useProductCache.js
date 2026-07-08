/**
 * localStorage cache for the product list used in the POS sales page.
 * TTL: 5 minutes. Busted on version mismatch.
 */

const KEY     = 'pos_products_v1';
const TTL_MS  = 5 * 60 * 1000; // 5 minutes

export function getCachedProducts() {
    try {
        const raw = localStorage.getItem(KEY);
        if (!raw) return null;
        const { ts, data } = JSON.parse(raw);
        if (Date.now() - ts > TTL_MS) { localStorage.removeItem(KEY); return null; }
        return data;
    } catch {
        return null;
    }
}

export function setCachedProducts(data) {
    try {
        localStorage.setItem(KEY, JSON.stringify({ ts: Date.now(), data }));
    } catch {
        // Storage full — just skip caching
    }
}

export function bustProductCache() {
    localStorage.removeItem(KEY);
}
