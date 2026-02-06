<?php

use App\Http\Controllers\AI\AIAssistantController;
use App\Http\Controllers\Analytics\StatsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Budget\BudgetController;
use App\Http\Controllers\Category\CategoryController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
});

Route::prefix('budgets')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/{category}', [BudgetController::class, 'store'])->name('budgets.store');
    Route::put('/{category}', [BudgetController::class, 'update'])->name('budgets.update');
    Route::delete('/{category}', [BudgetController::class, 'destroy'])->name('budgets.destroy');
});

Route::prefix('stats')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/', [StatsController::class, 'index']);

    Route::get('/records', [StatsController::class, 'getUserStats']);

    Route::post('/{budget}', [StatsController::class, 'store']);

    Route::put('/{stat}', [StatsController::class, 'update']);

    Route::delete('/{stat}', [StatsController::class, 'destroy']);

    Route::get('/categories/{category}', [StatsController::class, 'show']);
});

Route::prefix('ai')->middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/ask', [AIAssistantController::class, 'ask']);

    Route::get('/insights', [AIAssistantController::class, 'getInsights']);

    Route::get('/recommendations', [AIAssistantController::class, 'getBudgetRecommendations']);

    Route::get('/anomalies', [AIAssistantController::class, 'analyzeAnomalies']);

    Route::get('/savings-suggestions', [AIAssistantController::class, 'getSavingsSuggestions']);
});