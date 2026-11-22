<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Fills a fresh database with the default permissions and the first admin account. */
class DatabaseSeeder extends Seeder
{
    /** Run with: php artisan db:seed */
    public function run(): void
    {
        foreach (config('admin.defaults') as $role => $perms) {
            foreach ($perms as $permission => $allowed) {
                RolePermission::updateOrCreate(['role' => $role, 'permission' => $permission], ['allowed' => $allowed]);
            }
        }

        // First administrator. Sign in, then change this password right away.
        $admin = User::firstOrNew(['email' => 'admin@victoria.test']);
        $admin->forceFill([
            'name'                => 'Alex Morgan',
            'password'            => 'admin',
            'role'                => 'admin',
            'is_active'           => true,
            'password_changed_at' => now(),
        ])->save();

        // A few demo ordinances so the matcher has something to suggest
        $this->call(DemoOrdinanceSeeder::class);

        AuditLog::create([
            'actor_id' => $admin->id, 'actor_name' => $admin->name, 'type' => 'audit',
            'event' => 'Role assigned', 'detail' => 'Administrator access',
        ]);
    }
}
