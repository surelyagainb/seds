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
            $table->string('email')->nullable()->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('mobile', 32)->nullable()->unique()->after('email');
            $table->string('status', 20)->default('active')->after('is_active');
            $table->timestamp('verified_at')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('verified_at');
            $table->string('verification_code', 8)->nullable()->after('last_login_at');
            $table->timestamp('verification_sent_at')->nullable()->after('verification_code');
            $table->json('assigned_categories')->nullable()->after('verification_sent_at');
        });

        DB::table('users')->orderBy('id')->each(function ($user) {
            $base = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', trim($user->name)) ?: 'user');
            $username = $base;
            $suffix = 1;
            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base.$suffix++;
            }

            DB::table('users')->where('id', $user->id)->update([
                'username' => $username,
                'status' => $user->is_active ? 'active' : 'deactivated',
                'verified_at' => $user->is_active ? now() : null,
            ]);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type', 32)->default('system');
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['mobile']);
            $table->dropColumn([
                'username', 'mobile', 'status', 'verified_at', 'last_login_at',
                'verification_code', 'verification_sent_at', 'assigned_categories',
            ]);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
