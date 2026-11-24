<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\BarangayController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConcernController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Staff;
use Illuminate\Support\Facades\Route;

// Home page (/): signed-in people go to their own area (admin or citizen), everyone else goes to the login page.
Route::get('/', fn() => auth()->check()
    ? redirect()->route(auth()->user()->homeRoute())
    : redirect()->route('login'));

// Pages only for people who are NOT signed in yet: login and register.
// throttle:6,1 = at most 6 tries per minute (stops password guessing).
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/citizen/login', [AuthController::class, 'showLogin'])->name('citizen.login');
    Route::get('/hall/login', [AuthController::class, 'showHallLogin'])->name('hall.login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');
    Route::get('/login/verify', [AuthController::class, 'showLoginOtp'])->name('login.verify');
    Route::post('/login/verify', [AuthController::class, 'verifyLoginOtp'])->middleware('throttle:6,1')->name('login.verify.attempt');
    Route::post('/login/verify/resend', [AuthController::class, 'resendLoginOtp'])->middleware('throttle:3,1')->name('login.verify.resend');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.attempt');
    Route::get('/register/verify', [RegisterController::class, 'verifyPage'])->name('register.verify');
    Route::post('/register/verify', [RegisterController::class, 'verify'])->middleware('throttle:6,1')->name('register.verify.attempt');
    Route::post('/register/verify/resend', [RegisterController::class, 'resendVerification'])->middleware('throttle:3,1')->name('register.verify.resend');

    Route::get('/password/reset', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/reset', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/password/reset/{token}', [PasswordResetController::class, 'update'])->name('password.update');
});

// Sign out (must be a POST form, not a link)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

// Citizen interface: submit a concern, review it, get a reference number, track status.
// Middleware: auth = signed in, auth.session = signs out other devices after a password change,
// active = account not deactivated, citizen = admins are sent to /admin instead.
Route::middleware(['auth', 'auth.session', 'active', 'citizen'])->group(function () {
    // My Dashboard: counts and recent submissions
    Route::get('dashboard', [ConcernController::class, 'dashboard'])->name('citizen.dashboard');
    // Submit a concern: step 1 (form), step 2 (review), then save
    Route::get('concerns/create', [ConcernController::class, 'create'])->name('concerns.create');
    Route::post('concerns/review', [ConcernController::class, 'preview'])->name('concerns.preview');
    Route::get('concerns/review', [ConcernController::class, 'review'])->name('concerns.review');
    Route::get('concerns/draft/photo', [ConcernController::class, 'draftPhoto'])->name('concerns.draft-photo');
    // "Clear form": throw away the unsaved draft
    Route::delete('concerns/draft', [ConcernController::class, 'clear'])->name('concerns.clear');
    // Save the concern and create the reference number
    Route::post('concerns', [ConcernController::class, 'store'])->name('concerns.store');
    // "Concern received" page
    Route::get('concerns/{concern}/confirmation', [ConcernController::class, 'confirmation'])->name('concerns.confirmation');
    Route::delete('concerns/{concern}/photo', [ConcernController::class, 'deletePhoto'])->name('concerns.delete-photo');
    Route::get('ordinances', [ConcernController::class, 'ordinances'])->name('concerns.ordinances');
    Route::get('ordinances/{ordinance}', [ConcernController::class, 'ordinance'])->name('concerns.ordinance');
    // Track a concern by its reference number
    Route::get('status', [ConcernController::class, 'status'])->name('concerns.status');
});

