<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->enum('type', [
                'nic_front', 'nic_back', 'photo',
                'address_proof', 'guarantor_nic', 'agreement', 'other'
            ]);
            $table->string('label')->nullable(); // for 'other' type
            $table->string('file_path');         // disk path (private storage)
            $table->string('original_name')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')->references('id')->on('installment_plans')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_documents');
    }
};
