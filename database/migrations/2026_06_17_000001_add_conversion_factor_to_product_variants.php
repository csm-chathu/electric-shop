<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // How many base-unit (product stock unit) equals 1 of this variant.
            // Example: product unit = kg, variant = 200g  → conversion_factor = 0.2
            // Example: product unit = kg, variant = 1kg   → conversion_factor = 1.0
            // Default 1 = no conversion (variant and product are same unit).
            $table->decimal('conversion_factor', 10, 6)->default(1)->after('alert_qty');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('conversion_factor');
        });
    }
};
