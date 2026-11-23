<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Store one active OTP per email address and purpose. */
    public function up(): void
    {
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190);
            $table->string('purpose', 30);
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('last_sent_at');
            $table->timestamps();
            $table->unique(['email', 'purpose']);
        });
    }

    /** Remove the OTP table. */
    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
