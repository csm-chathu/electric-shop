import axios from 'axios';

const LS_KEY = 'pos_products_v2';
const TTL    = 5 * 60 * 1000; // 5 minutes

let _mem     = null; // in-memory for same-session reuse
let _pending = null;

function lsRead() {
    try {
        const raw = localStorage.getItem(LS_KEY);
        if (!raw) return null;
        const { ts, data } = JSON.parse(raw);
        if (Date.now() - ts > TTL) { localStorage.removeItem(LS_KEY); return null; }
        return data;
    } catch { return null; }
}

function lsWrite(data) {
    try { localStorage.setItem(LS_KEY, JSON.stringify({ ts: Date.now(), data })); }
    catch { /* storage full — skip */ }
}

export function getProducts() {
    // 1. Same-session in-memory hit (instant)
    if (_mem) return Promise.resolve(_mem);

    // 2. localStorage hit (instant, survives page reload)
    const cached = lsRead();
    if (cached) { _mem = cached; return Promise.resolve(_mem); }

    // 3. Deduplicate in-flight requests
    if (_pending) return _pending;

    // 4. Fetch from server
    _pending = axios.get('/api/products/all')
        .then(res => {
            _mem     = res.data;
            _pending = null;
            lsWrite(_mem);
            return _mem;
        })
        .catch(err => {
            _pending = null;
            throw err;
        });

    return _pending;
}

export function invalidateProducts() {
    _mem     = null;
    _pending = null;
    try { localStorage.removeItem(LS_KEY); } catch { /* ignore */ }
}
