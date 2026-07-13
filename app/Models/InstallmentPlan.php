<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'plan_no', 'customer_id', 'user_id',
        'subtotal', 'discount', 'total',
        'down_payment', 'balance', 'installment_amount',
        'installments_count', 'down_payment_percent',
        'status', 'notes',
    ];

    protected $casts = [
        'total'               => 'float',
        'subtotal'            => 'float',
        'discount'            => 'float',
        'down_payment'        => 'float',
        'balance'             => 'float',
        'installment_amount'  => 'float',
        'installments_count'  => 'integer',
        'down_payment_percent'=> 'integer',
    ];

    public function customer()    { return $this->belongsTo(Customer::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function items()       { return $this->hasMany(InstallmentPlanItem::class, 'plan_id'); }
    public function payments()    { return $this->hasMany(InstallmentPayment::class, 'plan_id')->orderBy('installment_no'); }
    public function documents()   { return $this->hasMany(InstallmentDocument::class, 'plan_id'); }

    public function downPaymentRecord()
    {
        return $this->hasOne(InstallmentPayment::class, 'plan_id')->where('installment_no', 0);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments->sum('amount_paid');
    }

    public function amountRemaining(): float
    {
        return max(0, $this->total - $this->amountPaid());
    }

    public function nextPending(): ?InstallmentPayment
    {
        return $this->payments
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sortBy('installment_no')
            ->first();
    }
}
