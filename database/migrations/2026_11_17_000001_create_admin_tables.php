<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $t) {
            $t->id();
            $t->string('role', 32);
            $t->string('permission', 64);
            $t->boolean('allowed')->default(false);
            $t->timestamps();
            $t->unique(['role', 'permission']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('actor_name')->default('System'); // kept so history survives user changes
            $t->string('type', 16);                      // audit (security changes) | history (general activity)
            $t->string('event');
            $t->string('detail')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamps();
            $t->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('role_permissions');
    }
};
