<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\LibraryAuthController;
use App\Http\Controllers\Api\V1\Auth\LearnerAuthController;
use App\Http\Controllers\Api\V1\LearnerController;
use App\Http\Controllers\Api\V1\MasterController;
use App\Http\Controllers\Api\V1\LibraryController;
use App\Http\Controllers\Api\V1\QrBookingController;
use App\Http\Controllers\Api\V1\DemoBookingController;
use App\Http\Controllers\Api\V1\LibraryReferralController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\ReceiptController;




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
    Route::post('library/resend-otp', [LibraryAuthController::class, 'resendEmailOtp']);
   

    Route::get('/states', [MasterController::class, 'getStates']);
    Route::get('/cities', [MasterController::class, 'getCities']);

    Route::post('plans', [MasterController::class, 'plans']);
    Route::post('shift-plan-types', [MasterController::class, 'getPlanTypeSeatWiseApi']);
    Route::post('chargeable-days', [MasterController::class, 'getChargeableDaysApi']);
    Route::post('plan-price', [MasterController::class, 'getPriceApi']);
    Route::post('get-seat', [MasterController::class, 'getSeat']);

    Route::get('library/about', [LibraryController::class, 'about']);
    Route::get('library/support', [LibraryController::class, 'support']);
    Route::match(['get', 'post'], 'library/how-to-use', [LibraryController::class, 'howToUse']);
    Route::get('library/video-tutorial', [LibraryController::class, 'videoTutorial']);
    Route::get('library/faq', [LibraryController::class, 'faq']);
    Route::get('library/payment-types', [MasterController::class, 'paymentTypeList']);
    Route::post('demo-bookings/store', [DemoBookingController::class, 'store']);
    
});

