<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create the tables or columns. */
    public function up(): void
    {
        // The ordinances the matcher can suggest
        Schema::create('ordinances', function (Blueprint $t) {
            $t->id();
            $t->string('number', 40)->nullable()->unique();   // e.g. "DEMO-001"
            $t->string('title');
            $t->string('category', 20)->index();               // one of the keys in config/concerns.php
            $t->json('keywords');                              // words that signal this ordinance
            $t->text('summary')->nullable();
            $t->boolean('is_active')->default(true);           // inactive ordinances are never suggested
            $t->timestamps();
        });

        // The suggestions the matcher made for each concern (for SB members to review)
        Schema::create('concern_matches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('concern_id')->constrained('concerns')->cascadeOnDelete();
            $t->foreignId('ordinance_id')->constrained('ordinances')->cascadeOnDelete();
            $t->unsignedSmallInteger('score');
            $t->string('confidence', 10);                      // high | medium | low
            $t->json('reasons');                               // plain-text reasons for the score
            $t->timestamps();
            $t->unique(['concern_id', 'ordinance_id']);
        });
    }

    /** Undo what up() did (php artisan migrate:rollback). */
    public function down(): void
    {
        Schema::dropIfExists('concern_matches');
        Schema::dropIfExists('ordinances');
    }
};
