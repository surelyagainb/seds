<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concerns', function (Blueprint $table) {
            $table->timestamp('reminder_snoozed_at')->nullable();
            $table->timestamp('match_confirmed_at')->nullable();
            $table->text('rejection_reason')->nullable()->change();
        });

        Schema::create('concern_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('name', 120);
            $table->string('color', 20)->default('#64748b');
            $table->json('keywords_en')->nullable();
            $table->json('keywords_fil')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concern_categories');
        Schema::table('concerns', function (Blueprint $table) {
            $table->dropColumn(['reminder_snoozed_at', 'match_confirmed_at']);
        });
    }
};
