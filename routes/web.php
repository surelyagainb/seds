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

Route::get('/', fn() => auth()->check()
    ? redirect()->route(auth()->user()->homeRoute())
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.attempt');

    // Citizen self-chosen password registration + one-time email verification
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.attempt');
    Route::get('/register/verify', [RegisterController::class, 'verifyPage'])->name('register.verify');
    Route::post('/register/verify', [RegisterController::class, 'verify'])->middleware('throttle:6,1')->name('register.verify.attempt');
    Route::post('/register/verify/resend', [RegisterController::class, 'resendVerification'])->middleware('throttle:3,1')->name('register.verify.resend');

    // Staff registration includes one-time email verification and admin approval.
    Route::get('/staff/register', [RegisterController::class, 'showStaff'])->name('staff.register');
    Route::post('/staff/register', [RegisterController::class, 'storeStaff'])->middleware('throttle:6,1')->name('staff.register.attempt');
    Route::get('/staff/register/verify', [RegisterController::class, 'verifyStaffPage'])->name('staff.register.verify');
    Route::post('/staff/register/verify', [RegisterController::class, 'verifyStaff'])->middleware('throttle:6,1')->name('staff.register.verify.attempt');
    Route::post('/staff/register/verify/resend', [RegisterController::class, 'resendStaffVerification'])->middleware('throttle:3,1')->name('staff.register.verify.resend');
    Route::get('/staff/register/pending', [RegisterController::class, 'staffPending'])->name('staff.register.pending');

    // Password Recovery
    Route::get('/password/reset', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/password/reset', [PasswordResetController::class, 'sendCode'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/password/reset/verify', [PasswordResetController::class, 'verifyForm'])->name('password.verify');
    Route::post('/password/reset/verify', [PasswordResetController::class, 'verifyCode'])->middleware('throttle:6,1')->name('password.verify.attempt');
    Route::post('/password/reset/resend', [PasswordResetController::class, 'resendCode'])->middleware('throttle:3,1')->name('password.resend');
    Route::get('/password/reset/new-password', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/password/reset/new-password', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'auth.session', 'active'])->group(function () {
    Route::get('/account/set-password', [AuthController::class, 'showForcePasswordChange'])->name('password.force');
    Route::post('/account/set-password', [AuthController::class, 'updateForcePasswordChange'])->name('password.force.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

// Citizen interface
Route::middleware(['auth', 'auth.session', 'active', 'citizen'])->group(function () {
    Route::get('dashboard', [ConcernController::class, 'dashboard'])->name('citizen.dashboard');
    Route::get('concerns/create', [ConcernController::class, 'create'])->name('concerns.create');
    Route::post('concerns/review', [ConcernController::class, 'preview'])->name('concerns.preview');
    Route::get('concerns/review', [ConcernController::class, 'review'])->name('concerns.review');
    Route::get('concerns/draft/photo', [ConcernController::class, 'draftPhoto'])->name('concerns.draft-photo');
    Route::delete('concerns/draft', [ConcernController::class, 'clear'])->name('concerns.clear');
    Route::post('concerns', [ConcernController::class, 'store'])->name('concerns.store');
    Route::get('concerns/{concern}/confirmation', [ConcernController::class, 'confirmation'])->name('concerns.confirmation');
    Route::delete('concerns/{concern}/photo', [ConcernController::class, 'deletePhoto'])->name('concerns.delete-photo');
    Route::get('ordinances', [ConcernController::class, 'ordinances'])->name('concerns.ordinances');
    Route::get('ordinances/{ordinance}', [ConcernController::class, 'ordinance'])->name('concerns.ordinance');
    Route::get('ordinances/{ordinance}/download', [ConcernController::class, 'downloadOrdinance'])->name('concerns.ordinance.download');
    Route::get('status', [ConcernController::class, 'status'])->name('concerns.status');
});

// SB member (staff) interface
Route::prefix('staff')->name('staff.')->middleware(['auth', 'auth.session', 'active', 'sb'])->group(function () {
    Route::get('/', [Staff\DashboardController::class, 'index'])->name('dashboard');
    Route::get('concerns', [Staff\ConcernController::class, 'index'])->name('concerns.index');
    Route::get('concerns/{concern}', [Staff\ConcernController::class, 'show'])->name('concerns.show');
    Route::post('concerns/{concern}/decision', [Staff\ConcernController::class, 'decide'])->name('concerns.decide');
    Route::post('concerns/{concern}/reassign', [Staff\ConcernController::class, 'reassign'])->name('concerns.reassign');
    Route::post('concerns/{concern}/link', [Staff\ConcernController::class, 'link'])->name('concerns.link');
    Route::post('concerns/{concern}/unmatched', [Staff\ConcernController::class, 'unmatched'])->name('concerns.unmatched');
    Route::post('concerns/{concern}/suggest-new', [Staff\ConcernController::class, 'suggestNew'])->name('concerns.suggest');
    Route::post('concerns/{concern}/snooze', [Staff\ConcernController::class, 'snooze'])->name('concerns.snooze');
    Route::get('recurring', [Staff\RecurringController::class, 'index'])->name('recurring');
    Route::post('recurring/flag', [Staff\RecurringController::class, 'flag'])->name('recurring.flag');
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

// Admin area
Route::prefix('admin')->name('admin.')->middleware(['auth', 'auth.session', 'active', 'permission:admin'])->group(function () {
    Route::get('/', OverviewController::class)->name('overview');
    Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('audit/export', [AuditController::class, 'export'])->name('audit.export');
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
