<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LicenseController extends Controller
{
    // ── Admin: list all licenses ───────────────────────────────────────────────
    public function index()
    {
        $licenses = License::latest()->get();
        return Inertia::render('Licenses/Index', compact('licenses'));
    }

    // ── Admin: generate trial or paid key ─────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'type'          => 'required|in:trial,paid',
            'notes'         => 'nullable|string|max:500',
        ]);

        $key = $this->generateKey($request->type);

        License::create([
            'key'           => $key,
            'type'          => $request->type,
            'customer_name' => $request->customer_name,
            'notes'         => $request->notes,
        ]);

        $label = $request->type === 'trial' ? '14-day trial' : 'lifetime';
        return back()->with('success', "License key created ({$label}): {$key}");
    }

    // ── Admin: upgrade trial → paid ────────────────────────────────────────────
    public function upgrade(License $license)
    {
        $license->update([
            'type'       => 'paid',
            'expires_at' => null,
        ]);

        return back()->with('success', 'License upgraded to paid (no expiry).');
    }

    // ── Admin: reset machine binding ───────────────────────────────────────────
    public function update(Request $request, License $license)
    {
        $license->update([
            'mac_address'  => null,
            'activated_at' => null,
            'expires_at'   => null,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'License reset — machine can re-activate.');
    }

    public function destroy(License $license)
    {
        $license->delete();
        return back()->with('success', 'License deleted.');
    }

    // ── API: called by Electron on first activation ────────────────────────────
    public function activate(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'mac' => 'required|string',
        ]);

        $license = License::where('key', strtoupper($request->key))->first();

        if (!$license)              return response()->json(['error' => 'invalid_key'],    404);
        if (!$license->is_active)   return response()->json(['error' => 'key_disabled'],   403);

        // Already activated on a different machine
        if ($license->mac_address && $license->mac_address !== $request->mac) {
            return response()->json(['error' => 'already_activated'], 409);
        }

        // Set expiry only on first activation
        $expiresAt = $license->expires_at;
        if (!$license->activated_at) {
            $expiresAt = $license->type === 'trial' ? now()->addDays(14) : null;
        }

        $license->update([
            'mac_address'  => $request->mac,
            'activated_at' => $license->activated_at ?? now(),
            'expires_at'   => $expiresAt,
        ]);

        return response()->json([
            'success'       => true,
            'customer'      => $license->customer_name,
            'type'          => $license->type,
            'expires_at'    => $license->fresh()->expires_at?->toISOString(),
            'days_remaining'=> $license->fresh()->daysRemaining(),
            'token'         => hash_hmac('sha256', $license->key . $request->mac, config('app.key')),
        ]);
    }

    // ── Key generation (must match keygen.cjs / electron/main.cjs) ───────────
    private const CHARS  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 32 chars, no 0/O/I/1
    private const SECRET = 'lumac-pos-offline-k3y-s3cr3t-2025';
    private const EPOCH  = '2025-01-01';

    private function generateKey(string $type): string
    {
        $typeChar = $type === 'paid' ? 'P' : 'T';

        // Paid: embed today's date so key must be activated today (then works forever)
        // Trial: no date — activates any time, expires 14 days after first activation
        $payload = $typeChar === 'P'
            ? $typeChar . $this->encodeDate(now()) . $this->randomChars(5)  // 1+4+5 = 10
            : $typeChar . $this->randomChars(9);                             // 1+9   = 10

        $hmac = strtoupper(substr(hash_hmac('sha256', $payload, self::SECRET), 0, 6));
        $raw  = $payload . $hmac; // 16 chars

        return implode('-', str_split($raw, 4));
    }

    private function randomChars(int $n): string
    {
        $result = '';
        $bytes  = random_bytes($n);
        for ($i = 0; $i < $n; $i++) {
            $result .= self::CHARS[ord($bytes[$i]) % 32];
        }
        return $result;
    }

    private function encodeDate(\Illuminate\Support\Carbon $date): string
    {
        $epoch     = \Carbon\Carbon::parse(self::EPOCH)->startOfDay();
        $days      = (int) $epoch->diffInDays($date->copy()->startOfDay());
        $encoded   = '';
        for ($i = 0; $i < 4; $i++) {
            $encoded = self::CHARS[$days % 32] . $encoded;
            $days    = intdiv($days, 32);
        }
        return $encoded;
    }

    // ── API: called by Electron on every launch ────────────────────────────────
    public function verify(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'mac' => 'required|string',
        ]);

        $license = License::where('key', strtoupper($request->key))->first();

        if (!$license || !$license->is_active)       return response()->json(['valid' => false, 'error' => 'invalid']);
        if ($license->mac_address !== $request->mac) return response()->json(['valid' => false, 'error' => 'mac_mismatch']);
        if ($license->isExpired())                   return response()->json(['valid' => false, 'error' => 'expired']);

        return response()->json([
            'valid'          => true,
            'customer'       => $license->customer_name,
            'type'           => $license->type,
            'expires_at'     => $license->expires_at?->toISOString(),
            'days_remaining' => $license->daysRemaining(),
        ]);
    }
}

