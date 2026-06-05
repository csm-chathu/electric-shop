<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'closing_date',
        'total_sales',
        'total_cash',
        'total_card',
        'total_qr',
        'total_credit',
        'total_transactions',
        'note',
    ];

    protected $casts = [
        'closing_date'      => 'date',
        'total_sales'       => 'decimal:2',
        'total_cash'        => 'decimal:2',
        'total_card'        => 'decimal:2',
        'total_qr'          => 'decimal:2',
        'total_credit'      => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
