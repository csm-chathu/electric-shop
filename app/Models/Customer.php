<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'credit_limit',
        'credit_balance',
        'active',
    ];

    protected $casts = [
        'credit_limit'   => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'active'         => 'boolean',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function creditPayments()
    {
        return $this->hasMany(CreditPayment::class);
    }
}
