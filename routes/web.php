<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LibraryUserController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;





Route::get('/get-libraries', [MasterController::class, 'getLibraries'])->name('get-libraries');


Route::get('administrator/login', [LoginController::class, 'showLoginForm'])->name('login.administrator');
Route::get('library/login', [LoginController::class, 'showAdminLoginForm'])->name('login.library');

Route::get('learner/login', [LoginController::class, 'showLearnerLoginForm'])->name('login.learner');
Route::post('login/store', [LoginController::class, 'login'])->name('login.store');
Route::get('library/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
// Auth routes
Auth::routes(['register' => false, 'login' => false,'verify' => false]);
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->withoutMiddleware('auth');


Route::group(['prefix' => 'library'], function () {
  Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request.library');
  Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email.library');
  Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset.library');
  Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update.library');
});


// Route::get('/email/verify', function () {
//   return view('auth.verify');
// })->name('verification.notice');
Route::get('/email/verify', [LibraryController::class, 'emailVerification'])->name('verification.notice');
Route::post('/verify-otp', [LibraryController::class, 'verifyOtp'])->name('verify.otp');
Route::get('library/choose-plan-price', [LibraryController::class, 'getSubscriptionPrice'])->name('subscriptions.getSubscriptionPrice');
Route::get('cityGetStateWise', [MasterController::class, 'stateWiseCity'])->name('cityGetStateWise');
Route::get('library/create', [LibraryController::class, 'create'])->name('library.create');
Route::post('library/store', [LibraryController::class, 'store'])->name('library.store');
Route::post('/fee/generate-receipt', [Controller::class, 'generateReceipt'])->name('fee.generateReceipt');

Route::get('about-us', [SiteController::class, 'aboutUs'])->name('about-us');
Route::get('blog', [SiteController::class, 'blog'])->name('blog');
Route::get('contact-us', [SiteController::class, 'contactUs'])->name('contact-us');
Route::get('privacy-policy', [SiteController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('terms-and-condition', [SiteController::class, 'termAndCondition'])->name('term-and-condition');
Route::get('refund-policy', [SiteController::class, 'refundPolicy'])->name('refund-policy');

Route::get('/search-libraries', [SiteController::class, 'searchLibrary'])->name('find-my-library');
Route::get('/', [SiteController::class, 'home'])->name('/');
Route::post('demo-request', [SiteController::class, 'demoRequestStore'])->name('demo-request');
Route::post('/store/inquiry', [SiteController::class, 'Inquerystore'])->name('submit.inquiry');
Route::post('/store-selected-plan', [SiteController::class, 'storeSelectedPlan'])->name('store.selected.plan');
Route::get('blog/detail/{slug}', [SiteController::class, 'blogDetail'])->name('blog-detail');
Route::get('getLibrariesLocations', [SiteController::class, 'getLibrariesLocations'])->name('getLibrariesLocations');
Route::get('library-detail/{slug}', [SiteController::class, 'libraryDetail'])->name('libraryDetail');
Route::post('/submit-review', [SiteController::class, 'reviewstore'])->name('submit.review');
Route::post('/store/library/inquiry', [SiteController::class, 'libraryInquerystore'])->name('submit.library.inquiry');
Route::get('/home/library_user', [DashboardController::class, 'librar_UserDashboard'])->name('library.user.login'); 
// Routes for library users with 'auth:library' guard
 Route::middleware(['auth.library_or_user', 'verified.library', 'log.requests'])->group(function () {
// Route::middleware(['auth:library','log.requests'])->group(function () {
  // 
    Route::get('/branch/index', [BranchController::class, 'index'])->name('branch.list');
    Route::delete('/branch/{id}', [BranchController::class, 'destroy'])->name('branch.destroy');

    Route::post('/branch', [BranchController::class, 'store'])->name('branch.store');
    Route::get('/branch/create', [BranchController::class, 'branchForm'])->name('branch.create');
    // Edit form
    Route::get('/branch/{id}/edit', [BranchController::class, 'branchForm'])->name('branch.edit');
    Route::put('/branch/{id}', [BranchController::class, 'update'])->name('branch.update');

    Route::get('/library-users', [LibraryUserController::class, 'index'])->name('library-users.index');
    Route::get('/library-users/create', [LibraryUserController::class, 'create'])->name('library-users.create');
    Route::post('/library-users/store', [LibraryUserController::class, 'store'])->name('library-users.store');
    Route::post('/library-users/toggle-status/{id}', [LibraryUserController::class, 'toggleStatus']);

    Route::post('/library/master/upload', [Controller::class, 'uploadmastercsv'])->name('library.master.upload');
    Route::post('/dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data.get');
    Route::get('export-learners-csv', [Controller::class, 'exportLearnerCSV'])->name('learners.export-csv');
    Route::get('/csv/library/upload', [Controller::class, 'showUploadForm'])->name('library.upload.form');
    Route::post('/csv/library/learner/upload', [Controller::class, 'uploadCsv'])->name('library.csv.upload');
    Route::get('/export-invalid-records/library', [Controller::class, 'exportCsv'])->name('library.export.invalid.records');
    Route::post('/clear-invalid-records/library', [Controller::class, 'clearSession'])->name('library.clear.session');
    Route::get('/renew/configration/library', [Controller::class, 'renewConfigration'])->name('renew.configration');

    Route::get('/learner/expire/{id?}', [LearnerController::class, 'learnerExpire'])->name('learner.expire');
    Route::put('/learner/expire/update/{id?}', [LearnerController::class, 'editLearnerExpire'])->name('learner.expire.update');
      //**LEARNER**//
    Route::get('library/learners', [LearnerController::class, 'index'])->name('seats');
    Route::post('library/learners/log', [LearnerController::class, 'learnerLog'])->name('learner.log');
  
    Route::prefix('library')->group(function () {
      Route::get('/home', [DashboardController::class, 'libraryDashboard'])->name('library.home'); 
      Route::get('/transaction', [LibraryController::class, 'transaction'])->name('library.transaction'); 
      Route::get('/myplan', [LibraryController::class, 'myplan'])->name('library.myplan'); 
      Route::get('/library-master', [MasterController::class, 'masterPlan'])->name('library.master');
      Route::get('/plantype', [MasterController::class, 'planTypeView'])->name('plantype.index');
      Route::get('/plantype/create/{id?}', [MasterController::class, 'planTypeCreate'])->name('planType.create');
      Route::get('/plan/list', [MasterController::class, 'planView'])->name('plan.index');
      Route::get('/plan/create/{id?}', [MasterController::class, 'planCreate'])->name('plan.create');
      Route::get('/expense/list', [MasterController::class, 'expenseView'])->name('expense.index');
      Route::get('/expense/create/{id?}', [MasterController::class, 'expenseCreate'])->name('expense.create');
      Route::get('/exam/list', [MasterController::class, 'examView'])->name('exam.index');
      Route::get('/exam/create/{id?}', [MasterController::class, 'examCreate'])->name('exam.create');
      Route::get('/seat/create/{id?}', [MasterController::class, 'seatCreate'])->name('seat.create');
      Route::get('/hour/create/{id?}', [MasterController::class, 'hourCreate'])->name('hour.create');
      Route::get('/extendDay/create/{id?}', [MasterController::class, 'extendDayCreate'])->name('extendDay.create');
      Route::get('/lockeramount/create/{id?}', [MasterController::class, 'lockerAmountCreate'])->name('lockeramount.create');
      Route::get('/planPrice/create/{id?}', [MasterController::class, 'planPriceCreate'])->name('planPrice.create');
      Route::get('/planPrice/list', [MasterController::class, 'planPriceView'])->name('planPrice.index');
      Route::get('/choose-plan', [LibraryController::class, 'choosePlan'])->name('subscriptions.choosePlan');
      Route::get('/master/account', [LibraryController::class, 'sidebarRedirect'])->name('library.master.account');
      Route::get('/subscriptions/payment-add', [LibraryController::class, 'paymentProcess'])->name('subscriptions.payment');
    
      Route::post('/subscriptions/payment-add', [LibraryController::class, 'paymentProcess'])->name('subscriptions.payment');
      Route::post('/payment-store', [LibraryController::class, 'paymentStore'])->name('library.payment.store');
    
      Route::get('/profile', [LibraryController::class, 'profile'])->name('profile');
      Route::post('/profile/update', [LibraryController::class, 'updateProfile'])->name('library.profile.update');
      Route::post('/payment/success', [LibraryController::class, 'handleSuccess'])->name('library.payment.success');
      Route::get('/payment/error', [LibraryController::class, 'handleError'])->name('library.payment.error');
      


      Route::post('/master/store', [MasterController::class, 'storemaster'])->name('master.store');
      Route::get('/master/edit', [MasterController::class, 'masterEdit'])->name('master.edit');
      
      Route::post('/extend-day', [MasterController::class, 'extendDay'])->name('extendDay.store');
      
      Route::delete('/activeDeactive/{id}/toggle', [MasterController::class, 'activeDeactive'])->name('activeDeactive');
      //Report menu
      Route::get('monthly/create', [ReportController::class, 'monthlyReport'])->name('report.monthly');
      Route::get('report/expense/{year}/{month}', [ReportController::class, 'monthlyExpenseCreate'])->name('report.expense');
      Route::post('report/expense/store', [ReportController::class, 'monthlyExpenseStore'])->name('report.expense.store');
      Route::get('library/video-training', [LibraryController::class, 'videoTraining'])->name('library.video-training');

      // Other Pages Routes
     
      Route::get('pending/payment', [ReportController::class, 'pendingPayment'])->name('pending.payment.report');
      Route::get('activity/report', [ReportController::class, 'activity'])->name('activity.report');
      Route::get('attendance/report', [ReportController::class, 'attendanceReport'])->name('attendance.report');
      Route::get('payment/collection/report', [ReportController::class, 'paymentCollection'])->name('payment.collection.report');
      Route::get('partial/payment/collection/report', [ReportController::class, 'partialPaymentCollection'])->name('partial.payment.collection.report');
      Route::get('learner/report', [ReportController::class, 'learnerReport'])->name('learner.report');
      Route::get('upcoming/payment/report', [ReportController::class, 'upcomingPayment'])->name('upcoming.payment.report');
      Route::get('expired/learner/report', [ReportController::class, 'expiredLearner'])->name('expired.learner.report');
      Route::post('increment-message-count', [LearnerController::class, 'incrementMessageCount'])->name('increment.message.count');
      Route::get('settings', [LibraryController::class, 'librarySetting'])->name('library.settings');
      Route::post('settings/store', [LibraryController::class, 'SettingStore'])->name('library.settings.store');
      Route::get('feedback', [LibraryController::class, 'libraryFeedback'])->name('library.feedback');
      Route::post('feedback/store', [LibraryController::class, 'feedbackStore'])->name('library.feedback.store');
      Route::get('list/notification', [NotificationController::class, 'show'])->name('list.notification'); 
      Route::post('/notifications/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
      Route::get('enquiry', [LibraryController::class, 'getEnquiry'])->name('library.enquiry');
  
      Route::post('branch/switch', [BranchController::class, 'switch'])->name('branch.switch');
      
    });
   
    Route::prefix('library/learners')->group(function () {
      Route::post('/store', [LearnerController::class, 'learnerStore'])->name('learners.store');
      Route::post('/generallearner/store', [LearnerController::class, 'generallearnerStore'])->name('genral.learners.store');
      Route::get('/list', [LearnerController::class, 'learnerList'])->name('learners');
      Route::get('/search', [LearnerController::class, 'learnerSearch'])->name('learner.search');
      Route::get('/history/list', [LearnerController::class, 'learnerHistory'])->name('learnerHistory');
      Route::get('/booking-info/{id?}', [LearnerController::class, 'showLearner'])->name('learners.show');
      Route::get('/edit/{id?}', [LearnerController::class, 'getUser'])->name('learners.edit');
      Route::put('/upgrade/update/{id?}', [LearnerController::class, 'userUpdate'])->name('learners.update.upgrade');
      Route::put('/change/plan/update/{id?}', [LearnerController::class, 'changePlanUpdate'])->name('learners.update.changePlan');
      Route::put('/update/{id?}', [LearnerController::class, 'learnerUpdate'])->name('learners.update');
      
      Route::get('/swap/{id?}', [LearnerController::class, 'getSwapUser'])->name('learners.swap');
      Route::put('/swap-seat', [LearnerController::class, 'swapSeat'])->name('learners.swap-seat');
      Route::get('/change/plan/{id?}', [LearnerController::class, 'getLearner'])->name('learner.change.plan');
      Route::post('/close', [LearnerController::class, 'userclose'])->name('learners.close');
      Route::delete('/{Learner}', [LearnerController::class, 'destroy'])->name('learners.destroy');
      Route::get('/reactive/{id?}', [LearnerController::class, 'reactiveUser'])->name('learners.reactive');
      Route::put('/reactive/{id?}', [LearnerController::class, 'reactiveLearner'])->name('learner.reactive.store');
      Route::get('/payment/{id?}', [LearnerController::class, 'makePayment'])->name('learner.payment');
      Route::post('/payment/store', [LearnerController::class, 'paymentStore'])->name('learner.payment.store');
      Route::get('/getTransactionDetail', [LearnerController::class, 'getTransactionDetail'])->name('getTransactionDetail');
      Route::get('learner/pending/payment/{id?}', [LearnerController::class, 'pendingPayment'])->name('learner.pending.payment');
      Route::post('pending/payment/store', [LearnerController::class, 'pendingPaymentStore'])->name('learner.pending.payment.store');
      
      Route::get('/seats/view', [DashboardController::class, 'viewSeats'])->name('learners.list.view');
      Route::get('/upgrade/renew/{id?}', [LearnerController::class, 'getLearner'])->name('learners.upgrade.renew');
      Route::get('/renew/{id?}', [LearnerController::class, 'getLearner'])->name('learner.renew.plan');
      Route::post('/upgrade/renew/store', [LearnerController::class, 'learnerUpgradeRenew'])->name('learner.upgrade.renew.store');
      Route::get('/attendance', [LearnerController::class, 'learnerAttendence'])->name('attendance');
      Route::get('get/learner/attendance', [LearnerController::class, 'getLearnerAttendence'])->name('get.learner.attendance');
      Route::post('/update-attendance', [LearnerController::class, 'updateAttendance'])->name('update.attendance');
      Route::get('/complaints', [LibraryController::class, 'learnerComplaints'])->name('library.learner.complaints');
      Route::get('/suggestions', [LibraryController::class, 'learnerSuggestions'])->name('library.learner.suggestions');
      Route::get('/feedback', [LibraryController::class, 'learnerFeedback'])->name('library.learner.feedback');
      Route::post('/clarification/submit/status', [LibraryController::class, 'clarificationStatus'])->name('clarification.submit.status');

    });
    Route::get('seat/history/list', [LearnerController::class, 'seatHistory'])->name('seats.history');
    Route::get('seats/history/{id?}', [LearnerController::class, 'history'])->name('seats.history.show');
    Route::post('change-password', [UserController::class, 'changePassword'])->name('change-password');
    Route::get('change/password', [UserController::class, 'changePasswordView'])->name('change.password');
    
    //condition base route
    Route::post('learner/renew/', [LearnerController::class, 'learnerRenew'])->name('learners.renew');
    Route::get('getSeatStatus', [LearnerController::class, 'getSeatStatus'])->name('getSeatStatus');
    Route::get('getPlanType', [LearnerController::class, 'getPlanType'])->name('gettypePlanwise');
    Route::get('getPlanTypeSeatWise', [LearnerController::class, 'getPlanTypeSeatWise'])->name('gettypeSeatwise');
    Route::get('getPrice', [LearnerController::class, 'getPrice'])->name('getPricePlanwise');
    Route::get('getPricePlanwiseUpgrade', [LearnerController::class, 'getPricePlanwiseUpgrade'])->name('getPricePlanwiseUpgrade');
    Route::post('generateIdCard', [LearnerController::class, 'generateIdCard'])->name('generateIdCard');

    Route::get('/locker-price', function (\Illuminate\Http\Request $req) {
    return response()->json([
        'price' => getLockerPrice($req->query('plan_id'))
        ]);
    })->name('locker.price');
        
});
// Routes for superadmin and admin users
Route::middleware(['auth:web'])->group(function () {
  Route::post('library/storedata', [LibraryController::class, 'libraryStore'])->name('library.storedata');
  
    Route::post('library/verify/otp', [AdminController::class, 'libraryVerify'])->name('library.verify.otp');
    Route::get('/home', [DashboardController::class, 'index'])->name('home'); // Admin or superadmin home
    Route::get('library/payment/{id}', [AdminController::class, 'libraryPayment'])->name('library.payment');
    Route::get('get/subscription/fees', [AdminController::class, 'getSubscriptionFees'])->name('get.subscription.fees');
    Route::post('admin/library/payment/store', [AdminController::class, 'libraryPaymentStore'])->name('admin.library.payment.store');
    Route::middleware(['role:superadmin'])->group(function () {
        Route::post('library/dashboard/data', [DashboardController::class, 'libraryGetData'])->name('library.dashboard.data.get');
        Route::delete('/activeDeactive/{id}/toggle', [MasterController::class, 'activeDeactive'])->name('activeDeactive');
        Route::get('/csv/web/upload/{id?}', [Controller::class, 'showUploadForm'])->name('configration.upload');
       
        Route::get('/export-invalid-records/web', [Controller::class, 'exportCsv'])->name('web.export.invalid.records');
        Route::post('/clear-invalid-records/web', [Controller::class, 'clearSession'])->name('web.clear.session');
   
        Route::get('library', [LibraryController::class, 'index'])->name('library');

        Route::post('subscriptions/store', [MasterController::class, 'storeSubscription'])->name('subscriptions.store');
        Route::post('subscriptions/assign-permissions', [MasterController::class, 'assignPermissionsToSubscription'])->name('subscriptions.assignPermissions');
        Route::get('/subscriptions/{id}/permissions', [MasterController::class, 'getPermissions'])->name('subscriptions.getPermissions');
        
       
        Route::get('subscriptions-permissions', [MasterController::class, 'index'])->name('subscriptions.permissions');
        Route::get('planwise/permissions/{id}', [MasterController::class, 'showPlanwisePermission'])->name('planwise.permissions');
        Route::get('subscription/master', [MasterController::class, 'subscriptionMaster'])->name('subscription.master');
        Route::get('/subscription/master/{id}', [MasterController::class, 'subscriptionMasterEdit'])->name('subscriptions.edit');
        Route::put('/subscriptions/update/{id}', [MasterController::class, 'subscriptionMasterUpdate'])->name('subscriptions.update');
        Route::get('permissions/{permissionId?}', [MasterController::class, 'managePermissions'])->name('permissions');

        Route::put('permissions/{permissionId?}', [MasterController::class, 'storeOrUpdatePermission'])->name('permissions.storeOrUpdate');
        Route::put('permission-categories/storeOrUpdate/{categoryId?}', [MasterController::class, 'storeOrUpdateCategory'])->name('permission-categories.storeOrUpdate');

        Route::delete('permissions/{permissionId}', [MasterController::class, 'deletePermission'])->name('permissions.delete');
        Route::delete('subscriptionPermissions/{permissionId}', [MasterController::class, 'deleteSubscriptionPermission'])->name('subscriptionPermissions.delete');
        Route::get('library/show/{id?}', [LibraryController::class, 'showLibrary'])->name('library.show');
        Route::delete('library/learners/delete/{id?}', [LibraryController::class, 'destroyLearners'])->name('library.learners.destroy');
        Route::delete('library/masters/delete/{id?}', [LibraryController::class, 'destroyAllMasters'])->name('library.masters.destroy');
        Route::get('create/notification', [NotificationController::class, 'create'])->name('create.notification'); 
        Route::get('edit/notification/{id?}', [NotificationController::class, 'edit'])->name('notifications.edit'); 
        Route::post('/notifications/send', [NotificationController::class, 'send'])->name('notifications.send'); 
        Route::put('/notifications/update', [NotificationController::class, 'update'])->name('notifications.update');
        Route::get('/library/count/view', [DashboardController::class, 'libraryView'])->name('library.count.view');
        Route::get('/features', [MasterController::class, 'featureCreate'])->name('feature.create');
        Route::post('/features/store/{id?}', [MasterController::class, 'storeFeature'])->name('feature.storeFeature');
        Route::get('/features/edit/{id}', [MasterController::class, 'featureEdit'])->name('feature.edit');
        Route::delete('/features/delete/{id}', [MasterController::class, 'destroy'])->name('feature.destroy');
        Route::get('/add-page', [SiteController::class, 'createpage'])->name('add-page');
        Route::get('/page', [SiteController::class, 'listPage'])->name('page');
        Route::post('/page/store/{id?}', [SiteController::class, 'pageStore'])->name('page.store');
        Route::get('/page/edit/{id}', [SiteController::class, 'editPage'])->name('page.edit');
        Route::get('/add-blog', [SiteController::class, 'createBlog'])->name('add-blog');
        Route::post('/blog/store/{id?}', [SiteController::class, 'blogStore'])->name('blog.store');
        Route::get('/blogs', [SiteController::class, 'listBlog'])->name('blogs');
        Route::get('/blog/edit/{id}', [SiteController::class, 'editBlog'])->name('blog.edit');
        Route::get('/inquery', [SiteController::class, 'inqueryShow'])->name('inquiry');
        Route::get('/demo', [SiteController::class, 'demoRequest'])->name('demo');
        Route::get('menu/create', [DataController::class, 'create'])->name('menu.create');
        Route::post('menu/store', [DataController::class, 'store'])->name('menu.store');
        Route::get('menu/edit/{id?}', [DataController::class, 'edit'])->name('menu.edit');
        Route::put('menu/update', [DataController::class, 'update'])->name('menu.update');
        Route::delete('menu/destroy', [DataController::class, 'delete'])->name('menu.destroy');
        Route::get('inquery/get', [AdminController::class, 'contactInqueryGet'])->name('inquery.list');
        Route::get('demo/request/get', [AdminController::class, 'demoRequestGet'])->name('demo.list');
        Route::get('library/upgrade/{id?}', [AdminController::class, 'libraryUpgrade'])->name('library.upgrade');
       
      
        });
});

Route::middleware(['auth:learner'])->group(function () {
  // Route::get('list/notification', [NotificationController::class, 'show'])->name('list.notification');
  Route::get('learner/home', [DashboardController::class, 'learnerDashboard'])->name('learner.home');//learner dashboard
  Route::get('learner/profile', [LearnerController::class, 'learnerProfile'])->name('learner.profile');
  Route::get('learner/request', [LearnerController::class, 'learnerRequest'])->name('learner.request');
  Route::get('learner/IdCard', [LearnerController::class, 'IdCard'])->name('my-library-id');
  Route::get('learner/support', [LearnerController::class, 'support'])->name('support');
  Route::get('learner/blog', [LearnerController::class, 'blog'])->name('learner.blog');
  Route::get('learner/blog/detail/show', [LearnerController::class, 'blogDetailShow'])->name('blog.detail.show');
  Route::get('learner/feadback', [LearnerController::class, 'feadback'])->name('learner.feadback');
  Route::get('learner/suggestions', [LearnerController::class, 'suggestions'])->name('learner.suggestions');
  Route::get('learner/attendance', [LearnerController::class, 'attendance'])->name('my-attendance');
  Route::get('learner/complaints', [LearnerController::class, 'complaints'])->name('complaints');
  Route::get('learner/transactions', [LearnerController::class, 'transactions'])->name('my-transactions');
  Route::get('learner/books-library', [LearnerController::class, 'booksLibrary'])->name('books-library');
  Route::post('learner/request/store', [LearnerController::class, 'learnerRequestCreate'])->name('requaet.store');
  Route::post('learner/suggestions/store', [LearnerController::class, 'suggestionsStore'])->name('learner.suggestion.store');
  Route::post('learner/complaints/store', [LearnerController::class, 'complaintsStore'])->name('learner.complaint.store');
  Route::post('learner/feadback/store', [LearnerController::class, 'feadbackStore'])->name('learner.feedback.store');
  
});



