<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_no',
        'supplier_id',
        'user_id',
        'total',
        'paid',
        'status',
        'purchase_date',
        'note',
    ];

    protected $casts = [
        'total'         => 'decimal:2',
        'paid'          => 'decimal:2',
        'purchase_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
