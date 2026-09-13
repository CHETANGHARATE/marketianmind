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
        // 1. Orders table composite indexes
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Accelerates order listing filters and revenue/sales aggregation
                $table->index(['status', 'created_at'], 'orders_status_created_at_idx');
                // Accelerates student order history timeline
                $table->index(['user_id', 'created_at'], 'orders_user_id_created_at_idx');
            });
        }

        // 2. Payments table composite indexes
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                // Accelerates admin payment logs and financial reporting
                $table->index(['status', 'created_at'], 'payments_status_created_at_idx');
                // Accelerates student payment queries
                $table->index(['user_id', 'created_at'], 'payments_user_id_created_at_idx');
            });
        }

        // 3. Conversion events table composite indexes
        if (Schema::hasTable('conversion_events')) {
            Schema::table('conversion_events', function (Blueprint $table) {
                // Accelerates conversion funnel analytics and date-range counts
                $table->index(['event_name', 'occurred_at'], 'conv_events_name_occurred_idx');
                // Accelerates A/B test variant attribution queries
                $table->index(['experiment_id', 'variant_id'], 'conv_events_exp_var_idx');
            });
        }

        // 4. Leads table composite indexes
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                // Accelerates CRM dashboard and pipeline filters
                $table->index(['status', 'created_at'], 'leads_status_created_at_idx');
                // Accelerates lead source analytics
                $table->index(['source', 'created_at'], 'leads_source_created_at_idx');
            });
        }

        // 5. Audit logs table composite indexes
        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                // Accelerates admin audit trail filtering by resource type and date
                $table->index(['auditable_type', 'created_at'], 'audit_logs_type_created_at_idx');
            });
        }

        // 6. Enrollments table composite index
        if (Schema::hasTable('enrollments')) {
            Schema::table('enrollments', function (Blueprint $table) {
                // Accelerates enrollment analytics and activity timelines
                $table->index(['status', 'created_at'], 'enrollments_status_created_at_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('enrollments')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropIndex('enrollments_status_created_at_idx');
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('audit_logs_type_created_at_idx');
            });
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropIndex('leads_source_created_at_idx');
                $table->dropIndex('leads_status_created_at_idx');
            });
        }

        if (Schema::hasTable('conversion_events')) {
            Schema::table('conversion_events', function (Blueprint $table) {
                $table->dropIndex('conv_events_exp_var_idx');
                $table->dropIndex('conv_events_name_occurred_idx');
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('payments_user_id_created_at_idx');
                $table->dropIndex('payments_status_created_at_idx');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_user_id_created_at_idx');
                $table->dropIndex('orders_status_created_at_idx');
            });
        }
    }
};
