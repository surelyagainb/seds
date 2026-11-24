# Victoria Backend

The backend for the Victoria, Oriental Mindoro community concern system.
It has **three sides**, and the same login page sends you to the right one:

- **Citizens** report community concerns (like flooding or garbage) and track them.
- **SB members** (Sangguniang Bayan staff) review the concerns, check the suggested ordinances, and manage the ordinance list.
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
   Then open `.env` and check the `DB_USERNAME` and `DB_PASSWORD` lines match your MySQL. Set your Gmail SMTP values and `ADMIN_EMAIL` as explained in section 2 before continuing.
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

### Moving the project to another computer

- **Do not copy `.env`** to another computer. It holds your own passwords (database, Gmail App Password). On the new computer, copy `.env.example` to `.env`, fill in that computer's values, and run `php artisan key:generate`.
- **Do not copy generated files**: `bootstrap/cache/*.php`, `storage/framework/views/*`, `storage/framework/cache/*`, `storage/logs/*`. They are rebuilt automatically, and they are ignored by git. (Copied ones can point to folders that only exist on the old computer.)
- The folders Laravel needs (`storage/framework/views`, `storage/logs`, `bootstrap/cache` and so on) are now created automatically on the first run, so an error like **"Please provide a valid cache path"** should not appear anymore. If you ever see it, run `php artisan view:clear` once.
- The project folder can be named anything. The recommended name is `victoria-backend`.

---

## 2. Email verification (Gmail OTP)

Registration and login both use a six-digit code sent by email. A new citizen account stays pending until its email code is verified. After registration, users sign in with their username, email, or mobile number and password, then enter the code sent to the email address on their account.

Every account that can sign in needs an email address. Mobile numbers remain optional contact information. The code expires after 5 minutes, can be used once, and allows up to 5 incorrect attempts. A new code can be requested after 60 seconds.

Before running `php artisan migrate:fresh --seed`, set `ADMIN_EMAIL` in `.env` to an inbox you can open. The seeded administrator uses that email address and needs it to receive login codes. The starter password is still `admin`; change it after the first sign in. If the database was already seeded, update the administrator's email through Admin > Accounts or set `ADMIN_EMAIL` and run `php artisan db:seed`; this updates the email without resetting an existing administrator password.

