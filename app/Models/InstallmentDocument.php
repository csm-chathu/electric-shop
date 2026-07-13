<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentDocument extends Model
{
    protected $fillable = [
        'plan_id', 'type', 'label', 'file_path', 'original_name', 'uploaded_by',
    ];

    public function plan()     { return $this->belongsTo(InstallmentPlan::class, 'plan_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function typeLabel(): string
    {
        return match($this->type) {
            'nic_front'      => 'NIC Front',
            'nic_back'       => 'NIC Back',
            'photo'          => 'Customer Photo',
            'address_proof'  => 'Address Proof',
            'guarantor_nic'  => 'Guarantor NIC',
            'agreement'      => 'Signed Agreement',
            default          => $this->label ?? 'Document',
        };
    }
}
