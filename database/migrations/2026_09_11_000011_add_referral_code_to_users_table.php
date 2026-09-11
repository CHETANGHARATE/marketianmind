<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 32)->nullable()->unique()->after('role');
        });

        // Populate existing users with unique referral codes
        if (Schema::hasTable('users')) {
            $users = DB::table('users')->whereNull('referral_code')->get();
            foreach ($users as $user) {
                $code = 'MM' . strtoupper(Str::random(6));
                while (DB::table('users')->where('referral_code', $code)->exists()) {
                    $code = 'MM' . strtoupper(Str::random(6));
                }
                DB::table('users')->where('id', $user->id)->update(['referral_code' => $code]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });
    }
};