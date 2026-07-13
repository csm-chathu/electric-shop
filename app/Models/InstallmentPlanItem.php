<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentPlanItem extends Model
{
    protected $fillable = [
        'plan_id', 'product_id', 'product_name',
        'qty', 'unit_price', 'cost_price', 'discount', 'total',
    ];

    protected $casts = [
        'qty'        => 'float',
        'unit_price' => 'float',
        'cost_price' => 'float',
        'discount'   => 'float',
        'total'      => 'float',
    ];

    public function plan()    { return $this->belongsTo(InstallmentPlan::class, 'plan_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