// SB member (staff) interface: review concerns and ordinance matches, manage ordinances, see recurring issues.
// Middleware: sb = only SB members can open anything under /staff.
Route::prefix('staff')->name('staff.')->middleware(['auth', 'auth.session', 'active', 'sb'])->group(function () {
    // Community overview (map, chart, counts per barangay)
    Route::get('/', [Staff\DashboardController::class, 'index'])->name('dashboard');

    // Concerns: list, one concern, and the decisions staff can make
    Route::get('concerns', [Staff\ConcernController::class, 'index'])->name('concerns.index');
    Route::get('concerns/{concern}', [Staff\ConcernController::class, 'show'])->name('concerns.show');
    Route::post('concerns/{concern}/decision', [Staff\ConcernController::class, 'decide'])->name('concerns.decide');
    Route::post('concerns/{concern}/reassign', [Staff\ConcernController::class, 'reassign'])->name('concerns.reassign');
    Route::post('concerns/{concern}/link', [Staff\ConcernController::class, 'link'])->name('concerns.link');
    Route::post('concerns/{concern}/unmatched', [Staff\ConcernController::class, 'unmatched'])->name('concerns.unmatched');
    Route::post('concerns/{concern}/suggest-new', [Staff\ConcernController::class, 'suggestNew'])->name('concerns.suggest');
    Route::post('concerns/{concern}/snooze', [Staff\ConcernController::class, 'snooze'])->name('concerns.snooze');

    // Recurring issues
    Route::get('recurring', [Staff\RecurringController::class, 'index'])->name('recurring');
    Route::post('recurring/flag', [Staff\RecurringController::class, 'flag'])->name('recurring.flag');

    // Ordinance register
    Route::get('ordinances', [Staff\OrdinanceController::class, 'index'])->name('ordinances.index');
    Route::post('ordinances', [Staff\OrdinanceController::class, 'store'])->name('ordinances.store');
    Route::get('ordinances/{ordinance}', [Staff\OrdinanceController::class, 'show'])->name('ordinances.show');
    Route::get('categories', [Staff\CategoryController::class, 'index'])->name('categories.index');
    Route::post('categories', [Staff\CategoryController::class, 'store'])->name('categories.store');
    Route::put('categories/{category}', [Staff\CategoryController::class, 'update'])->name('categories.update');
    Route::post('categories/{category}/toggle', [Staff\CategoryController::class, 'toggle'])->name('categories.toggle');
    Route::post('categories/{category}/assign', [Staff\CategoryController::class, 'assign'])->name('categories.assign');
    Route::get('reports', [Staff\ReportsController::class, 'index'])->name('reports');


});

Route::get('staff/map-data', [Staff\DashboardController::class, 'mapData'])
    ->name('map.data')
    ->middleware(['auth', 'auth.session', 'active', 'sb']);

// The admin area is for administrators only. Admins can view the audit log,
// check accounts, and remove accounts. They cannot create accounts, change roles,
// or edit permissions (roles will only be granted through an approved office request).
// permission:admin = only administrators can open anything under /admin.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'auth.session', 'active', 'permission:admin'])->group(function () {
    // Admin home page
    Route::get('/', OverviewController::class)->name('overview');

    // Password settings (own account)
    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    // Audit trail / History
    Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('audit/export', [AuditController::class, 'export'])->name('audit.export');

    // Accounts: check and remove only
    Route::get('accounts', [MemberController::class, 'index'])->name('members.index');
    Route::delete('accounts/{member}', [MemberController::class, 'destroy'])->name('members.destroy');
    Route::post('accounts', [MemberController::class, 'store'])->name('members.store');
    Route::put('accounts/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::post('accounts/{member}/toggle', [MemberController::class, 'toggle'])->name('members.toggle');
    Route::post('accounts/{member}/reset-password', [MemberController::class, 'resetPassword'])->name('members.reset-password');

    Route::get('health', SystemHealthController::class)->name('health');
    Route::get('barangays', [BarangayController::class, 'index'])->name('barangays.index');
    Route::post('barangays', [BarangayController::class, 'store'])->name('barangays.store');
    Route::put('barangays/{barangay}', [BarangayController::class, 'update'])->name('barangays.update');
    Route::post('barangays/{barangay}/toggle', [BarangayController::class, 'toggle'])->name('barangays.toggle');
    Route::delete('barangays/{barangay}', [BarangayController::class, 'destroy'])->name('barangays.destroy');
});
