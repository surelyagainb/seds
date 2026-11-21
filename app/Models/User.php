<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** An account. Admins and citizens are both stored here, told apart by the role column. */
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'is_active', 'password_changed_at'];
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
        ];
    }

    /** True if this account is an administrator. */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Where this account lands after signing in. */
    public function homeRoute(): string
    {
        return $this->isAdmin() ? 'admin.overview' : 'citizen.dashboard';
    }

    /** The role as readable text, like "Citizen". */
    public function roleLabel(): string
    {
        return config("admin.roles.{$this->role}.label", ucfirst($this->role));
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
