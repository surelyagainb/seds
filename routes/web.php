<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConcernController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

// Home page (/): signed-in people go to their own area (admin or citizen), everyone else goes to the login page.
Route::get('/', fn () => auth()->check()
    ? redirect()->route(auth()->user()->homeRoute())
    : redirect()->route('login'));

// Pages only for people who are NOT signed in yet: login and register.
// throttle:6,1 = at most 6 tries per minute (stops password guessing).
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.attempt');
});

// Sign out (must be a POST form, not a link)
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

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
    // "Clear form": throw away the unsaved draft
    Route::delete('concerns/draft', [ConcernController::class, 'clear'])->name('concerns.clear');
    // Save the concern and create the reference number
    Route::post('concerns', [ConcernController::class, 'store'])->name('concerns.store');
    // "Concern received" page
    Route::get('concerns/{concern}/confirmation', [ConcernController::class, 'confirmation'])->name('concerns.confirmation');
    // Track a concern by its reference number
    Route::get('status', [ConcernController::class, 'status'])->name('concerns.status');
});

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
});
