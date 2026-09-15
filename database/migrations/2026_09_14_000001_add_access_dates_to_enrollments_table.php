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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('status')->index();
            $table->timestamp('expires_at')->nullable()->after('starts_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['starts_at']);
            $table->dropColumn(['starts_at', 'expires_at']);
        });
    }
};
