<?php

use App\Http\Controllers\AI\AIAssistantController;
use App\Http\Controllers\Analytics\StatsController;
use App\Http\Controllers\Auth\SocialAuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Category\CategoryController;

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
Route::post('/send-otp', [AuthController::class, 'sendOtp'])->name('send-otp');

Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])->name('social.redirect');
Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])->name('social.callback');


Route::prefix('users')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/{user}/logout', [AuthController::class, 'logout'])->name('user.logout');
});

Route::prefix('categories')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/{category}/store', [CategoryController::class, 'store'])->name('categories.store');
});

Route::prefix('stats')->group(function () {
    Route::get('/', [StatsController::class, 'index']);

    Route::get('/records', [StatsController::class, 'getUserStats']);

    Route::post('/', [StatsController::class, 'store']);

    Route::put('/{stat}', [StatsController::class, 'update']);

    Route::delete('/{stat}', [StatsController::class, 'destroy']);

    Route::get('/categories/{category}', [StatsController::class, 'show']);
});

Route::prefix('ai')->group(function () {
    Route::post('/ask', [AIAssistantController::class, 'ask']);

    Route::get('/insights', [AIAssistantController::class, 'getInsights']);

    Route::get('/recommendations', [AIAssistantController::class, 'getBudgetRecommendations']);

    Route::get('/anomalies', [AIAssistantController::class, 'analyzeAnomalies']);

    Route::get('/savings-suggestions', [AIAssistantController::class, 'getSavingsSuggestions']);
});