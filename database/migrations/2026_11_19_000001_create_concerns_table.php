<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create the tables or columns. */
    public function up(): void
    {
        Schema::create('concerns', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 20)->unique();           // VIC-2026-123456
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('barangay', 60);
            $t->text('description');
            $t->string('location', 150)->nullable();
            $t->json('categories');                           // e.g. ["waste","flood"]
            $t->string('photo_path');
            $t->string('photo_name');
            $t->timestamp('submitted_at');
            $t->timestamp('analyzed_at')->nullable();
            $t->timestamp('matched_at')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'submitted_at']);
        });
    }

    /** Undo what up() did (php artisan migrate:rollback). */
    public function down(): void
    {
        Schema::dropIfExists('concerns');
    }
};
