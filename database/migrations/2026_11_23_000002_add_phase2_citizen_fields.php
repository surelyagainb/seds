<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('concerns', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('reports_count')->default(1)->after('longitude');
            $table->foreignId('canonical_concern_id')->nullable()->after('reports_count')->constrained('concerns')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('decision');
            $table->unsignedBigInteger('photo_size')->default(0)->after('photo_name');
        });
    }

    public function down(): void
    {
        Schema::table('concerns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canonical_concern_id');
            $table->dropColumn([
                'latitude', 'longitude', 'reports_count', 'rejection_reason', 'photo_size',
            ]);
        });
    }
};
