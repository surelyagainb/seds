# Victoria backend: admin area

Laravel 13 + MySQL (PHP 8.3+). Server-rendered Blade admin area (no npm build needed), based on the four mockups:
Administration overview, Password settings, Permissions, Audit trail / History.

## Setup (Laragon, runs with `php artisan serve`)

This is a complete Laravel 13 project: only `vendor/` is missing.

1. Open a terminal in this folder and install the packages (needs PHP 8.3+ and Composer):
       composer install
2. Create the environment file and app key:
       copy .env.example .env        (macOS/Linux: cp .env.example .env)
       php artisan key:generate
3. Create an empty MySQL database `victoria_db` (Laragon: HeidiSQL, or `mysql -u root -e "CREATE DATABASE victoria_db"`).
   Edit `.env` if your MySQL user or password is not root with no password.
4. Create the tables and the first administrator:
       php artisan migrate:fresh --seed
5. Start the server:
       php artisan serve
6. Open http://127.0.0.1:8000 and sign in with `admin@victoria.test` / `ChangeMe!2026`.
   Change that password first under Password settings.

## What is in it

| Page | URL | Who |
|---|---|---|
| Administration (account, role, recent activity) | /admin | administrators |
| Password settings | /admin/password | administrators (own account) |
| Audit trail / History (+ Export CSV, last 30 days) | /admin/audit | administrators |
| Accounts (check and remove only) | /admin/accounts | administrators |

- The admin area is administrator-only. Admins can view the audit log, check accounts, and remove non-admin accounts. They cannot add accounts, change roles, or edit permissions; roles will only be granted through an approved office request (not built yet). `role` is not mass-assignable on the User model.
- Password rule: at least 12 characters with a number and symbol. Changing it signs out other sessions.
- Audit trail records security changes (password, permissions, roles, deactivation). History records general activity (sign in/out, CSV exports).
- Administrator accounts cannot be removed from the UI.
- Settings (brand name, role labels, permission list, default permissions): `config/admin.php`.
- "Edit workspace settings" is a permission flag only; there is no workspace settings page yet.
