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
        Schema::create('course_access_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('period_type')->default('initial'); // initial, renewal, admin_grant
            $table->timestamp('starts_at')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            // Composite index for fast lookups of access periods per enrollment
            $table->index(['enrollment_id', 'expires_at'], 'course_access_periods_enrollment_expires_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_access_periods');
    }
};
