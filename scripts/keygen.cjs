/**
 * Lumac POS — Offline License Key Generator
 * Run: node scripts/keygen.cjs trial "Shop Name"
 *      node scripts/keygen.cjs paid  "Shop Name"
 */

const crypto = require('crypto');

// !! Keep this secret — same value must be in electron/main.cjs !!
const SECRET = 'lumac-pos-offline-k3y-s3cr3t-2025';

const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/I/1 → 32 chars

// Use local midnight so timezone doesn't shift the date
const EPOCH = new Date(2025, 0, 1).getTime(); // Jan 1 2025 local midnight

function localDaysSinceEpoch(date) {
  const localMidnight = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
  return Math.floor((localMidnight - EPOCH) / 86400000);
}

function randomChars(n) {
  let s = '';
  const bytes = crypto.randomBytes(n);
  for (let i = 0; i < n; i++) s += CHARS[bytes[i] % CHARS.length];
  return s;
}

// Encode local days-since-epoch into 4 chars (base-32, supports ~100 years)
function encodeDate(date) {
  let days = localDaysSinceEpoch(date);
  let s = '';
  for (let i = 0; i < 4; i++) {
    s = CHARS[days % 32] + s;
    days = Math.floor(days / 32);
  }
  return s;
}

function generateKey(type) {
  const typeChar = type === 'paid' ? 'P' : 'T';

  // Paid: embed today's date so key only activates today
  // Trial: no date — activates any time
  const payload = typeChar === 'P'
    ? typeChar + encodeDate(new Date()) + randomChars(5)  // 1+4+5 = 10
    : typeChar + randomChars(9);                          // 1+9   = 10

  const hmac = crypto
    .createHmac('sha256', SECRET)
    .update(payload)
    .digest('hex')
    .toUpperCase()
    .slice(0, 6);

  const raw = payload + hmac; // 16 chars
  return raw.match(/.{1,4}/g).join('-');
}

// ── CLI ───────────────────────────────────────────────────────────────────────
const type     = process.argv[2] || 'trial';
const customer = process.argv[3] || '';

if (!['trial', 'paid'].includes(type)) {
  console.error('Usage: node keygen.cjs [trial|paid] ["Customer Name"]');
  process.exit(1);
}

const key = generateKey(type);
const d   = new Date();
const now = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

console.log('\n─────────────────────────────────────');
console.log(`  Key      : ${key}`);
console.log(`  Type     : ${type === 'paid' ? 'Paid — valid TODAY only to activate (then works forever)' : 'Trial (14 days)'}`);
console.log(`  Customer : ${customer || '—'}`);
console.log(`  Valid on : ${now}`);
console.log('─────────────────────────────────────\n');
