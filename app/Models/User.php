<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** An account. Admins, SB members and citizens are all stored here, told apart by the role column. */
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'username', 'email', 'mobile', 'password', 'is_active', 'status',
        'password_changed_at', 'verified_at', 'last_login_at', 'role', 'assigned_categories',
    ];
    // 'role' is deliberately not fillable: it can only be set explicitly (e.g. by the seeder
    // or a future approved office-request flow), never through form input.
    protected $hidden = ['password', 'remember_token'];

    /** How columns are converted: password is hashed automatically, dates become date objects. */
    protected function casts(): array
    {
        return [
            'password'            => 'hashed',
            'is_active'           => 'boolean',
            'password_changed_at' => 'datetime',
            'verified_at'          => 'datetime',
            'last_login_at'        => 'datetime',
            'verification_sent_at' => 'datetime',
            'assigned_categories'  => 'array',
        ];
    }

    /** True if this account is an administrator. */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** True if this account is a Sangguniang Bayan (SB) member. */
    public function isSbMember(): bool
    {
        return $this->role === 'sb_member';
    }

    /** Where this account lands after signing in: admin area, SB member (staff) area, or citizen dashboard. */
    public function homeRoute(): string
    {
        if ($this->isAdmin())    return 'admin.overview';
        if ($this->isSbMember()) return 'staff.dashboard';

        return 'citizen.dashboard';
    }

    /** The role as readable text, like "Citizen". */
    public function roleLabel(): string
    {
        return config("admin.roles.{$this->role}.label", ucfirst($this->role));
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /** Administrators can always do everything; other roles follow the role_permissions table. */
    public function allows(string $permission): bool
    {
        if ($this->isAdmin()) return true;

        return RolePermission::mapFor($this->role)[$permission] ?? false;
    }

    /** True if another active admin exists besides the given one. */
    public static function otherActiveAdminExists(User $except): bool
    {
        return static::where('role', 'admin')->where('is_active', true)->where('id', '!=', $except->id)->exists();
    }
}
