<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\LibraryAuthController;
use App\Http\Controllers\Api\V1\Auth\LearnerAuthController;
use App\Http\Controllers\Api\V1\LearnerController;
use App\Http\Controllers\Api\V1\MasterController;
use App\Http\Controllers\Api\V1\LibraryController;


Route::middleware(['device.check'])->group(function () {
Route::middleware(['api_key','throttle:60,1'])->group(function () {
    Route::get('library/app-settings', [LibraryAuthController::class, 'setting']);
    Route::get('library/subscription/plan', [LibraryAuthController::class, 'libraryPlan']);
    Route::get('master/static-data', [MasterController::class, 'getStaticMasters']);
     // Library login
    Route::post('library/login', [LibraryAuthController::class, 'login']);
    Route::post('library/register', [LibraryAuthController::class, 'register']);
    Route::post('library/verify-email', [LibraryAuthController::class, 'verifyEmailOtp']);
    Route::post('library/forgot-password', [LibraryAuthController::class, 'sendResetLinkEmail']);
    Route::post('library/reset-password', [LibraryAuthController::class, 'resetPassword']);
   
});

Route::middleware(['auth:library_api','api_key','throttle:60,1'])->group(function () {
    Route::get('library/profile', [LibraryAuthController::class, 'profile']);
    Route::post('library/logout', [LibraryAuthController::class, 'logout']);
   
    Route::post('library/branch/configure', [LibraryAuthController::class, 'configure']);
    Route::post('library/shift/configure', [LibraryAuthController::class, 'shiftConfigure']);
    Route::get('razorpay-credentials', [LibraryAuthController::class, 'getRazorpayCredentials']);
    Route::get('plans', [MasterController::class, 'plans']);
    Route::get('shift-plan-types', [MasterController::class, 'getPlanTypeSeatWiseApi']);
    Route::get('chargeable-days', [MasterController::class, 'getChargeableDaysApi']);
    Route::get('plan-price', [MasterController::class, 'getPriceApi']);
    Route::post('library/payment/create-order', [LibraryAuthController::class, 'createOrderApi']);
    Route::post('library/payment/verify', [LibraryAuthController::class, 'verifyPaymentApi']);
    Route::get('library/branches', [LibraryController::class, 'branches']);

    Route::get('library/dashboard', [LibraryController::class, 'dashboard']);
    Route::post('/floor/store', [MasterController::class, 'floorStore']);
    Route::get('/floor/list', [MasterController::class, 'floorlist']);
    Route::post('/plan/store',[MasterController::class,'planStore']);
    Route::get('/plan/edit',[MasterController::class,'planEdit']);
    Route::get('/plan/list', [MasterController::class, 'planlist']);
    Route::get('/plantype/edit',[MasterController::class,'planTypeEdit']);
    Route::post('/plantype/store',[MasterController::class,'plantypeStore']);
    Route::get('/plantype/list', [MasterController::class, 'planTypelist']);
    Route::get('/planprice/edit',[MasterController::class,'planPriceEdit']);
    Route::post('/price/store',[MasterController::class,'priceStore']);
    
    Route::get('/price/list', [MasterController::class, 'pricelist']);
    
    Route::get('/library/permissions', [MasterController::class, 'libraryPermissions']);
    Route::post('/library/user',[MasterController::class,'saveLibraryUser']);
    Route::post('/library/user/permissions',[MasterController::class,'assignPermissions']);

    Route::prefix('library/learners')->group(function () {

        Route::post('/seat-book', [LearnerController::class,'store']);
        Route::get('/list', [LearnerController::class,'index']);
        Route::get('/{id}', [LearnerController::class,'show']);
        Route::put('/{id}', [LearnerController::class,'update']);

        Route::post('/operation',[LearnerController::class,'process']);

    });
   
    
    
});

});

// Learner login
// Route::post('learner/login', [LearnerAuthController::class, 'login']);
// Route::middleware('auth:learner_api')->group(function () {
//     Route::get('learner/profile', [LearnerAuthController::class, 'profile']);
//     Route::post('learner/logout', [LearnerAuthController::class, 'logout']);
// });
