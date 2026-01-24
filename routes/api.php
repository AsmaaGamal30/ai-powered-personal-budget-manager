<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;


Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
Route::post('/send-otp', [AuthController::class, 'sendOtp'])->name('send-otp');


Route::prefix('users')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/{user}/logout', [AuthController::class, 'logout'])->name('user.logout');
});
