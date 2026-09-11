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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('discount_type')->default('percentage'); // 'percentage' or 'fixed'
            $table->unsignedInteger('discount_value'); // e.g. 20 (for 20%) or 50000 (for ₹500 in paise)
            $table->unsignedBigInteger('min_order_amount')->nullable(); // in paise, e.g. 99900 for ₹999
            $table->unsignedBigInteger('max_discount_amount')->nullable(); // in paise, cap for percentage
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable(); // Platform total max redemptions
            $table->unsignedInteger('per_user_limit')->default(1); // Max redemptions per student
            $table->unsignedInteger('times_used')->default(0); // Counter cache of successful redemptions
            $table->boolean('is_active')->default(true);
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete(); // Specific course or null for all
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
            $table->index(['starts_at', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};