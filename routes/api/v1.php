<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\LibraryAuthController;
use App\Http\Controllers\Api\V1\Auth\LearnerAuthController;
use App\Http\Controllers\Api\V1\MasterController;


 // Library login
Route::post('library/login', [LibraryAuthController::class, 'login']);
Route::post('library/register', [LibraryAuthController::class, 'register']);
Route::post('library/verify-email', [LibraryAuthController::class, 'verifyEmailOtp']);
Route::post('library/forgot-password', [LibraryAuthController::class, 'sendResetLinkEmail']);
Route::post('library/reset-password', [LibraryAuthController::class, 'resetPassword']);

Route::middleware(['api_key','throttle:60,1'])->group(function () {
    Route::get('library/app-settings', [LibraryAuthController::class, 'setting']);
    Route::get('library/subscription/plan', [LibraryAuthController::class, 'libraryPlan']);
    Route::get('master/static-data', [MasterController::class, 'getStaticMasters']);
   
});

Route::middleware('auth:library_api')->group(function () {
    Route::get('library/profile', [LibraryAuthController::class, 'profile']);
    Route::post('library/logout', [LibraryAuthController::class, 'logout']);
    Route::get('library/payment/create-order', [LibraryAuthController::class, 'paymentApi']);
    Route::post('library/payment/create-order', [LibraryAuthController::class, 'paymentApi']);
    Route::post('library/branch/configure', [LibraryAuthController::class, 'configure']);
    Route::post('library/shift/configure', [LibraryAuthController::class, 'configure']);
    Route::get('razorpay-credentials', [LibraryAuthController::class, 'getRazorpayCredentials']);
    Route::get('plans', [MasterController::class, 'plans']);
    Route::get('shift-plan-types', [MasterController::class, 'getPlanTypeSeatWiseApi']);
    Route::get('chargeable-days', [MasterController::class, 'getChargeableDaysApi']);
    Route::get('plan-price', [MasterController::class, 'getPriceApi']);
   
});

// Learner login
// Route::post('learner/login', [LearnerAuthController::class, 'login']);
// Route::middleware('auth:learner_api')->group(function () {
//     Route::get('learner/profile', [LearnerAuthController::class, 'profile']);
//     Route::post('learner/logout', [LearnerAuthController::class, 'logout']);
// });
