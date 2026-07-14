<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installment_plans', function (Blueprint $table) {
            $table->decimal('interest_rate', 5, 2)->default(0)->after('down_payment_percent');
            $table->decimal('interest_amount', 12, 2)->default(0)->after('interest_rate');
            $table->unsignedTinyInteger('dp_grace_days')->default(7)->after('interest_amount');
        });
    }

    public function down(): void
    {
        Schema::table('installment_plans', function (Blueprint $table) {
            $table->dropColumn(['interest_rate', 'interest_amount', 'dp_grace_days']);
        });
    }
};
