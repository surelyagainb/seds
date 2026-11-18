<?php

return [
    'brand' => env('ADMIN_BRAND', 'folio'),

    'roles' => [
        'admin'  => ['label' => 'Administrator', 'description' => 'Reviews the audit trail, checks accounts, and removes accounts.'],
        'member' => ['label' => 'Member',        'description' => 'Works in the workspace with the access you allow.'],
        'viewer' => ['label' => 'Viewer',        'description' => 'Read-only access with the permissions you allow.'],
    ],

    'permissions' => [
        'manage_members'    => ['label' => 'Manage members',          'description' => 'Invite, remove, and change member roles.'],
        'edit_settings'     => ['label' => 'Edit workspace settings', 'description' => 'Update account and workspace preferences.'],
        'view_audit'        => ['label' => 'View audit trail & history', 'description' => 'Review activity and account changes.'],
        'deactivate_account'=> ['label' => 'Deactivate account',      'description' => 'Disable account access while keeping history.'],
    ],

    // Starting permissions for the editable roles (the admin role is always allowed everything).
    'defaults' => [
        'member' => ['manage_members' => false, 'edit_settings' => true,  'view_audit' => true,  'deactivate_account' => false],
        'viewer' => ['manage_members' => false, 'edit_settings' => false, 'view_audit' => false, 'deactivate_account' => false],
    ],
];
