<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\ConcernCategory;
use App\Models\Barangay;

/** Place for app-wide setup. Nothing is needed here yet. */
class AppServiceProvider extends ServiceProvider
{
    /** Runs first: register services (none yet). */
    public function register(): void {}

    /** Runs after everything is registered: app-wide setup (none yet). */
    public function boot(): void
    {
        if (app()->runningInConsole() && ! env('DB_CONNECTION')) return;
        try {
            if (Schema::hasTable('concern_categories')) {
                $categories = [];
                foreach (ConcernCategory::where('is_active', true)->get() as $category) {
                    $categories[$category->key] = [
                        'label' => $category->name,
                        'cls' => $category->key === 'flood' ? 'flood' : ($category->key === 'health' ? 'health' : ($category->key === 'road' ? 'road' : ($category->key === 'other' ? 'other' : 'waste'))),
                        'kw' => array_merge($category->keywords_en ?: [], $category->keywords_fil ?: []),
                    ];
                }
                if ($categories) config(['concerns.categories' => $categories]);
            }
            if (Schema::hasTable('barangays')) {
                $barangays = Barangay::where('is_active', true)->orderBy('name')->pluck('name')->all();
                $coordinates = Barangay::where('is_active', true)->get()->mapWithKeys(fn ($barangay) => [$barangay->name => [(float)$barangay->latitude,(float)$barangay->longitude]])->all();
                if ($barangays) config(['concerns.barangays' => $barangays, 'concerns.barangay_coordinates' => $coordinates]);
            }
        } catch (\Throwable) {
            // Keep configured fallback values during migrations and setup.
        }
    }
}
