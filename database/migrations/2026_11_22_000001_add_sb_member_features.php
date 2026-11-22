<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Create the tables or columns. */
    public function up(): void
    {
        // Ordinances: add a status, version, date enacted and the section texts.
        // The old is_active yes/no is replaced by the status (Draft = not used for matching).
        Schema::table('ordinances', function (Blueprint $t) {
            $t->string('status', 20)->default('Active')->after('category');   // Active | Under review | Draft
            $t->string('version', 10)->default('v1')->after('status');
            $t->date('enacted_on')->nullable()->after('version');
            $t->json('sections')->nullable()->after('summary');                // [{n, title, text}]
        });
        DB::table('ordinances')->where('is_active', false)->update(['status' => 'Draft']);
        Schema::table('ordinances', function (Blueprint $t) {
            $t->dropColumn('is_active');
        });

        // Concerns: what SB members decided
        Schema::table('concerns', function (Blueprint $t) {
            $t->string('decision', 20)->nullable()->after('resolved_at');     // confirmed | rejected | unmatched
            $t->foreignId('matched_ordinance_id')->nullable()->after('decision')->constrained('ordinances')->nullOnDelete();
            $t->foreignId('reviewed_by')->nullable()->after('matched_ordinance_id')->constrained('users')->nullOnDelete();
            $t->timestamp('viewed_at')->nullable()->after('reviewed_by');      // first time staff opened it
            $t->string('assigned_to')->nullable()->after('viewed_at');
            $t->boolean('suggest_new')->default(false)->after('assigned_to');  // flagged as a possible new ordinance
        });

        // Activity log shown on each concern ("Viewed by staff", "Match confirmed", ...)
        Schema::create('concern_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('concern_id')->constrained('concerns')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('text', 600);
            $t->timestamps();
        });

        // Recurring issues that staff flagged
        Schema::create('issue_flags', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20);                                            // review | new_ordinance
            $t->string('barangay', 60);
            $t->string('category', 20);
            $t->foreignId('ordinance_id')->nullable()->constrained('ordinances')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['type', 'barangay', 'category']);
        });
    }

    /** Undo what up() did (php artisan migrate:rollback). */
    public function down(): void
    {
        Schema::dropIfExists('issue_flags');
        Schema::dropIfExists('concern_logs');

        Schema::table('concerns', function (Blueprint $t) {
            $t->dropConstrainedForeignId('matched_ordinance_id');
            $t->dropConstrainedForeignId('reviewed_by');
            $t->dropColumn(['decision', 'viewed_at', 'assigned_to', 'suggest_new']);
        });

        Schema::table('ordinances', function (Blueprint $t) {
            $t->boolean('is_active')->default(true);
        });
        DB::table('ordinances')->where('status', 'Draft')->update(['is_active' => false]);
        Schema::table('ordinances', function (Blueprint $t) {
            $t->dropColumn(['status', 'version', 'enacted_on', 'sections']);
        });
    }
};
