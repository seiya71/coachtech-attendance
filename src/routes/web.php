<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ApplicationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\AuthController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/register', [AuthController::class, 'register'])->name('register.perform');

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', fn() => view('auth.verify-email'))
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('home')->with('status', 'verified');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'verification-link-sent');
    })->middleware(['throttle:6,1'])->name('verification.send');
});

Route::get('/', [AttendanceController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('home');

Route::get('/attendance', [AttendanceController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('attendance.index');

Route::middleware(['auth', 'verified'])->prefix('attendance')->group(function () {
    Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock_in');
    Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock_out');
    Route::post('/break-in', [AttendanceController::class, 'startBreak'])->name('attendance.break_in');
    Route::post('/break-out', [AttendanceController::class, 'finishBreak'])->name('attendance.break_out');
    Route::get('/list', [AttendanceController::class, 'listIndex'])->name('attendance.list');
    Route::get('/{id}', [AttendanceController::class, 'show'])->name('attendance.detail');
});

Route::middleware('auth')->group(function () {
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'attendanceDetail'])
        ->name('attendance.detail');
    Route::get('/attendance/application/{id}', [AttendanceController::class, 'attendanceDetail'])
        ->name('attendance.application');
    Route::get('/attendance/new/{date}', [AttendanceController::class, 'attendanceDetail'])
        ->name('attendance.new');

});

Route::middleware('auth')->group(function () {
    Route::post('/attendance/application', [ApplicationController::class, 'submit'])
        ->name('applications.submit');
});

Route::get('/stamp_correction_request/list', [ApplicationController::class, 'requests_list'])
    ->name('applications.list');

Route::get('/stamp_correction_request/list/approved', [ApplicationController::class, 'requests_list'])
    ->name('applications.list.approved');

Route::get('/admin/login', [AuthController::class, 'admin_login'])->name('admin.login');