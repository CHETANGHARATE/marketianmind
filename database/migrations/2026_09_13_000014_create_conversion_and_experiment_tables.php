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
        // 1. Experiments Table
        if (!Schema::hasTable('experiments')) {
            Schema::create('experiments', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('key', 100)->unique();
                $table->text('description')->nullable();
                $table->string('status', 20)->default('draft')->index(); // draft, active, paused, completed, archived
                $table->string('target', 50)->default('global')->index(); // course_cta, bundle_cta, lead_form, homepage_hero, global
                $table->string('target_audience', 100)->default('all');
                $table->unsignedBigInteger('target_id')->nullable()->index(); // Course ID, Bundle ID, etc.
                $table->unsignedTinyInteger('traffic_percentage')->default(100); // 1 to 100
                $table->timestamp('start_at')->nullable();
                $table->timestamp('end_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->string('primary_metric', 50)->default('checkout_started');
                $table->unsignedBigInteger('winning_variant_id')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 2. Experiment Variants Table
        if (!Schema::hasTable('experiment_variants')) {
            Schema::create('experiment_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('key', 50); // control, variant_a, variant_b
                $table->text('description')->nullable();
                $table->boolean('is_control')->default(false);
                $table->unsignedTinyInteger('allocation_percentage')->default(50);
                $table->unsignedTinyInteger('weight')->default(50);
                $table->json('configuration')->nullable(); // Allowlisted data fields (button_text, headline, etc.)
                $table->json('config')->nullable();
                $table->string('status', 20)->default('active'); // active, inactive
                $table->timestamps();

                $table->unique(['experiment_id', 'key']);
            });
        }

        // 3. Experiment Exposures Table (Idempotent visitor exposure tracking)
        if (!Schema::hasTable('experiment_exposures')) {
            Schema::create('experiment_exposures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('experiment_id')->constrained('experiments')->cascadeOnDelete();
                $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('anonymous_id', 100)->nullable()->index();
                $table->string('visitor_hash', 64)->index();
                $table->timestamp('occurred_at');
                $table->timestamps();

                $table->unique(['experiment_id', 'visitor_hash']);
            });
        }

        // 4. Centralized Conversion Events Table
        if (!Schema::hasTable('conversion_events')) {
            Schema::create('conversion_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_name', 60)->index();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
                $table->string('session_id', 100)->nullable()->index();
                $table->string('anonymous_id', 100)->nullable()->index();
                $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
                $table->foreignId('bundle_id')->nullable()->constrained('bundles')->nullOnDelete();
                $table->foreignId('experiment_id')->nullable()->constrained('experiments')->nullOnDelete();
                $table->foreignId('variant_id')->nullable()->constrained('experiment_variants')->nullOnDelete();
                $table->string('url_path', 255)->nullable();
                $table->string('referrer', 500)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversion_events');
        Schema::dropIfExists('experiment_exposures');
        Schema::dropIfExists('experiment_variants');
        Schema::dropIfExists('experiments');
    }
};
