# Victoria Backend

The backend for the Victoria, Oriental Mindoro community concern system.
It has **two sides**, and the same login page sends you to the right one:

- **Citizens** report community concerns (like flooding or garbage) and track them.
- **Administrators** check the activity log and manage accounts.

Built with Laravel 13 and MySQL. The pages are plain server-made pages, so there is nothing extra to build or install for the frontend.

---

## 1. How to run it

You need PHP 8.3+, Composer, and MySQL (Laragon has all three).

1. Open a terminal inside this folder and install the packages:
   ```
   composer install
   ```
2. Make your settings file and the app key:
   ```
   copy .env.example .env
   php artisan key:generate
   ```
   (On Mac/Linux, use `cp` instead of `copy`.)
3. Create an empty MySQL database named `victoria_db`.
   Then open `.env` and check the `DB_USERNAME` and `DB_PASSWORD` lines match your MySQL.
4. Create the tables and the first admin account:
   ```
   php artisan migrate:fresh --seed
   ```
5. Start the app:
   ```
   php artisan serve
   ```
6. Open http://127.0.0.1:8000

> If you already ran step 4 before and only pulled new code, run `php artisan migrate` instead.
> `migrate:fresh` **deletes everything** in the database first.

---

## 2. Logging in

| Who | How to get an account | Where you land after login |
|---|---|---|
| **Citizen** | Click "Create an account" on the login page (`/register`) | My Dashboard (`/dashboard`) |
| **Administrator** | Created by the seeder: `admin@victoria.test` / `admin` | Admin area (`/admin`) |

- Change the admin password right away (Admin > Password settings). `admin` is only a starting password.
- Each side is closed to the other. An admin opening a citizen page is sent to `/admin`, and a citizen opening `/admin` is sent to their dashboard.
- Nobody picks their own role. Everyone who registers is a citizen.

---

## 3. What citizens can do

| Page | URL | What it does |
|---|---|---|
| My Dashboard | `/dashboard` | Shows how many concerns you sent and their status, plus your 5 newest |
| Submit a concern | `/concerns/create` | Step 1: fill in barangay, description, location, photo, categories |
| Review | `/concerns/review` | Step 2: check everything, tick the box, submit |
| Concern received | `/concerns/{reference}/confirmation` | Shows your reference number (like `VIC-2026-123456`) |
| Check status | `/status` | Look up a reference number and see the 4-stage tracker |

Good to know:
- You can only see **your own** concerns.
- Your answers are kept between step 1 and step 2, so "Edit details" does not lose anything.
- Photos are saved privately (not public) in `storage/app/private`.
- Statuses on the dashboard:
  - **Under Review**: sent, not reviewed yet
  - **Reviewed**: staff reviewed it
  - **Resolved**: marked as resolved
- Analysis, ordinance matching, and staff review are **not built yet**. For now every concern stays "Under Review" and the Reviewed / Resolved counts stay at 0.

---

## 4. What administrators can do

| Page | URL | What it does |
|---|---|---|
| Administration | `/admin` | Your account, your role, latest activity |
| Password settings | `/admin/password` | Change your password (12+ characters, with a number and a symbol). Other devices get signed out |
| Audit trail / History | `/admin/audit` | Last 30 days of events, with a CSV download |
| Accounts | `/admin/accounts` | See all accounts and remove them |

What admins **cannot** do (on purpose):
- Change anyone's role or make someone an administrator.
- Add accounts or edit permissions.
- Remove an administrator account.

Roles will only be given through an **office request**. That part is not built yet.

The two log tabs:
- **Audit trail**: security things (password changed, account removed).
- **History**: everyday things (signed in, signed out, concern submitted, CSV exported).

---

## 5. Where things are (folder map)

| Folder / file | What is inside |
|---|---|
| `routes/web.php` | Every page address, and who is allowed to open it |
| `app/Http/Controllers/` | The code behind each page. `ConcernController` = citizen pages, `AuthController` = login, `RegisterController` = sign up |
| `app/Http/Controllers/Admin/` | The code behind the admin pages |
| `app/Http/Middleware/` | Checks that run before a page opens (is the account active? is this an admin?) |
| `app/Models/` | The database tables as code: `User`, `Concern`, `AuditLog`, `RolePermission` |
| `app/Services/AuditLogger.php` | Writes entries to the audit trail / history |
| `database/migrations/` | Creates the tables (`users`, `concerns`, `audit_logs`, ...) |
| `database/seeders/` | Creates the first admin account |
| `config/concerns.php` | The barangay list and the concern categories |
| `config/admin.php` | Role names and permission settings |
| `resources/views/` | The pages themselves (HTML). `admin/` = admin pages, `concerns/` = citizen pages, `auth/` = login and register |
| `public/css/` | The styles: `admin.css` and `citizen.css` |

Every controller, model, and middleware has a short comment above each function explaining what it does.

---

## 6. Not built yet

- Adding and managing ordinances
- Rule-based matching of concerns to ordinances (the "Analyzed" and "Matched" stages)
- Staff review of matches (the "Reviewed" stage)
- The office-request flow for giving roles
- Email verification (nothing is emailed to new accounts)
