<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('login_otp_verified_at')->nullable()->after('last_login_at');
        });

        DB::table('users')
            ->whereNotNull('last_login_at')
            ->update(['login_otp_verified_at' => DB::raw('last_login_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('login_otp_verified_at');
        });
    }
};
