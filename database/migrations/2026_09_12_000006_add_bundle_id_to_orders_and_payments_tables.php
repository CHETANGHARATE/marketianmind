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
            $table->foreignId('course_id')->nullable()->change();
            $table->foreignId('bundle_id')->nullable()->after('course_id')->constrained('bundles')->nullOnDelete();
            $table->index('bundle_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->change();
            $table->foreignId('bundle_id')->nullable()->after('course_id')->constrained('bundles')->nullOnDelete();
            $table->index('bundle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['bundle_id']);
            $table->dropIndex(['bundle_id']);
            $table->dropColumn('bundle_id');
            $table->foreignId('course_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['bundle_id']);
            $table->dropIndex(['bundle_id']);
            $table->dropColumn('bundle_id');
            $table->foreignId('course_id')->nullable(false)->change();
        });
    }
};