To use Gmail, open `.env` and set the mail values below. Use a Google **App Password**, not your normal Gmail password. Turn on 2-Step Verification in the Google account first, then create the App Password at https://myaccount.google.com/apppasswords.

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD="abcd efgh ijkl mnop"
MAIL_FROM_ADDRESS=your-gmail@gmail.com
MAIL_FROM_NAME="Victoria Admin"
```

`MAIL_USERNAME` and `MAIL_FROM_ADDRESS` should be the Gmail account used by the system to send verification codes. Users can receive the code at their own email address; they do not need to use Gmail. Keep `.env` private and do not upload it to GitHub.

**After every change to `.env`, run `php artisan config:clear`.** Laravel remembers the old values until you do.

### If the code does not arrive

Run this in the terminal. It shows the settings Laravel is really using, warns about the common mistakes, and sends a test email with the exact error if it fails:

```
php artisan mail:test
php artisan mail:test someone@example.com
```

While `APP_DEBUG=true`, the login and register pages also show what to fix. The full technical error is always written to `storage/logs/laravel.log`.

| What you see | What it means | Fix |
|---|---|---|
| "Email sending is switched off" | `MAIL_MAILER` is `log` or missing, so nothing is really sent | Set `MAIL_MAILER=smtp`, then `php artisan config:clear` |
| "Gmail is not set up yet" | `MAIL_USERNAME` / `MAIL_PASSWORD` are empty or still the examples | Fill them in with your Gmail and App Password |
| "Gmail rejected the login" (535) | Wrong password. A normal Gmail password does not work | Create a Google App Password (needs 2-Step Verification). It has 16 characters |
| Nothing happens or the page freezes | Port 587 is blocked or there is no internet | Check the connection, firewall and antivirus. Try `MAIL_PORT=465` with `MAIL_SCHEME=smtps` |
| "could not verify the security certificate" | PHP on Windows has no certificate list | Set `openssl.cafile` in `php.ini`. For local testing only: `MAIL_VERIFY_PEER=false` |
| The page says "sent" but no email arrives | The code went to another address, for example the seeded admin's `ADMIN_EMAIL` | Check the address shown on the verify page, and the Spam folder |
| `.env` error about whitespace | The App Password has spaces and no quotes | Put it in quotes: `MAIL_PASSWORD="abcd efgh ijkl mnop"` |

The `email_otps` table stores only a hash of each code. Run the new migration with `php artisan migrate` after updating an existing database.

---

## 3. Logging in

| Who | How to get an account | Where you land after login |
|---|---|---|
| **Citizen** | Click   "Create an account" on the login page (`/register`) | My Dashboard (`/dashboard`) |
| **SB member** | Created by an administrator or with the `php artisan sb:create` command. Their first sign-in requires an email code | Staff area (`/staff`) |
| **Administrator** | Created by the seeder using the `ADMIN_EMAIL` value in `.env`; starter password: `admin` | Admin area (`/admin`) |

- Change the admin password right away (Admin > Password settings). `admin` is only a starting password.
- Each side is closed to the others. If you open a page that belongs to another side, you are sent back to your own home page.
- Nobody picks their own role. Everyone who registers is a citizen.

**Creating a real SB member account** (run in the terminal, it asks for the password):
```
php artisan sb:create "Juan Dela Cruz" juan@example.com
```
The password needs 12+ characters with letters, numbers and a symbol. Later, SB accounts will be given through the office-request flow (not built yet).

---

## 4. What citizens can do

| Page | URL | What it does |
|---|---|---|
| My Dashboard | `/dashboard` | Shows how many concerns you sent and their status, plus your 5 newest |
| Submit a concern | `/concerns/create` | Step 1: fill in barangay, description, location, photo, categories |
| Review | `/concerns/review` | Step 2: check everything, tick the box, submit |
| Concern received | `/concerns/{reference}/confirmation` | Shows your reference number (like `VIC-2026-123456`) |
| Check status | `/status` | Look up a reference number and see the 4-stage tracker |

Good to know:
- You can only see **your own** concerns.
- **Search and filters** on the dashboard: type a word (reference, barangay, description) or pick a category (for example *Health / Sanitation* or *Waste Management*), a status, or a barangay. Press **Filter**; **Reset** clears it. Results show 10 per page.
- Your answers are kept between step 1 and step 2, so "Edit details" does not lose anything.
- Photos are saved privately (not public) in `storage/app/private`.
- Statuses on the dashboard:
  - **Under Review**: sent, not reviewed yet
  - **Reviewed**: staff reviewed it
  - **Resolved**: marked as resolved
- Analysis, ordinance matching, and staff review are **not built yet**. For now every concern stays "Under Review" and the Reviewed / Resolved counts stay at 0.

---

## 5. What SB members can do

Sign in at `/login`. You land on the **Community overview**.

| Page | URL | What it does |
|---|---|---|
| Community overview | `/staff` | Totals, a map of Victoria with a pin per barangay, a chart by month and category, and a table per barangay. Filter by barangay, category and 3 or 6 months |
| Concerns | `/staff/concerns` | Every concern. Search, and filter by category, status and barangay |
| One concern | `/staff/concerns/{reference}` | The suggested ordinances (with a score bar), the concern next to the best-fitting section of the ordinance with the matching words highlighted, and the decision buttons |
| Recurring issues | `/staff/recurring` | Problems reported in 2 or more different months in the same barangay. Flag them for review or for a new ordinance |
| Ordinances | `/staff/ordinances` | The ordinance list (search, category, status). Add a new ordinance with its keywords. Open one to read its text |
| Barangay directory | `/staff/barangays` | Counts per barangay, and a page for each barangay with its concerns and ordinances |

**What you can do with a concern:**
- **Confirm match** or **Reject** the selected ordinance (with an optional note). You can change your mind later.
- **Reassign** it to another office, with a reason.
- If nothing was suggested: **Suggest new ordinance**, **Manually link ordinance**, or **Mark as unmatched**.
- Every action is written to the concern's activity log, and to History for the admin.
- Confirm, reject, link and unmatched all move the citizen's tracker to **Reviewed**.

**Statuses used on the staff pages:**
- **Needs review**: suggestions exist, no decision yet
- **Reviewed**: match confirmed (or an ordinance linked by hand)
- **Rejected** / **Unmatched**: staff decided so
- **No ordinance found**: the matching found nothing

**Good to know:**
- "Matched" on the overview means the matching found at least one ordinance and staff did not mark it unmatched.
- "Ordinances flagged" counts the ordinances that staff flagged from the recurring issues page.
- The map only has pins for the 8 barangays listed in `config/staff.php`. Add more coordinates there.
- The map and charts load from the internet (OpenStreetMap, Leaflet, Chart.js), so they need a connection.
- "Barangay officials" is an empty list for now. No official records are loaded.

---

## 6. What administrators can do

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

## 7. Rule-based matching

This is the "brain" that suggests which ordinances fit a concern. It uses simple rules (no AI), so you can always explain *why* an ordinance was suggested. The SB member pages show these suggestions.

**The two rules** (numbers can be changed in `config/matching.php`):

| Rule | Points |
|---|---|
| 1. The concern's category is the same as the ordinance's category | +40 |
| 2. A keyword of the ordinance appears in the concern text (description + location) | +12 for each different keyword, up to 36 |

**Then:**
- Score under **24** = not suggested.
- **64 or more** = high confidence, **40 to 63** = medium, **24 to 39** = low.
- The best 5 are kept, highest score first.
- Ordinances with the status **Draft** are never suggested.
- The score bar on the staff page is the score as a percentage of the best possible score (76).
- Keywords also match word endings: `flood` finds "flooding", but `rain` does not find "train".

**Example:** the concern *"It floods because of garbage."* with categories *Waste Management* and *Flooding*:
- "Solid Waste Segregation and Collection": category match (40) + keyword "garbage" (12) = **52, medium**
- "Drainage and Canal Maintenance": category match (40) + keyword "flood" (12) = **52, medium**
- "Anti-Littering and Illegal Dumping": category match (40), no keywords = **40, medium**
- "Dengue Prevention and Community Sanitation": no category, no keywords = **0, not suggested**

**When it runs:**
- **Automatically** when a citizen submits a concern. The citizen's tracker then shows Analyzed, and Matched if something was found.
- When an SB member opens a concern that was never analyzed (for example one submitted before this was added).
- By hand: `php artisan concerns:match` (only concerns not analyzed yet) or `php artisan concerns:match --all` (again for every concern not reviewed yet). Concerns that staff already reviewed are never changed.

**Setting it up on an existing database:**
1. `php artisan migrate`
2. `php artisan db:seed --class=DemoOrdinanceSeeder` (adds 5 sample ordinances, see below)

**Demo ordinances:** the 5 sample ordinances are named "Demo Ordinance No. ..." and use sample text, not official records. Replace them with the real ordinances from the **Ordinances** page. (The very first placeholders, DEMO-001 to DEMO-005, are removed by the seeder.)

---

## 8. Where things are (folder map)

| Folder / file | What is inside |
|---|---|
| `routes/web.php` | Every page address, and who is allowed to open it |
| `app/Http/Controllers/` | The code behind each page. `ConcernController` = citizen pages, `AuthController` = login, `RegisterController` = sign up |
| `app/Http/Controllers/Staff/` | The code behind the SB member pages |
| `app/Http/Controllers/Admin/` | The code behind the admin pages |
| `app/Http/Middleware/` | Checks that run before a page opens (is the account active? is this an admin?) |
| `app/Models/` | The database tables as code: `User`, `Concern`, `Ordinance`, `ConcernMatch`, `ConcernLog`, `IssueFlag`, `AuditLog`, `RolePermission` |
| `app/Services/AuditLogger.php` | Writes entries to the audit trail / history |
| `app/Services/OrdinanceMatcher.php` | The rule-based matching (section 6) |
| `app/Services/RecurringIssues.php` | Finds the recurring issues |
| `app/Console/Commands/` | `php artisan concerns:match` and `php artisan sb:create` |
| `database/migrations/` | Creates the tables (`users`, `concerns`, `audit_logs`, ...) |
| `database/seeders/` | Creates the first admin account |
| `config/concerns.php` | The barangay list and the concern categories |
| `config/matching.php` | The points and cut-offs for the matching rules |
| `config/staff.php` | Offices for reassigning, map pins, and the recurring-issue rule |
| `config/admin.php` | Role names and permission settings |
| `resources/views/` | The pages themselves (HTML). `admin/` = admin pages, `staff/` = SB member pages, `concerns/` = citizen pages, `auth/` = login and register |
| `public/css/`, `public/js/` | The styles (`admin.css`, `citizen.css`, `staff.css`) and a small script for the staff pages |

Every controller, model, and middleware has a short comment above each function explaining what it does.

---

## 9. Not built yet

- Editing or deleting an ordinance (you can add and view them)
- A sign-up / approval flow for SB members, and the office-request flow for giving roles
- Marking a concern as **Resolved** (the dashboard shows the count, but nothing sets it yet)
- The "Reports" and "Settings" pages and "Import ordinance" from the first design
- Barangay officials (the tab is an empty list)
- Email verification and password reset (nothing is emailed)
