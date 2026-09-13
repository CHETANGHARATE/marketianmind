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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('company_name', 255)->nullable()->after('phone');
            $table->string('job_title', 255)->nullable()->after('company_name');
            $table->string('city', 100)->nullable()->after('job_title');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('country', 100)->nullable()->default('India')->after('state');
            $table->foreignId('bundle_id')->nullable()->after('course_id')->constrained('bundles')->nullOnDelete();
            $table->string('priority', 20)->default('medium')->after('status')->index();
            $table->foreignId('assigned_to')->nullable()->after('priority')->constrained('users')->nullOnDelete()->index();
            $table->timestamp('next_follow_up_at')->nullable()->after('notes')->index();
            $table->string('follow_up_status', 20)->nullable()->after('next_follow_up_at');
            $table->timestamp('last_contacted_at')->nullable()->after('follow_up_status');
            $table->timestamp('converted_at')->nullable()->after('last_contacted_at');
            $table->foreignId('converted_user_id')->nullable()->after('converted_at')->constrained('users')->nullOnDelete();

            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['bundle_id']);
            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['converted_user_id']);
            $table->dropColumn([
                'company_name',
                'job_title',
                'city',
                'state',
                'country',
                'bundle_id',
                'priority',
                'assigned_to',
                'next_follow_up_at',
                'follow_up_status',
                'last_contacted_at',
                'converted_at',
                'converted_user_id',
            ]);
        });
    }
};
