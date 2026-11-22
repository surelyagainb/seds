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
| **SB member** | 5 **temporary** sample accounts are created by the seeder (`johnsmith123@gmail.com`, `mariasantos123@gmail.com`, `juandelacruz123@gmail.com`, `anareyes123@gmail.com`, `pedrogarcia123@gmail.com`), all with the password `sbmember`. There is no sign-up page for staff | Staff area (`/staff`) |
| **Administrator** | Created by the seeder: `admin@victoria.test` / `admin` | Admin area (`/admin`) |

- Change the admin password right away (Admin > Password settings). `admin` is only a starting password.
- Each side is closed to the others. If you open a page that belongs to another side, you are sent back to your own home page.
- Nobody picks their own role. Everyone who registers is a citizen.

**The 5 sample SB accounts are only for trying things out.** Remove them before real use (Admin > Accounts > Remove, or delete the block marked TEMPORARY in `database/seeders/DatabaseSeeder.php`). Running `php artisan db:seed` again will not reset a password you changed, but it will re-create a sample account you removed.

**Creating a real SB member account** (run in the terminal, it asks for the password):
```
php artisan sb:create "Juan Dela Cruz" juan@example.com
```
The password needs 12+ characters with letters, numbers and a symbol. Later, SB accounts will be given through the office-request flow (not built yet).

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
- **Search and filters** on the dashboard: type a word (reference, barangay, description) or pick a category (for example *Health / Sanitation* or *Waste Management*), a status, or a barangay. Press **Filter**; **Reset** clears it. Results show 10 per page.
- Your answers are kept between step 1 and step 2, so "Edit details" does not lose anything.
- Photos are saved privately (not public) in `storage/app/private`.
- Statuses on the dashboard:
  - **Under Review**: sent, not reviewed yet
  - **Reviewed**: staff reviewed it
  - **Resolved**: marked as resolved
- Analysis, ordinance matching, and staff review are **not built yet**. For now every concern stays "Under Review" and the Reviewed / Resolved counts stay at 0.

---

## 4. What SB members can do

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

## 5. What administrators can do

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

## 6. Rule-based matching

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

## 7. Where things are (folder map)

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

## 8. Not built yet

- Editing or deleting an ordinance (you can add and view them)
- A sign-up / approval flow for SB members, and the office-request flow for giving roles
- Marking a concern as **Resolved** (the dashboard shows the count, but nothing sets it yet)
- The "Reports" and "Settings" pages and "Import ordinance" from the first design
- Barangay officials (the tab is an empty list)
- Email verification and password reset (nothing is emailed)
