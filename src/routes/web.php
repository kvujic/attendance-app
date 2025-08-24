<?php

use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminCorrectionController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\AdminLoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\UserLoginController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\CorrectionController;
use App\Http\Middleware\AdminCorrectionListMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/




// user: before login
Route::middleware('guest')->group(function() {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/login', [UserLoginController::class, 'create'])->name('login');
    Route::post('/login', [UserLoginController::class, 'login']);
});

//email-verification
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'show'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
});

// user: after login with authentication
Route::middleware('auth', 'verified')->group(function() {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.list');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{id}', [CorrectionController::class, 'update'])->name('attendance.update');

    Route::post('/logout', [UserLoginController::class, 'logout'])->name('logout');
});




// admin
Route::prefix('admin')->name('admin.')->group(function() {
    Route::middleware('guest:admin')->group(function() {
        Route::get('/login', [AdminLoginController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login']);
    });

    Route::middleware(['auth:admin', 'admin'])->group(function() {
        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendance.list');

        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.list');
        Route::get('/attendance/staff/{user}', [StaffController::class, 'showAttendance'])->name('attendance.staff');
        Route::get('/attendance/staff/{user}/csv', [StaffController::class, 'exportCsv'])->name('attendance.csv');

        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'showDetail'])->where('id', 'new|[0-9]+')->name('attendance.showDetail');
        Route::post('/attendance/{id}', [AdminCorrectionController::class, 'updateAttendance'])->where('id', 'new|[0-9]+')->name('attendance.updateAttendance');

        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
    });
});

Route::middleware(['auth:admin', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{correction}', [AdminCorrectionController::class, 'showCorrection'])->name('stamp_correction_request.showCorrection');
    Route::patch('/stamp_correction_request/approve/{correction}', [AdminCorrectionController::class, 'approve'])->name('stamp_correction_request.approve');
});

Route::get('/stamp_correction_request/list', [\App\Http\Controllers\User\CorrectionController::class, 'index'])
    ->middleware(['auth:admin,web', \App\Http\Middleware\AdminCorrectionListMiddleware::class, 'verified'])
    ->name('stamp_correction_request.index');

