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
        // 1. Enhance users table with phone and WhatsApp consent
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('phone_normalized', 50)->nullable()->index()->after('phone');
            $table->boolean('whatsapp_opt_in')->default(false)->after('phone_normalized');
            $table->timestamp('whatsapp_opted_in_at')->nullable()->after('whatsapp_opt_in');
            $table->timestamp('whatsapp_opted_out_at')->nullable()->after('whatsapp_opted_in_at');
            $table->string('whatsapp_consent_source')->nullable()->after('whatsapp_opted_out_at');
        });

        // 2. Enhance leads table with normalized phone and WhatsApp consent
        Schema::table('leads', function (Blueprint $table) {
            $table->string('phone_normalized', 50)->nullable()->index()->after('phone');
            $table->boolean('whatsapp_opt_in')->default(false)->after('phone_normalized');
            $table->timestamp('whatsapp_opted_in_at')->nullable()->after('whatsapp_opt_in');
            $table->timestamp('whatsapp_opted_out_at')->nullable()->after('whatsapp_opted_in_at');
            $table->string('whatsapp_consent_source')->nullable()->after('whatsapp_opted_out_at');
        });

        // 3. Create whatsapp_message_templates table
        Schema::create('whatsapp_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('provider_template_name');
            $table->string('language', 10)->default('en');
            $table->string('category', 50)->default('utility'); // marketing, utility, authentication
            $table->text('body_text');
            $table->json('variables')->nullable();
            $table->string('status', 50)->default('approved'); // approved, pending, rejected
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Create whatsapp_messages table
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('phone_number', 50);
            $table->string('phone_normalized', 50)->index();
            $table->string('direction', 20)->default('outbound'); // outbound, inbound
            $table->string('message_type', 50)->default('template'); // template, text
            $table->foreignId('template_id')->nullable()->constrained('whatsapp_message_templates')->nullOnDelete();
            $table->string('template_name')->nullable();
            $table->string('template_language', 10)->nullable();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('status', 50)->default('pending')->index(); // pending, queued, sent, delivered, read, failed, cancelled
            $table->string('idempotency_key')->nullable()->unique();
            $table->boolean('is_marketing')->default(false);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // 5. Create whatsapp_webhook_events table
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type')->index();
            $table->string('provider', 50)->default('meta');
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // 6. Enhance automations and automation_executions for WhatsApp channel
        Schema::table('automations', function (Blueprint $table) {
            $table->string('channel', 20)->default('email')->after('status');
            $table->foreignId('whatsapp_template_id')->nullable()->after('template_id')->constrained('whatsapp_message_templates')->nullOnDelete();
        });

        Schema::table('automation_executions', function (Blueprint $table) {
            $table->string('channel', 20)->default('email')->after('status');
            $table->foreignId('whatsapp_message_id')->nullable()->after('failure_reason')->constrained('whatsapp_messages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automation_executions', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_message_id']);
            $table->dropColumn(['whatsapp_message_id', 'channel']);
        });

        Schema::table('automations', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_template_id']);
            $table->dropColumn(['whatsapp_template_id', 'channel']);
        });

        Schema::dropIfExists('whatsapp_webhook_events');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_message_templates');

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'phone_normalized',
                'whatsapp_opt_in',
                'whatsapp_opted_in_at',
                'whatsapp_opted_out_at',
                'whatsapp_consent_source',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'phone_normalized',
                'whatsapp_opt_in',
                'whatsapp_opted_in_at',
                'whatsapp_opted_out_at',
                'whatsapp_consent_source',
            ]);
        });
    }
};
