<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'name_si',
        'barcode',
        'sku',
        'description',
        'cost_price',
        'selling_price',
        'wholesale_price',
        'stock_qty',
        'alert_qty',
        'unit',
        'active',
    ];

    protected $casts = [
        'cost_price'      => 'decimal:2',
        'selling_price'   => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'active'        => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_qty', '<=', 'alert_qty');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
