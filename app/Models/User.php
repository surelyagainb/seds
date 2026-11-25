<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** An account. Admins, SB members and citizens are all stored here, told apart by the role column. */
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'first_name', 'last_name', 'username', 'employee_id',
        'email', 'mobile', 'password', 'must_change_password', 'is_active', 'status',
        'password_changed_at', 'verified_at', 'terms_accepted_at', 'last_login_at',
        'role', 'assigned_categories',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'             => 'hashed',
            'must_change_password' => 'boolean',
            'is_active'            => 'boolean',
            'password_changed_at'  => 'datetime',
            'verified_at'          => 'datetime',
            'terms_accepted_at'    => 'datetime',
            'last_login_at'        => 'datetime',
            'verification_sent_at' => 'datetime',
            'assigned_categories'  => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSbMember(): bool
    {
        return $this->role === 'sb_member';
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            'admin' => 'admin.overview',
            'sb_member' => 'staff.dashboard',
            'citizen' => 'citizen.dashboard',
            default => abort(403, 'This account has an invalid role.'),
        };
    }

    public function roleLabel(): string
    {
        return config("admin.roles.{$this->role}.label", ucfirst($this->role));
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function allows(string $permission): bool
    {
        if ($this->isAdmin()) return true;

        return RolePermission::mapFor($this->role)[$permission] ?? false;
    }

    public static function otherActiveAdminExists(User $except): bool
    {
        return static::where('role', 'admin')->where('is_active', true)->where('id', '!=', $except->id)->exists();
    }
}
