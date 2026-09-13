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
        Schema::create('marketing_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject');
            $table->longText('body_html');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type')->index();
            $table->string('status')->default('draft')->index();
            $table->foreignId('template_id')->nullable()->constrained('marketing_templates')->cascadeOnDelete();
            $table->integer('delay_minutes')->default(0);
            $table->json('conditions')->nullable();
            $table->timestamps();
        });

        Schema::create('automation_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_id')->constrained('automations')->cascadeOnDelete();
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id');
            $table->string('trigger_event');
            $table->string('reference_id')->nullable();
            $table->timestamp('scheduled_at')->index();
            $table->timestamp('executed_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['recipient_type', 'recipient_id']);
            $table->unique(
                ['automation_id', 'recipient_type', 'recipient_id', 'trigger_event', 'reference_id'],
                'automation_exec_idempotency_unique'
            );
        });

        Schema::create('marketing_unsubscribes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique()->index();
            $table->string('reason')->nullable();
            $table->timestamp('unsubscribed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_unsubscribes');
        Schema::dropIfExists('automation_executions');
        Schema::dropIfExists('automations');
        Schema::dropIfExists('marketing_templates');
    }
};
