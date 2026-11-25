<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 80)->nullable()->after('name');
            $table->string('last_name', 80)->nullable()->after('first_name');
            $table->string('employee_id', 60)->nullable()->unique()->after('username');
            $table->string('invitation_code', 64)->nullable()->after('employee_id');
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->timestamp('terms_accepted_at')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['employee_id']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'employee_id',
                'invitation_code',
                'must_change_password',
                'terms_accepted_at',
            ]);
        });
    }
};
