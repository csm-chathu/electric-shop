<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

        $key = strtoupper(implode('-', [
            Str::random(4), Str::random(4), Str::random(4), Str::random(4),
        ]));

        License::create([
            'key'           => $key,
            'type'          => $request->type,
            'customer_name' => $request->customer_name,
            'notes'         => $request->notes,
        ]);

        $label = $request->type === 'trial' ? '14-day trial' : 'paid';
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

