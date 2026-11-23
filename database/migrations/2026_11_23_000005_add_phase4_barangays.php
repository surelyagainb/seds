<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $coordinates = config('concerns.barangay_coordinates', []);
        foreach (config('concerns.barangays', []) as $name) {
            $point = $coordinates[$name] ?? [13.177, 121.278];
            DB::table('barangays')->insert([
                'name' => $name,
                'latitude' => $point[0],
                'longitude' => $point[1],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};
