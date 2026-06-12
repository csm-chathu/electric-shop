<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'type', 'customer_name', 'mac_address',
        'activated_at', 'expires_at', 'is_active', 'notes',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at'   => 'datetime',
        'is_active'    => 'boolean',
    ];

    public function isActivated(): bool  { return !is_null($this->mac_address); }
    public function isExpired(): bool    { return $this->expires_at && $this->expires_at->isPast(); }
    public function isTrial(): bool      { return $this->type === 'trial'; }
    public function daysRemaining(): int { return $this->expires_at ? max(0, (int) now()->diffInDays($this->expires_at, false)) : 9999; }
}
