<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('email_otps')->whereIn('purpose', ['login', 'registration'])->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_otp_verified_at', 'invitation_code']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('login_otp_verified_at')->nullable();
            $table->string('invitation_code', 64)->nullable();
        });
    }
};
