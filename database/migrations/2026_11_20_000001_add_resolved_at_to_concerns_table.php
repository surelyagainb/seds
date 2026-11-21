<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create the tables or columns. */
    public function up(): void
    {
        Schema::table('concerns', function (Blueprint $t) {
            $t->timestamp('resolved_at')->nullable()->after('reviewed_at');
        });
    }

    /** Undo what up() did (php artisan migrate:rollback). */
    public function down(): void
    {
        Schema::table('concerns', function (Blueprint $t) {
            $t->dropColumn('resolved_at');
        });
    }
};