Route::middleware(['auth:library_api','api_key','throttle:60,1'])->group(function () {
    Route::get('library/profile', [LibraryAuthController::class, 'profile']);
    Route::post('library/profile/update', [LibraryAuthController::class, 'updateProfile']);
    Route::post('library/change-password', [LibraryAuthController::class, 'changePassword']);
    Route::post('library/logout', [LibraryAuthController::class, 'logout']);
    Route::get('razorpay-credentials', [LibraryAuthController::class, 'getRazorpayCredentials']);
    Route::post('library/branch/configure', [LibraryAuthController::class, 'configure']);
    Route::post('library/branch/store', [LibraryAuthController::class, 'configure']);
    Route::post('library/shift/configure', [LibraryAuthController::class, 'shiftConfigure']);
    Route::get('library/branch/list', [LibraryAuthController::class, 'branches']);
    Route::post('library/branch/detail', [LibraryAuthController::class, 'branchDetailEdit']);
    Route::post('library/shift/configure/price', [LibraryAuthController::class, 'getConfigurePrice']);
    Route::post('branch/status', [MasterController::class, 'branchStatus']);
    Route::delete('branch/delete', [MasterController::class, 'branchDestroy']);
    // Route::post('library/branche-shift/configure/edit', [LibraryAuthController::class, 'branchShiftConfigure']);
    Route::post('library/expense/save', [LibraryController::class, 'expenseSave']); // add + update
    Route::post('library/expense/list', [LibraryController::class, 'expenseList']); // list with filters
    Route::post('library/expense/delete', [LibraryController::class, 'expenseDelete']);
    // 🔹 Message Template List
    Route::post('library/templates/list', [LibraryController::class, 'templateList']);

    // 🔹 Message Template Create/Update
    Route::post('library/templates/update', [LibraryController::class, 'templateUpdate']);

   
    Route::post('library/payment/create-order', [LibraryAuthController::class, 'createOrderApi']);
    Route::post('library/payment/verify', [LibraryAuthController::class, 'verifyPaymentApi']);
    Route::get('library/subscriptions', [LibraryController::class, 'subscriptions']);

    Route::post('receipt/link', [ReceiptController::class, 'link']);
    Route::post('id-card/link', [IdCardController::class, 'link']);

    Route::get('library/detail', [LibraryController::class, 'getLibraryDetail']);
    
    // Route::get('library/branches', [LibraryController::class, 'getCurrentBranchDetail']);

    Route::post('library/dashboard', [LibraryController::class, 'dashboard']);
    Route::post('library/dashboard/revenue', [LibraryController::class, 'dashboardRevenue']);
    Route::get('library/notifications', [LibraryController::class, 'notifications']);
    Route::post('library/branch/switch', [LibraryController::class, 'switchBranch']);
    Route::get('library/referral/dashboard', [LibraryReferralController::class, 'dashboard']);
    Route::post('library/referral/redeem', [LibraryReferralController::class, 'redeem']);

    // Explicit path (avoids prefix nesting issues; same as api/v1/library/learners/available-seats)
   

   

    Route::post('/floor/store', [MasterController::class, 'floorStore']);
    Route::post('/floor/list', [MasterController::class, 'floorlist']);
    Route::post('/floor/detail', [MasterController::class, 'floorDetail']);
    Route::post('/floor/delete', [MasterController::class, 'deleteFloor']);
    Route::post('/floor/status', [MasterController::class, 'floorStatus']);

    Route::post('/plan/store',[MasterController::class,'planStore']);
    Route::post('/plan/detail',[MasterController::class,'planEdit']);
    Route::post('/plan/list', [MasterController::class, 'planlist']);
    Route::post('/plan/delete',[MasterController::class,'deletePlan']);
    Route::post('/plan/status',[MasterController::class,'planStatus']);
    
    Route::post('/plantype/detail',[MasterController::class,'planTypeEdit']);
    Route::post('/plantype/store',[MasterController::class,'plantypeStore']);
    Route::post('/plantype/list', [MasterController::class, 'planTypelist']);
    Route::post('/plantype/delete',[MasterController::class,'deletePlanType']);
    Route::post('/plantype/status',[MasterController::class,'planTypeStatus']);

    Route::post('/planprice/detail',[MasterController::class,'planPriceEdit']);
    Route::post('/planprice/store',[MasterController::class,'priceStore']);
    Route::post('/planprice/list', [MasterController::class, 'pricelist']);
    Route::post('/planprice/delete', [MasterController::class, 'deletePlanPrice']);
    Route::post('/planprice/status', [MasterController::class, 'planPriceStatus']);
    
    Route::post('/library/user/permissions', [MasterController::class, 'libraryPermissions']);
    Route::post('/library/user/add',[MasterController::class,'saveLibraryUser']);
    Route::post('/library/user/detail',[MasterController::class,'editLibraryUser']);
    Route::get('/library/user/list',[MasterController::class,'libraryUserList']);
    Route::get('library_user/roles', [MasterController::class, 'rolesList']);
    Route::post('/library/user/permissions/update',[MasterController::class,'assignPermissions']);
    Route::post('/library/user/delete', [MasterController::class, 'deleteLibraryUser']);
    Route::post('/library/user/status', [MasterController::class, 'libraryUserStatus']);

    Route::post('exam/list', [MasterController::class, 'examlist']);
    Route::post('exam/store', [MasterController::class, 'examstore']);
    Route::post('exam/detail', [MasterController::class, 'examedit']);
    Route::post('exam/delete', [MasterController::class, 'examdelete']);

    Route::post('expense/list', [MasterController::class, 'expenseList']);
    Route::post('expense/store', [MasterController::class, 'expenseStore']);
    Route::post('expense/detail', [MasterController::class, 'expenseDetail']);
    Route::post('expense/delete', [MasterController::class, 'expenseDelete']);

    Route::post('upload/temp-images', [LibraryController::class, 'uploadTempImages']);


    Route::prefix('library/learners')->group(function () {

        Route::post('/seat-book', [LearnerController::class,'store']);
        Route::match(['get', 'post'], '/seat-status', [LearnerController::class, 'seatStatus']);
        Route::post('/swap-seat', [LearnerController::class, 'swapSeat']);
        Route::post('/list', [LearnerController::class,'index']);
        Route::post('/list-by-type', [LearnerController::class,'listByType']);
        Route::post('/activity', [LearnerController::class, 'activity']);
        Route::get('/status-list', [LearnerController::class,'statusList']);
        Route::post('/detail', [LearnerController::class,'show']);
        Route::post('/gift-days/assign', [LearnerController::class, 'assignGiftDays']);
       
        Route::post('/operation',[LearnerController::class,'process']);
        Route::post('/close-delete', [LearnerController::class, 'closeDelete']);
        Route::post('/plan-types', [MasterController::class, 'getFilterPlantypeWithself']);

        Route::post('/lifecycle', [LearnerController::class, 'lifecycle']);
        Route::post('/settlement', [LearnerController::class, 'settlement']);
        Route::post('/other-payment', [LearnerController::class, 'otherPaymentApi']);
        Route::post('/transactions', [LearnerController::class, 'transactions']);
        
        Route::post('/transactions/detail', [LearnerController::class, 'transactionsDetail']);
        Route::post('/transactions/activity/detail', [LearnerController::class, 'transactionsActivityDetail']);
        Route::post('/transactions/delete', [LearnerController::class, 'transactionsDelete']);
        Route::post('/transactions/activity/delete', [LearnerController::class, 'transactionsActivityDelete']); 

         Route::match(['get', 'post'], '/available-seats', [LearnerController::class, 'getAvailableSeat']);
         Route::match(['get', 'post'], '/seat-map', [LearnerController::class, 'seatMap']);
         Route::post('/attendance/summary', [AttendanceController::class, 'summary']);
         Route::post('/attendance/logs', [AttendanceController::class, 'logs']);
    });
   
    Route::get('/attendance/qr-token', [AttendanceController::class, 'qrToken']);
    Route::post('/attendance/qr-scan', [AttendanceController::class, 'qrScanAttendance']);

    Route::post('/attendance/id-scan', [AttendanceController::class, 'idCardScanAttendance']);
    Route::post('/attendance/manual', [AttendanceController::class, 'manualAttendance']);
    
    Route::post('/qr-bookings', [QrBookingController::class, 'index']);
    Route::post('/qr-bookings/detail', [QrBookingController::class, 'show']);
    Route::post('/qr-bookings/verify', [QrBookingController::class, 'verify']);
    Route::post('/qr-bookings/delete', [QrBookingController::class, 'destroy']);
    // Route::post('library/finance/today', [LibraryController::class, 'todayFinancial']);

    // Route::post('library/finance/monthly', [LibraryController::class, 'monthlyFinancial']);
    Route::post('library/finance/dashboardfinancial', [LibraryController::class, 'dashboardFinancial']);
   
});

});

// Learner login
// Route::post('learner/login', [LearnerAuthController::class, 'login']);
// Route::middleware('auth:learner_api')->group(function () {
//     Route::get('learner/profile', [LearnerAuthController::class, 'profile']);
//     Route::post('learner/logout', [LearnerAuthController::class, 'logout']);
// });
