<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Fills a fresh database with the default permissions, the first admin account, and TEMPORARY sample SB members (5). */
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

        // TEMPORARY sample SB members, so the staff pages can be tried right away.
        // All use the password "sbmember". Delete these accounts (Admin > Accounts) or remove this block before real use.
        // An existing sample account keeps its password, so a changed password is not reset by running the seeder again.
        // (Dummy addresses: nothing is ever emailed to them.)
        $samples = [
            ['John Smith',       'johnsmith123@gmail.com'],
            ['Maria Santos',     'mariasantos123@gmail.com'],
            ['Juan Dela Cruz',   'juandelacruz123@gmail.com'],
            ['Ana Reyes',        'anareyes123@gmail.com'],
            ['Pedro Garcia',     'pedrogarcia123@gmail.com'],
        ];

        // Remove the first sample accounts (sb1/sb2/sb3) if an earlier seed created them
        User::whereIn('email', ['sb1@victoria.test', 'sb2@victoria.test', 'sb3@victoria.test'])->where('role', 'sb_member')->delete();

        foreach ($samples as [$name, $email]) {
            $sb = User::firstOrNew(['email' => $email]);
            $isNew = ! $sb->exists;

            $sb->forceFill(['name' => $name, 'role' => 'sb_member', 'is_active' => true]);
            if ($isNew) {
                $sb->password = 'sbmember';
                $sb->password_changed_at = now();
            }
            $sb->save();
        }

        // A few demo ordinances so the matcher has something to suggest
        $this->call(DemoOrdinanceSeeder::class);

        AuditLog::create([
            'actor_id' => $admin->id, 'actor_name' => $admin->name, 'type' => 'audit',
            'event' => 'Role assigned', 'detail' => 'Administrator access',
        ]);
    }
}
