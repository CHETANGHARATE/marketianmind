<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('offer_id')->nullable()->after('bundle_id')->constrained('offers')->nullOnDelete();
            $table->unsignedInteger('offer_discount_amount')->default(0)->after('offer_id'); // in paise
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['offer_id']);
            $table->dropColumn(['offer_id', 'offer_discount_amount']);
        });
    }
};
