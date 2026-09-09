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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('razorpay_payment_id')->nullable()->unique();
            $table->string('razorpay_order_id')->nullable()->index();
            $table->unsignedBigInteger('amount'); // Stored in paise
            $table->string('currency', 10)->default('INR');
            $table->string('status')->default('created');
            $table->string('method')->nullable();
            $table->boolean('captured')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->text('failure_description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};