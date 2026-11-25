<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\ConcernCategory;
use Illuminate\Database\Seeder;

/** Fills a fresh database with the default permissions, the first admin account, and TEMPORARY sample SB members (5) & citizens (5). */
class DatabaseSeeder extends Seeder
{
    /** Run with: php artisan db:seed */
    public function run(): void
    {
        foreach ([
            ['key'=>'litter','name'=>'Improper Garbage Disposal & Littering','color'=>'#1f6b45','keywords_en'=>['garbage','trash','waste','litter','throw'],'keywords_fil'=>['basura','kalat']],
            ['key'=>'dumping','name'=>'Illegal Dumping','color'=>'#1f6b45','keywords_en'=>['dump','trash','waste'],'keywords_fil'=>['tambak','itapon','basura']],
            ['key'=>'collection','name'=>'Uncollected Garbage & Waste Collection','color'=>'#1f6b45','keywords_en'=>['collection','garbage','trash'],'keywords_fil'=>['kolekta','hindi kinuha','basura']],
            ['key'=>'flood','name'=>'Drainage & Flooding','color'=>'#2a5a8c','keywords_en'=>['flood','drain','clog','canal'],'keywords_fil'=>['baha','kanal','baradong','sambak']],
            ['key'=>'health','name'=>'Sanitation & Public Hygiene','color'=>'#a1274b','keywords_en'=>['sanitation','dengue','mosquito','sewage'],'keywords_fil'=>['lamok','malinis','dengue']],
            ['key'=>'road','name'=>'Road or Street Damage','color'=>'#8a5a00','keywords_en'=>['road','pothole','street'],'keywords_fil'=>['lubak','sirang kalsada','daan']],
            ['key'=>'fire','name'=>'Open or Dangerous Burning','color'=>'#8a5a00','keywords_en'=>['burning','fire','trash'],'keywords_fil'=>['sunog','nasusunog']],
            ['key'=>'other','name'=>'Others','color'=>'#4b3f8f','keywords_en'=>[],'keywords_fil'=>[]],
        ] as $category) {
            ConcernCategory::updateOrCreate(['key'=>$category['key']], $category);
        }
        foreach (config('admin.defaults') as $role => $perms) {
            foreach ($perms as $permission => $allowed) {
                RolePermission::updateOrCreate(['role' => $role, 'permission' => $permission], ['allowed' => $allowed]);
            }
        }

        // First administrator. Use a monitored inbox for account recovery.
        $admin = User::where('role', 'admin')->orderBy('id')->first();

        if (! $admin) {
            $admin = new User();
            $admin->forceFill([
                'name'                => 'Alex Morgan',
                'username'            => 'admin',
                'email'               => env('ADMIN_EMAIL', 'admin@victoria.test'),
                'password'            => 'admin',
                'role'                => 'admin',
                'is_active'           => true,
                'status'              => 'active',
                'verified_at'         => now(),
                'password_changed_at' => now(),
            ])->save();
        } else {
            $admin->forceFill([
                'username' => $admin->username ?: 'admin',
                'email' => env('ADMIN_EMAIL', $admin->email ?: 'admin@victoria.test'),
                'verified_at' => $admin->verified_at ?: now(),
            ])->save();
        }

        // SB members and citizens are created by registration or through the admin account form.

        // A few demo ordinances so the matcher has something to suggest
        $this->call(DemoOrdinanceSeeder::class);

        AuditLog::create([
            'actor_id' => $admin->id, 'actor_name' => $admin->name, 'type' => 'audit',
            'event' => 'Role assigned', 'detail' => 'Administrator access',
        ]);
    }
}