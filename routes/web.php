<?php

use App\Http\Controllers\AccountController;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AdminLimoController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\BookManagementController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\LibraryDashboardController;
use App\Http\Controllers\LearnerDashboardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\DemoUserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\LeadContactController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\LearnerDeleteController;
use App\Http\Controllers\LibraryAdminController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LibraryReferralController;
use App\Http\Controllers\LibraryUserController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\LearnerStudyController;
use App\Http\Controllers\LibraryQuizController;
use App\Http\Controllers\GlobalDiscountController;
use App\Http\Controllers\LearnerChatController;
use App\Http\Controllers\LibraryChatController;
use App\Http\Controllers\MessageTemplateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationSentController;
use App\Http\Controllers\QrEntryController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RenewDeleteController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;




Route::prefix('leads')->name('leads.')->middleware(['portal:admin', 'auth:web'])->group(function () {
    Route::get('/', [LeadContactController::class, 'index'])->name('index');
    

    Route::post('/store', [LeadContactController::class, 'store'])->name('store');
    Route::post('/{lead}/comment', [LeadContactController::class, 'addComment'])->name('comment');
    Route::post('/{lead}/call-status', [LeadContactController::class, 'updateCallStatus'])->name('call');
    Route::get('/{lead}/save-contact', [LeadContactController::class, 'saveContact'])->name('saveContact');
});


Route::get('refresh-csrf', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.refresh')->middleware('portal:auto');

// Secret admin login URL (ADMIN_LOGIN_PATH in .env, see config/admin.php)
Route::get(config('admin.login_path'), [LoginController::class, 'showLoginForm'])->name('login.administrator')->middleware('portal:admin');
Route::get('library/login', [LoginController::class, 'showAdminLoginForm'])->name('login.library');

Route::get('learner/login', [LoginController::class, 'showLearnerLoginForm'])->name('login.learner')->middleware('portal:learner');
Route::post('login/store', [LoginController::class, 'login'])->name('login.store')->middleware('portal:auto');
Route::get('library/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
// Auth routes
Auth::routes(['register' => false, 'login' => false, 'verify' => false]);
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('portal:auto')->withoutMiddleware('auth');

Route::group(['prefix' => 'library'], function () {
  Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request.library');
  Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email.library');


  Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset.library');
  Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update.library');
});

Route::get('/attendance/logs',[AttendanceController::class, 'logs'])->name('attendance.logs');
Route::get('/get-libraries', [MasterController::class, 'getLibraries'])->name('get-libraries');
Route::get('/email/verify', [LibraryController::class, 'emailVerification'])->name('verification.notice');
Route::post('/verify-otp', [LibraryController::class, 'verifyOtp'])->name('verify.otp');
Route::get('library/choose-plan-price', [LibraryController::class, 'getSubscriptionPrice'])->name('subscriptions.getSubscriptionPrice');
Route::get('cityGetStateWise', [MasterController::class, 'stateWiseCity'])->name('cityGetStateWise');
Route::post('library/store', [LibraryController::class, 'store'])->name('library.store');
Route::post('/fee/generate-receipt', [Controller::class, 'generateReceipt'])->name('fee.generateReceipt');
Route::get('library-managment-software', [SiteController::class, 'libraryManagmentLandingPage'])->name('library.managment.software');
Route::post('lead/store', [SiteController::class, 'leadstore'])->name('lead.store');
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
Route::get('blog/{slug}', [SiteController::class, 'blogDetail'])->name('blog-detail');
Route::get('blog-sitemap.xml', [SiteController::class, 'blogSitemap'])->name('blog.sitemap');
Route::get('getLibrariesLocations', [SiteController::class, 'getLibrariesLocations'])->name('getLibrariesLocations');
Route::post('/submit-review', [SiteController::class, 'reviewstore'])->name('submit.review');
Route::post('/store/library/inquiry', [SiteController::class, 'libraryInquerystore'])->name('submit.library.inquiry');
Route::get('/home/library_user', [LibraryDashboardController::class, 'librar_UserDashboard'])->name('library.user.login'); 
 //QR code feature
Route::get('/getPlanType/seatwise', [QrEntryController::class, 'getPlanTypeSeatWise'])->name('getPlantypeSeatwise');
Route::get('/getPlanType/seatWise', [QrEntryController::class, 'getPlanTypeForRenew'])->name('getPlanTypeForRenew');
Route::get('/qr/b/{uuid}', [QrEntryController::class, 'showOptions'])->name('qr.branch');
Route::get('/branch/{uuid}/book-seat', [QrEntryController::class, 'bookSeat'])->name('booking.form');
Route::get('/branch/{uuid}/renew-seat', [QrEntryController::class, 'renewSeat'])->name('renew.form');
Route::post('/get-plan-price', [QrEntryController::class, 'getPlanPrice'])->name('get.plan.price');
Route::post('/branch/{uuid}/book-seat', [QrEntryController::class, 'store'])->name('booking.store');
Route::get('/booking/{id}/payment-qr', [QrEntryController::class, 'showPaymentQR'])->middleware('signed')->name('booking.payment.qr');
Route::get('/booking/{id}/thank-you', [QrEntryController::class, 'showOfflineDetails'])->middleware('signed')->name('booking.offline.details');
Route::post('/renew/{uuid}/find', [QrEntryController::class, 'findCustomer'])->name('renew.find');
// same action as renew.store below; its own name so the routes can be cached (route:cache refuses duplicate names)
Route::post('/renew/{uuid}/store', [QrEntryController::class, 'renewStore'])->name('renew.store.legacy');
Route::post('/booking/{id}/upload-screenshot', [QrEntryController::class, 'uploadScreenshot'])->middleware('signed')->name('booking.upload.screenshot');
Route::post('/renew/{uuid}', [QrEntryController::class, 'renewStore'])->name('renew.store');
Route::get('/branch/{uuid}/qr-pdf', [QrEntryController::class, 'downloadBranchQR'])->name('branch.qr.pdf');
Route::get('/qr/attendance/link', [AttendanceController::class, 'showLink'])->name('qr.attendance.link');
Route::post('qr/attendance/scan', [AttendanceController::class, 'scanAttendance'])->name('store.scan.attendance');
Route::post('/attendance/verify-learner', [AttendanceController::class, 'verifyLearner'])->name('attendance.verify.learner');
Route::get('/attendance/success', [AttendanceController::class, 'markSuccess'])->name('attendance.success');
Route::get('/verify/learner/dashboard', [AttendanceController::class, 'dashboard'])->name('attendance.dashboard');
Route::post('/attendance/auto-verify', [AttendanceController::class,'autoVerify']);
Route::get('/attendance/instructions', [AttendanceController::class, 'view'])->name('attendance.instructions');
Route::get('/attendance/instructions/pdf', [AttendanceController::class, 'downloadPdf'])->name('attendance.instructions.pdf');


Route::get('/find-my-library', function () {
    return redirect()->route('find-my-library');
});
Route::get('/receipt/signed/{transactionId}', [LearnerController::class, 'viewReceipt'])
    ->middleware('signed')
    ->name('receipt.signed');
Route::get('/receipt/mobile/signed/{transactionId}', [ReceiptController::class, 'mobile'])
    ->middleware('signed')
    ->name('receipt.mobile.signed');
Route::get('/receipt/library/signed/{transactionId}', [ReceiptController::class, 'library'])
    ->middleware('signed')
    ->name('receipt.library.signed');
Route::get('/receipt/other-payment/signed/{activityId}', [ReceiptController::class, 'otherPayment'])
    ->middleware('signed')
    ->name('receipt.other-payment.signed');
Route::get('/id-card/signed/pdf/{id}', [IdCardController::class, 'pdf'])
    ->middleware('signed')
    ->name('idCard.signed.pdf');
Route::get('/id-card/signed/mobile/{id}', [IdCardController::class, 'mobile'])
    ->middleware('signed')
    ->name('idCard.signed.mobile');
Route::get('/get-chargeable-days', [LearnerController::class, 'getChargeableDaysAjax'])->name('getChargeableDays');
// Routes for library users with 'auth:library' guard
Route::middleware(['portal:library', 'auth.library_or_user', 'verified.library', 'log.requests'])->group(function () {
 
  Route::post('/learner/receipt/download', [ReceiptController::class, 'learnerDownload'])->name('learner.receipt.download');
  Route::get('/booking/{id}/details', [QrEntryController::class, 'showBookingDetails'])->name('booking.details');
  Route::delete('/booking/{id}', [QrEntryController::class, 'destroy'])->name('booking.destroy');
  Route::post('/booking/approve', [QrEntryController::class, 'requestApproveEdit'])->name('booking.details.approve');
  Route::get('/branch/index', [BranchController::class, 'index'])->name('branch.list');

  // Study quizzes made by the library (shown to its learners)
  // Student chat (end-to-end encrypted, owner only)
  Route::middleware('library.owner')->prefix('library/chat')->group(function () {
    Route::get('/', [LibraryChatController::class, 'index'])->name('chat.index');
    Route::get('/students', [LibraryChatController::class, 'students'])->name('chat.students');
    Route::get('/unread', [LibraryChatController::class, 'unread'])->name('chat.unread');
    Route::post('/key', [LibraryChatController::class, 'registerOwnKey'])->name('chat.key.own');
    Route::get('/{learner}', [LibraryChatController::class, 'thread'])->whereNumber('learner')->name('chat.thread');
    Route::post('/{learner}/key', [LibraryChatController::class, 'registerKey'])->whereNumber('learner')->name('chat.key');
    Route::get('/{learner}/messages', [LibraryChatController::class, 'messages'])->whereNumber('learner')->name('chat.messages');
    Route::post('/{learner}/send', [LibraryChatController::class, 'send'])->whereNumber('learner')->middleware('throttle:60,1')->name('chat.send');
    Route::post('/{learner}/read', [LibraryChatController::class, 'read'])->whereNumber('learner')->name('chat.read');
    Route::post('/{learner}/file', [LibraryChatController::class, 'upload'])->whereNumber('learner')->middleware('throttle:40,1')->name('chat.upload');
    Route::get('/{learner}/file/{token}', [LibraryChatController::class, 'file'])->whereNumber('learner')->name('chat.file');
  });

  // Advanced Management → Global Discount: one offer every booking form applies by itself (docs/GLOBAL_DISCOUNT.md)
  Route::middleware(['library.owner', 'library.can:Global Discount'])->prefix('library/global-discount')->group(function () {
    Route::get('/', [GlobalDiscountController::class, 'index'])->name('global-discount.index');
    Route::post('/', [GlobalDiscountController::class, 'store'])->name('global-discount.store');
    Route::put('/{id}', [GlobalDiscountController::class, 'update'])->whereNumber('id')->name('global-discount.update');
    Route::post('/{id}/toggle', [GlobalDiscountController::class, 'toggle'])->whereNumber('id')->name('global-discount.toggle');
    Route::delete('/{id}', [GlobalDiscountController::class, 'destroy'])->whereNumber('id')->name('global-discount.destroy');
  });

  Route::middleware(['library.owner', 'library.can:Student Quiz'])->prefix('library/quizzes')->group(function () {
    Route::get('/', [LibraryQuizController::class, 'index'])->name('quiz.index');
    Route::get('/create', [LibraryQuizController::class, 'form'])->name('quiz.create');
    Route::post('/', [LibraryQuizController::class, 'save'])->name('quiz.store');
    Route::get('/{id}/edit', [LibraryQuizController::class, 'form'])->name('quiz.edit');
    Route::put('/{id}', [LibraryQuizController::class, 'save'])->name('quiz.update');
    Route::post('/{id}/publish', [LibraryQuizController::class, 'toggle'])->name('quiz.toggle');
    Route::delete('/{id}', [LibraryQuizController::class, 'destroy'])->name('quiz.destroy');
  });

  Route::middleware('library.owner')->group(function () {
    Route::delete('/branch/{id}', [BranchController::class, 'destroy'])->name('branch.destroy');
    Route::post('/branch', [BranchController::class, 'store'])->name('branch.store');
    Route::get('/branch/create', [BranchController::class, 'branchForm'])->name('branch.create');
    // Edit form
    Route::get('/branch/{id}/edit', [BranchController::class, 'branchForm'])->name('branch.edit');
    Route::put('/branch/{id}', [BranchController::class, 'update'])->name('branch.update');
    // branch card switch: money above the bill as advance or a higher price (docs/LEARNER_ADVANCE.md)
    Route::post('/branch/{id}/higher-price', [BranchController::class, 'toggleHigherPrice'])->name('branch.higherPrice');
    // Operating hours page: library open / close time
    Route::post('/branch/{id}/hours', [BranchController::class, 'updateHours'])->name('branch.hours.update');

    Route::get('/library-users', [LibraryUserController::class, 'index'])->name('library-users.index');
    // predefined staff roles and permissions, view only
    Route::get('/library-users/roles', [LibraryUserController::class, 'roles'])->name('library-users.roles');
    Route::get('/library-users/permission-list', [LibraryUserController::class, 'permissionList'])->name('library-users.permission-list');
    Route::get('/library-users/create/{id?}', [LibraryUserController::class, 'create'])->name('library-users.create');
    Route::post('/library-users/store', [LibraryUserController::class, 'store'])->name('library-users.store');
    Route::post('/library-users/toggle-status/{id}', [LibraryUserController::class, 'toggleStatus']);
    Route::get('library-users/{user}/permissions', [LibraryUserController::class, 'editPermissions'])->name('library-users.permissions');
    Route::post('library-users/{user}/permissions', [LibraryUserController::class, 'updatePermissions'])->name('library-users.permissions.update');
  });


  Route::post('/library/master/upload', [Controller::class, 'uploadmastercsv'])->name('library.master.upload');
  Route::post('/dashboard/data', [LibraryDashboardController::class, 'getData'])->name('dashboard.data.get');
  Route::get('export-learners-csv', [Controller::class, 'exportLearnerCSV'])->name('learners.export-csv');
  
  Route::post('/csv/library/learner/upload', [Controller::class, 'uploadCsv'])->name('library.csv.upload');
  Route::get('/export-invalid-records/library', [Controller::class, 'exportCsv'])->name('library.export.invalid.records');
  Route::post('/clear-invalid-records/library', [Controller::class, 'clearSession'])->name('library.clear.session');
  Route::get('/renew/configration/library', [Controller::class, 'renewConfigration'])->name('renew.configration');

  Route::get('/learner/expire/{id?}', [LearnerController::class, 'learnerExpire'])->name('learner.expire');
  Route::put('/learner/expire/update/{id?}', [LearnerController::class, 'editLearnerExpire'])->name('learner.expire.update');
  Route::get('/add/expense/list', [LibraryController::class, 'expenceList'])->name('add.expense.list')->middleware("library.can:Add Daily Expense");
  Route::get('/add/expense/list/page', [LibraryController::class, 'expenceListPage'])->name('add.expense.list.page')->middleware("library.can:Add Daily Expense");
  Route::get('/add/expense/list/fragment', [LibraryController::class, 'expenceListFragment'])->name('add.expense.list.fragment')->middleware("library.can:Add Daily Expense");
  Route::post('/add/expense/store', [LibraryController::class, 'expenceStore'])->name('daily.expense.store')->middleware("library.can:Add Daily Expense");
  Route::delete('/add/expense/{id}', [LibraryController::class, 'expencedestroy'])->name('add.expenses.destroy')->middleware("library.can:Add Daily Expense");

  //**LEARNER**//
  Route::get('library/learners', [LearnerController::class, 'index'])->name('seats');
  Route::post('library/learners/log', [LearnerController::class, 'learnerLog'])->name('learner.log');

  Route::prefix('library')->group(function () {
    // AI Chat Assistant routes
    Route::post('/ai-chat/send', [AiAssistantController::class, 'sendQuery'])->name('ai.chat.send');
    Route::get('/ai-chat/history', [AiAssistantController::class, 'getHistory'])->name('ai.chat.history');
    Route::post('/ai-chat/clear', [AiAssistantController::class, 'clearHistory'])->name('ai.chat.clear');
    Route::post('/ai-chat/feedback', [AiAssistantController::class, 'feedback'])->name('ai.chat.feedback');
    Route::get('/ai-chat/alerts', [AiAssistantController::class, 'alerts'])->name('ai.chat.alerts');
    Route::get('/ai-chat/export/{id}', [AiAssistantController::class, 'exportPdf'])->whereNumber('id')->name('ai.chat.export');

    Route::get('/choose-plan', [LibraryController::class, 'choosePlan'])->name('subscriptions.choosePlan')->middleware('library.owner');
    Route::post('/payment-store', [LibraryController::class, 'paymentStore'])->name('library.payment.store');
    Route::get('/payment/store', [LibraryController::class, 'payment'])->name('payment.show');

    Route::post('/payment/store', [LibraryController::class, 'payment'])->name('payment.store');
    Route::get('/branch/configure/create', [BranchController::class, 'branchConfigurForm'])->name('branch.configure.create')->middleware('library.owner');
    Route::post('/branch/configure', [BranchController::class, 'branchConfigure'])->name('branch.configure')->middleware('library.owner');
    Route::get('/configration', [LibraryController::class, 'masterConfigration'])->name('library.configration');
    Route::post('/master/configuration/store',[LibraryController::class, 'configrationStore'])->name('master.configuration.store');
    Route::get('/home', [LibraryDashboardController::class, 'libraryDashboard'])->name('library.home');
    Route::get('/home/live-attendance', [LibraryDashboardController::class, 'liveAttendance'])->name('library.dashboard.live');
    Route::get('/transaction', [LibraryController::class, 'transaction'])->name('library.transaction');
    Route::get('/myplan', [LibraryController::class, 'myplan'])->name('library.myplan')->middleware('library.owner');
    Route::post('/plan-type/delete', [MasterController::class, 'deletePlanType'])->name('plan-type.delete');

    Route::get('/library-master', [MasterController::class, 'masterPlan'])->name('library.master');
    Route::get('/plantype', [MasterController::class, 'planTypeView'])->name('plantype.index')->middleware("library.can:Add Plan Type");
    Route::get('/plantype/create/{id?}', [MasterController::class, 'planTypeCreate'])->name('planType.create')->middleware("library.can:Add Plan Type");
    Route::get('/floors', [MasterController::class, 'floorView'])->name('floor.index');
    Route::get('/floor/create/{id?}', [MasterController::class, 'floorCreate'])->name('floor.create');
    Route::get('/plan/list', [MasterController::class, 'planView'])->name('plan.index')->middleware("library.can:Add Plan");
    Route::get('/plan/create/{id?}', [MasterController::class, 'planCreate'])->name('plan.create')->middleware("library.can:Add Plan");
    Route::get('/expense/list', [MasterController::class, 'expenseView'])->name('expense.index')->middleware("library.can:Add Expense");
    Route::get('/expense/create/{id?}', [MasterController::class, 'expenseCreate'])->name('expense.create')->middleware("library.can:Add Expense");
    Route::get('/exam/list', [MasterController::class, 'examView'])->name('exam.index')->middleware("library.can:Add Exam");
    Route::get('/exam/create/{id?}', [MasterController::class, 'examCreate'])->name('exam.create')->middleware("library.can:Add Exam");
    Route::get('/seat/create/{id?}', [MasterController::class, 'seatCreate'])->name('seat.create');
    Route::get('/hour/create/{id?}', [MasterController::class, 'hourCreate'])->name('hour.create');
    Route::get('/extendDay/create/{id?}', [MasterController::class, 'extendDayCreate'])->name('extendDay.create');
    Route::get('/locker/amount/create/{id?}', [MasterController::class, 'lockerAmountCreate'])->name('lockeramount.create');
    Route::get('/token/amount/create/{id?}', [MasterController::class, 'tokenAmountCreate'])->name('tokenAmount.create');
    Route::get('/planPrice/create/{id?}', [MasterController::class, 'planPriceCreate'])->name('planPrice.create')->middleware("library.can:Add Plan Price");
    Route::get('/planPrice/list', [MasterController::class, 'planPriceView'])->name('planPrice.index')->middleware("library.can:Add Plan Price");
    Route::get('/master/account', [LibraryController::class, 'sidebarRedirect'])->name('library.master.account');
    Route::get('/subscriptions/payment-add', [LibraryController::class, 'paymentProcess'])->name('subscriptions.payment')->middleware('library.owner');

    Route::post('/subscriptions/payment-add', [LibraryController::class, 'paymentProcess'])->name('subscriptions.payment.store')->middleware('library.owner');

    Route::get('/profile', [LibraryController::class, 'profile'])->name('profile');
    Route::post('/profile/update', [LibraryController::class, 'updateProfile'])->name('library.profile.update')->middleware('library.owner');
    Route::post('/payment/success', [LibraryController::class, 'handleSuccess'])->name('library.payment.success');
    Route::get('/payment/error', [LibraryController::class, 'handleError'])->name('library.payment.error');
    Route::get('/toggle/feature/list', [MasterController::class, 'toggleFeature'])->name('toggle.feature');
    Route::post('/branch/update/hidefield', [MasterController::class, 'updateHidefield'])->name('branch.update.hidefield');
    Route::get('/csv/upload', [Controller::class, 'showUploadForm'])->name('library.upload.form');

    Route::post('/master/store', [MasterController::class, 'storemaster'])->name('master.store');
    Route::get('/master/edit', [MasterController::class, 'masterEdit'])->name('master.edit');

    Route::post('/extend-day', [MasterController::class, 'extendDay'])->name('extendDay.store');

    Route::delete('/activeDeactive/{id}/toggle', [MasterController::class, 'activeDeactive'])->name('library.activeDeactive');
    Route::delete('/delete-master/{id}', [MasterController::class, 'deleteMaster'])->name('master.delete');


    //Report menu
    Route::get('monthly/create', [ReportController::class, 'monthlyReport'])->name('report.monthly')->middleware("library.can:Manage Report");
    Route::get('report/expense/{year}/{month}', [ReportController::class, 'monthlyExpenseCreate'])->name('report.expense')->middleware("library.can:Manage Report");
    Route::post('report/expense/store', [ReportController::class, 'monthlyExpenseStore'])->name('report.expense.store')->middleware("library.can:Manage Report");
    Route::get('library/video-training', [LibraryController::class, 'videoTraining'])->name('library.video-training');

    // Other Pages Routes

    Route::get('reports', [ReportController::class, 'hub'])->name('reports.hub')->middleware("library.can:Manage Report");
    Route::get('reports/occupancy', [ReportController::class, 'occupancy'])->name('reports.occupancy')->middleware("library.can:Manage Report");
    Route::get('reports/growth', [ReportController::class, 'growth'])->name('reports.growth')->middleware("library.can:Manage Report");
    Route::get('monthly/payment/collection', [ReportController::class, 'monthlyPaymentCollection'])->name('monthly.payment.collection.report')->middleware("library.can:Manage Report");
    Route::get('/monthly-payment-export', [ReportController::class, 'exportMonthlyPayment'])->name('monthly.payment.export');

    Route::get('pending/payment/report', [ReportController::class, 'pendingPayment'])->name('pending.payment.report')->middleware("library.can:Manage Report");
    Route::get('activity/report', [ReportController::class, 'activity'])->name('activity.report')->middleware("library.can:Manage Report");
    Route::get('activities', [LibraryDashboardController::class, 'allActivities'])->name('activities.all');
    Route::get('attendance/report', [ReportController::class, 'attendanceReport'])->name('attendance.report')->middleware("library.can:Manage Report");
    Route::get('payment/collection/report', [ReportController::class, 'paymentCollection'])->name('payment.collection.report')->middleware("library.can:Manage Report");
    Route::get('partial/payment/collection/report', [ReportController::class, 'partialPaymentCollection'])->name('partial.payment.collection.report')->middleware("library.can:Manage Report");
    Route::get('learner/report', [ReportController::class, 'learnerReport'])->name('learner.report')->middleware("library.can:Manage Report");
    Route::get('upcoming/payment/report', [ReportController::class, 'upcomingPayment'])->name('upcoming.payment.report')->middleware("library.can:Manage Report");
    Route::get('expired/learner/report', [ReportController::class, 'expiredLearner'])->name('expired.learner.report')->middleware("library.can:Manage Report");
    Route::post('increment-message-count', [LearnerController::class, 'incrementMessageCount'])->name('increment.message.count');
    Route::get('settings', [LibraryController::class, 'librarySetting'])->name('library.settings');
    Route::post('settings/store', [LibraryController::class, 'SettingStore'])->name('library.settings.store');
    Route::get('feedback', [LibraryController::class, 'libraryFeedback'])->name('library.feedback');
    Route::post('feedback/store', [LibraryController::class, 'feedbackStore'])->name('library.feedback.store');
    Route::get('list/notification', [NotificationController::class, 'show'])->name('list.notification');
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/unread', [NotificationController::class, 'markAsUnread'])->name('notifications.markAsUnread');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::get('enquiry', [LibraryController::class, 'getEnquiry'])->name('library.enquiry');
    Route::delete('enquiry/{id}', [LibraryController::class, 'deleteEnquiry'])->name('library.enquiry.destroy');
    Route::post('branch/switch', [BranchController::class, 'switch'])->name('branch.switch');
    Route::get('book/category', [BookManagementController::class, 'categoryIndex'])->name('book.category.index');
    Route::get('book/category/create/{id?}', [BookManagementController::class, 'categoryCreate'])->name('book.category.create');

    Route::get('message-templates', [MessageTemplateController::class, 'index'])->name('message.templates');
    Route::post('message-templates/update', [MessageTemplateController::class, 'update'])->name('message.templates.update');

    Route::get('notifications/subscription', [NotificationSentController::class, 'index'])->name('notifications.subscription');
    Route::post('notifications/subscription/purchase', [NotificationSentController::class, 'purchase'])->name('notifications.subscription.purchase');
    Route::get('notifications/settings', [NotificationSentController::class, 'settingsForm'])->name('notifications.settings');
    Route::post('notifications/settings/store', [NotificationSentController::class, 'settingStore'])->name('notification.settings.save');
    

    Route::get('/notification/payment/verify', [NotificationSentController::class, 'verifyPayment'])->name('notification.payment.verify');
    Route::get('/notification/dashboard', [NotificationSentController::class, 'dashboard'])->name('notification.dashboard');
    Route::post('/notification/render-message', [NotificationSentController::class, 'renderMessage'])->name('notification.renderMessage');
    Route::post('/notification/send-message',[NotificationSentController::class, 'sendMessage'])->name('notification.sendMessage');
    Route::post('/get-learner-mobiles', [NotificationSentController::class, 'getLearnerMobiles'])->name('notification.getLearnerMobiles');

    Route::get('/how-to-use', [LibraryDashboardController::class, 'howToUse'])->name('library.how-to-use');
    Route::get('daily-dashboard',[ServiceController::class,'daily_dashboard'])->name('daily_dashboard');
    Route::get('/referral/dashboard', [LibraryReferralController::class, 'dashboard'])->name('referral.dashboard');
    Route::post('/library/redeem', [LibraryReferralController::class, 'redeem'])->name('library.redeem');
    Route::get('/attendance/apply', [AttendanceController::class, 'index'])->name('attendance.apply');
    Route::get('/attendance/qr', [AttendanceController::class, 'generate'])->name('attendance.qrcode');
    Route::get('/attendance/qr/poster', [AttendanceController::class, 'staticQrPoster'])->name('attendance.qrcode.poster');
    Route::post('/attendance/scan', [AttendanceController::class, 'scan'])->name('library.attendance.scan');
    
    Route::get('/library/daily-popup', [LibraryController::class, 'dailyPopup'])->name('library.daily-popup');

    Route::post('/library/daily-popup/confirm', [LibraryController::class, 'confirmDailyPopup'])->name('library.daily-popup.confirm');


  });

  Route::prefix('library/learners')->group(function () {
    Route::get('future/bookings', [LearnerController::class, 'learnerFuture'])->name('future.bookings');
    Route::post('/store', [LearnerController::class, 'learnerStore'])->name('learners.store');
    Route::get('/booking-done/{detailId}', [LearnerController::class, 'bookingDone'])->name('learners.booking.done');
    Route::post('/generallearner/store', [LearnerController::class, 'generallearnerStore'])->name('genral.learners.store');
    Route::get('/list', [LearnerController::class, 'learnerList'])->name('learners');
    Route::post('/sync-status', [LearnerController::class, 'syncStatus'])->name('learners.sync.status');
    Route::get('/list/pdf', [LearnerController::class, 'learnerListPdf'])->name('learners.list.pdf');
    Route::get('/search', [LearnerController::class, 'learnerSearch'])->name('learner.search');
    Route::get('/renew-delete', [RenewDeleteController::class, 'index'])->name('create.renew.delete.index');
    Route::get('/renew-delete/{learner}/transaction', [RenewDeleteController::class, 'showTransaction'])->name('create.renew.delete.transaction');
    Route::post('/renew-delete/transactions/{transaction}/delete', [RenewDeleteController::class, 'destroy'])->name('create.renew.delete.destroy');
    Route::get('/history/list', [LearnerController::class, 'learnerHistory'])->name('learnerHistory');
    Route::get('/booking-info/{id?}', [LearnerController::class, 'showLearner'])->name('learners.show');
    Route::get('/edit-plan/{id?}', [LearnerController::class, 'getUser'])->name('learners.edit.plan')->middleware("library.can:Edit Plan");
    Route::get('/edit/{id?}', [LearnerController::class, 'getUser'])->name('learners.edit');
    Route::put('/upgrade/update/{id?}', [LearnerController::class, 'userUpdate'])->name('learners.update.upgrade');
    Route::post('/change/plan/update/{id?}', [LearnerController::class, 'changePlanUpdate'])->name('learners.update.changePlan')->middleware("library.can:Change Plan");
    Route::put('/update/{id?}', [LearnerController::class, 'learnerUpdate'])->name('learners.update');

    Route::get('/swap/{id?}', [LearnerController::class, 'getSwapUser'])->name('learners.swap');
    Route::put('/swap-seat', [LearnerController::class, 'swapSeat'])->name('learners.swap-seat');
    Route::get('/change/plan/{id?}', [LearnerController::class, 'getLearner'])->name('learner.change.plan')->middleware("library.can:Change Plan");
    Route::post('/close', [LearnerController::class, 'userclose'])->name('learners.close');
    Route::delete('/{Learner}', [LearnerController::class, 'destroy'])->name('learners.destroy');
    Route::get('/{Learner}/settlement/details', [LearnerDeleteController::class, 'settlementDetails'])->name('learners.settlement.details');
    Route::get('/{Learner}/soft-delete-v2/details', [LearnerDeleteController::class, 'softDeleteV2Details'])->name('learners.soft.destroy.v2.details');
    // Route::post('/{Learner}/soft-delete-v2', [LearnerController::class, 'softDeleteV2'])->name('learners.soft.destroy.v2');
   

      Route::post('/{Learner}/soft-delete-v2', [LearnerDeleteController::class, 'prepare'])->name('learners.soft.destroy.v2');
      Route::get('/settlement/{learnerId?}/{detailId?}', [LearnerController::class, 'settlementPage'])->name('learners.settlement.page');
      Route::post('/settlement/{id}', [LearnerController::class, 'settlement'])->name('learners.settlement');
      Route::post('/execute/{id}', [LearnerDeleteController::class, 'delete']);


    Route::get('/reactive/{id?}', [LearnerController::class, 'reactiveUser'])->name('learners.reactive');
    Route::put('/reactive/{id?}', [LearnerController::class, 'reactiveLearner'])->name('learner.reactive.store');
    Route::get('/payment/{id?}', [LearnerController::class, 'makePayment'])->name('learner.payment');
    Route::post('/payment/store', [LearnerController::class, 'paymentStore'])->name('learner.payment.store');
    Route::get('/getTransactionDetail', [LearnerController::class, 'getTransactionDetail'])->name('getTransactionDetail');
    Route::get('/transactions-data/{learnerId}', [LearnerController::class, 'learnerTransactionsModalData'])->name('learners.transactions.modal')->middleware("library.can:Learner Transactions");
    Route::get('/{learner}/transactions', [LearnerController::class, 'learnerTransactionSection'])->name('learners.transactions')->middleware("library.can:Learner Transactions");
    Route::get('/transactions/{transaction}/edit', [LearnerController::class, 'editTransaction'])->name('learners.transactions.edit')->middleware("library.can:Learner Transactions");
    Route::put('/transactions/{transaction}', [LearnerController::class, 'updateTransaction'])->name('learners.transactions.update')->middleware("library.can:Learner Transactions");
    Route::delete('/transactions/{transaction}', [LearnerController::class, 'destroyTransaction'])->name('learners.transactions.destroy')->middleware("library.can:Learner Transactions");
    Route::delete('/transactions/activity/{activity}', [LearnerController::class, 'destroyTransactionActivity'])->name('learners.transactions.activity.destroy')->middleware("library.can:Learner Transactions");
    Route::get('pending/payment/{id?}', [LearnerController::class, 'pendingPayment'])->name('learner.pending.payment');
    Route::post('pending/payment/store', [LearnerController::class, 'pendingPaymentStore'])->name('learner.pending.payment.store');
    Route::get('pending-payment/list', [LearnerController::class, 'pendingPaymentList'])->name('learner.pending.payment.list');

    Route::get('/seats/view', [LibraryDashboardController::class, 'viewSeats'])->name('learners.list.view');
    Route::get('/upgrade/{id?}', [LearnerController::class, 'getLearner'])->name('learners.upgrade')->middleware("library.can:Upgrade Seat Plan");
    Route::get('/renew/{id?}', [LearnerController::class, 'getLearner'])->name('learner.renew.plan');
    Route::post('/upgrade/renew/store', [LearnerController::class, 'learnerUpgradeRenew'])->name('learner.upgrade.renew.store');
    Route::get('/attendance', [LearnerController::class, 'learnerAttendence'])->name('attendance');
    Route::get('get/learner/attendance', [LearnerController::class, 'getLearnerAttendence'])->name('get.learner.attendance');
    Route::post('/update-attendance', [LearnerController::class, 'updateAttendance'])->name('update.attendance');
    Route::get('/complaints', [LibraryController::class, 'learnerComplaints'])->name('library.learner.complaints');
    Route::get('/suggestions', [LibraryController::class, 'learnerSuggestions'])->name('library.learner.suggestions');
    Route::get('/feedback', [LibraryController::class, 'learnerFeedback'])->name('library.learner.feedback');
    Route::post('/clarification/submit/status', [LibraryController::class, 'clarificationStatus'])->name('clarification.submit.status');
    Route::get('/library/transaction/view', [LibraryDashboardController::class, 'libraryTran'])->name('library.transaction.view');
    Route::get('other/payment/{id?}', [LearnerController::class, 'makeOtherPayment'])->name('learner.other.payment');
    Route::post('other/payment/store', [LearnerController::class, 'otherPaymentStore'])->name('learner.other.payment.store');
    Route::get('learner/checklist', [LearnerController::class, 'learnerChecklist'])->name('learner.checklist')->middleware("library.can:Genrate ID Card");
    Route::post('/learner/idcard/bulk', [LearnerController::class, 'printBulkIdCard'])->name('learner.idcard.bulk')->middleware("library.can:Genrate ID Card");
   
    Route::post('/learners/restore', [LearnerController::class, 'restore'])->name('learners.restore');
    Route::post('/assign-gift-days', [LearnerController::class, 'giftDaysAssign'])->name('assign.gift.days');
    Route::post('/get-gift-days', [LearnerController::class, 'getGiftDays']) ->name('get.gift.days');
    Route::post('/freeze-unfreeze', [LearnerController::class, 'freezeUnfreeze'])->name('freeze.unfreeze');

    Route::get(
        '/attendance/{learner}',
        [AttendanceController::class, 'summary']
    )->name('attendance.summary');

    Route::get(
        '/attendance/{learner}/logs/{date}',
        [AttendanceController::class, 'logsPage']
    )->name('attendance.logs.page');

    Route::get('demo-users', [DemoUserController::class, 'index'])->name('demo-users.index')->middleware("library.can:Demo Inquiry");
    Route::post('demo-users/store', [DemoUserController::class, 'store'])->name('demo-users.store')->middleware("library.can:Demo Inquiry");
    Route::get('demo-users/create', [DemoUserController::class, 'create'])->name('demo-users.create')->middleware("library.can:Demo Inquiry");


  });
  Route::get('seat/history/list', [LearnerController::class, 'seatHistory'])->name('seats.history');
  Route::get('seats/history/{id?}', [LearnerController::class, 'history'])->name('seats.history.show');
  Route::get('general/seats/history', [LearnerController::class, 'generalSeathistory'])->name('general.seat.history');
  Route::post('change-password', [UserController::class, 'changePassword'])->name('change-password');
  Route::get('change/password', [UserController::class, 'changePasswordView'])->name('change.password');

  //condition base route
  Route::post('learner/renew/', [LearnerController::class, 'learnerRenew'])->name('learners.renew');
  // Logic shared with API: App\Services\SeatAvailabilityService::getSwapSeatStatusCode
  Route::match(['get', 'post'], 'getSeatStatus', [LearnerController::class, 'getSeatStatus'])->name('getSeatStatus');
  Route::get('getPlanType', [LearnerController::class, 'getPlanType'])->name('gettypePlanwise');
  Route::get('getPlanTypeSeatWise', [LearnerController::class, 'getPlanTypeSeatWise'])->name('gettypeSeatwise');
  Route::get('getAvailableSeats', [LearnerController::class, 'getAvailableSeats'])->name('getAvailableSeats');
  Route::get('shift-timing/windows', [LearnerController::class, 'shiftTimingWindows'])->name('shiftTiming.windows');
  Route::get('getPrice', [LearnerController::class, 'getPrice'])->name('getPricePlanwise');
  Route::post('custom-plan/resolve', [LearnerController::class, 'resolveCustomPlan'])->name('customPlan.resolve');
  Route::get('getPrice/master', [MasterController::class, 'getPriceMaster'])->name('getPricePlanwiseMaster');
 

  Route::get('getPricePlanwiseUpgrade', [LearnerController::class, 'getPricePlanwiseUpgrade'])->name('getPricePlanwiseUpgrade');
  Route::post('generateIdCard', [LearnerController::class, 'generateIdCard'])->name('generateIdCard')->middleware("library.can:Genrate ID Card");
  Route::get('idCard/{id}', [LearnerController::class, 'learnerIdCard'])->name('idCard')->middleware("library.can:Genrate ID Card");
  Route::get('/locker-price', function (\Illuminate\Http\Request $req) {
    return response()->json([
      'price' => getLockerPrice($req->query('plan_id'),$req->query('plan_type_id'))
    ]);
  })->name('locker.price');

  Route::get('/locker-no', function (\Illuminate\Http\Request $req) {
    return response()->json([
      'learner' => myLearner($req->query('learner_id'))
    ]);
  })->name('locker.no');
});
 
// Routes for superadmin and admin users
Route::middleware(['portal:admin', 'auth:web'])->group(function () {
  Route::post('library/storedata', [AdminController::class, 'libraryStore'])->name('library.storedata');
  Route::get('library/create', [LibraryController::class, 'create'])->name('library.create');

  Route::post('library/verify/otp', [AdminController::class, 'libraryVerify'])->name('library.verify.otp');
  Route::get('/home', [AdminDashboardController::class, 'index'])->name('home'); // Admin or superadmin home
  Route::get('library/payment/{id}', [AdminController::class, 'libraryPayment'])->name('library.payment');
  Route::get('get/subscription/fees', [AdminController::class, 'getSubscriptionFees'])->name('get.subscription.fees');
  Route::post('admin/library/payment/store', [AdminController::class, 'libraryPaymentStore'])->name('admin.library.payment.store');
  Route::middleware(['role:superadmin'])->group(function () {
    Route::post('library/dashboard/data', [AdminDashboardController::class, 'libraryGetData'])->name('library.dashboard.data.get');
    Route::delete('/activeDeactive/{id}/toggle', [MasterController::class, 'activeDeactive'])->name('activeDeactive');
    Route::get('/csv/web/upload/{id?}', [Controller::class, 'showUploadForm'])->name('configration.upload');

    Route::get('/export-invalid-records/web', [Controller::class, 'exportCsv'])->name('web.export.invalid.records');
    Route::post('/clear-invalid-records/web', [Controller::class, 'clearSession'])->name('web.clear.session');

    Route::get('library', [AdminController::class, 'index'])->name('library');

    Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionManagementController::class, 'subscriptionsIndex'])->name('admin.subscriptions');
    Route::get('subscription/master', [\App\Http\Controllers\SubscriptionManagementController::class, 'subscriptionsIndex'])->name('subscription.master');
    Route::get('/subscriptions/create', [\App\Http\Controllers\SubscriptionManagementController::class, 'subscriptionsCreate'])->name('admin.subscriptions.create');
    Route::post('/subscriptions/store/{id?}', [\App\Http\Controllers\SubscriptionManagementController::class, 'subscriptionsStore'])->name('subscriptions.store');
    Route::get('/subscriptions/edit/{id}', [\App\Http\Controllers\SubscriptionManagementController::class, 'subscriptionsEdit'])->name('subscriptions.edit');
    Route::get('/subscription/master/{id}', [\App\Http\Controllers\SubscriptionManagementController::class, 'subscriptionsEdit'])->name('subscriptions.edit.legacy');
    Route::get('/subscriptions/delete/{id}', [\App\Http\Controllers\SubscriptionManagementController::class, 'subscriptionsDestroy'])->name('subscriptions.destroy');
    Route::get('/subscriptions/toggle-status/{id}', [\App\Http\Controllers\SubscriptionManagementController::class, 'subscriptionsToggleStatus'])->name('subscriptions.toggle-status');
    Route::get('subscriptions-permissions', [MasterController::class, 'index'])->name('subscriptions.permissions');
    Route::get('planwise/permissions/{id}', [MasterController::class, 'showPlanwisePermission'])->name('planwise.permissions');
    // Permission Categories Routes
    Route::get('/permission-categories', [MasterController::class, 'permissionCategoriesIndex'])->name('permission-categories.index');
    Route::get('/permission-categories/create', [MasterController::class, 'permissionCategoryCreate'])->name('permission-categories.create');
    Route::post('/permission-categories/store/{id?}', [MasterController::class, 'storeOrUpdateCategory'])->name('permission-categories.store');
    Route::get('/permission-categories/edit/{id}', [MasterController::class, 'permissionCategoryEdit'])->name('permission-categories.edit');
    Route::delete('/permission-categories/delete/{id}', [MasterController::class, 'deleteCategory'])->name('permission-categories.delete');

    // Permissions Routes (Separate Add/Edit Pages)
    Route::get('/permissions', [MasterController::class, 'managePermissions'])->name('permissions');
    Route::get('/permissions/create', [MasterController::class, 'permissionCreate'])->name('permissions.create');
    Route::post('/permissions/store/{id?}', [MasterController::class, 'storeOrUpdatePermission'])->name('permissions.store');
    Route::get('/permissions/edit/{id}', [MasterController::class, 'permissionEdit'])->name('permissions.edit');
    Route::delete('/permissions/delete/{id}', [MasterController::class, 'deletePermission'])->name('permissions.delete');
    Route::delete('subscriptionPermissions/{permissionId}', [MasterController::class, 'deleteSubscriptionPermission'])->name('subscriptionPermissions.delete');
    Route::get('library/show/{id?}', [LibraryController::class, 'showLibrary'])->name('library.show');
    Route::post('library/{id}/generate-app-verification-code', [AdminController::class, 'generateAppVerificationCode'])->name('library.generateAppVerificationCode');
    Route::post('library/{id}/toggle-app-verification', [AdminController::class, 'toggleAppVerification'])->name('library.toggleAppVerification');
    Route::delete('library/learners/delete/{id?}', [LibraryController::class, 'destroyLearners'])->name('library.learners.destroy');
    Route::delete('library/masters/delete/{id?}', [LibraryController::class, 'destroyAllMasters'])->name('library.masters.destroy');

    Route::get('library/{id}/branches', [LibraryAdminController::class, 'branches'])->name('library.branches');

    Route::get('library/branch/{branchId}/plans', [LibraryAdminController::class, 'branchPlans'])->name('library.branch.plans');
    Route::post('library/branch/{branchId}/plans/save', [LibraryAdminController::class, 'saveBranchPlan'])->name('library.branch.plans.save');
    Route::delete('library/branch/plans/{planId}/delete', [LibraryAdminController::class, 'deleteBranchPlan'])->name('library.branch.plans.delete');

    Route::get('library/branch/{branchId}/plan-types', [LibraryAdminController::class, 'branchPlanTypes'])->name('library.branch.plantypes');
    Route::post('library/branch/{branchId}/plan-types/save', [LibraryAdminController::class, 'saveBranchPlanTypes'])->name('library.branch.plantypes.save');
    Route::delete('library/branch/plan-types/{planTypeId}/delete', [LibraryAdminController::class, 'deleteBranchPlanType'])->name('library.branch.plantypes.delete');

    Route::get('library/branch/{branchId}/prices', [LibraryAdminController::class, 'branchPrices'])->name('library.branch.prices');
    Route::post('library/branch/{branchId}/prices/save', [LibraryAdminController::class, 'saveBranchPrice'])->name('library.branch.prices.save');
    Route::delete('library/branch/prices/{priceId}/delete', [LibraryAdminController::class, 'deleteBranchPrice'])->name('library.branch.prices.delete');

    Route::get('library/branch/{branchId}/seat-hour', [LibraryAdminController::class, 'branchSeatHour'])->name('library.branch.seatHour');
    Route::post('library/branch/{branchId}/seat-hour/save', [LibraryAdminController::class, 'saveBranchSeatHour'])->name('library.branch.seatHour.save');

    Route::get('library/branch/{branchId}/edit', [LibraryAdminController::class, 'editBranch'])->name('library.branch.edit');
    Route::put('library/branch/{branchId}/update', [LibraryAdminController::class, 'updateBranch'])->name('library.branch.update');
    Route::get('notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
    Route::get('create/notification', [NotificationController::class, 'create'])->name('create.notification');
    Route::get('edit/notification/{id?}', [NotificationController::class, 'edit'])->name('notifications.edit');
    Route::post('/notifications/send', [NotificationController::class, 'send'])->name('notifications.send');
    Route::put('/notifications/update', [NotificationController::class, 'update'])->name('notifications.update');
    Route::delete('/notifications/{batchId}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/library/count/view', [AdminDashboardController::class, 'libraryView'])->name('library.count.view');
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

    Route::get('php', [SiteController::class, 'videoIndex'])->name('videos.index');
    Route::post('videos', [SiteController::class, 'videoStore'])->name('videos.store');

    // User Management Module Routes
    Route::get('/users', [\App\Http\Controllers\UserManagementController::class, 'usersIndex'])->name('admin.users');
    Route::get('/users/create', [\App\Http\Controllers\UserManagementController::class, 'usersCreate'])->name('admin.users.create');
    Route::post('/users/store/{id?}', [\App\Http\Controllers\UserManagementController::class, 'usersStore'])->name('admin.users.store');
    Route::get('/users/edit/{id}', [\App\Http\Controllers\UserManagementController::class, 'usersEdit'])->name('admin.users.edit');
    Route::get('/users/delete/{id}', [\App\Http\Controllers\UserManagementController::class, 'usersDestroy'])->name('admin.users.destroy');
    Route::get('/users/toggle-status/{id}', [\App\Http\Controllers\UserManagementController::class, 'usersToggleStatus'])->name('admin.users.toggle-status');

    Route::get('/roles', [\App\Http\Controllers\UserManagementController::class, 'rolesIndex'])->name('admin.roles');
    Route::get('/roles/create', [\App\Http\Controllers\UserManagementController::class, 'rolesCreate'])->name('admin.roles.create');
    Route::post('/roles/store/{id?}', [\App\Http\Controllers\UserManagementController::class, 'rolesStore'])->name('admin.roles.store');
    Route::get('/roles/edit/{id}', [\App\Http\Controllers\UserManagementController::class, 'rolesEdit'])->name('admin.roles.edit');
    Route::get('/roles/delete/{id}', [\App\Http\Controllers\UserManagementController::class, 'rolesDestroy'])->name('admin.roles.destroy');

    Route::get('/admin-permissions', [\App\Http\Controllers\UserManagementController::class, 'permissionsIndex'])->name('admin-permissions');
    Route::get('/admin-permissions/create', [\App\Http\Controllers\UserManagementController::class, 'permissionsCreate'])->name('admin-permissions.create');
    Route::post('/admin-permissions/store/{id?}', [\App\Http\Controllers\UserManagementController::class, 'permissionsStore'])->name('admin-permissions.store');
    Route::get('/admin-permissions/edit/{id}', [\App\Http\Controllers\UserManagementController::class, 'permissionsEdit'])->name('admin-permissions.edit');
    Route::get('/admin-permissions/delete/{id}', [\App\Http\Controllers\UserManagementController::class, 'permissionsDestroy'])->name('admin-permissions.destroy');
    Route::post('/admin-permissions/assign', [\App\Http\Controllers\UserManagementController::class, 'permissionsAssign'])->name('admin-permissions.assign');
  });
});

Route::middleware(['portal:learner', 'auth:learner', 'enforce.guard:learner'])->group(function () {
  Route::get('learner/home', [LearnerDashboardController::class, 'learnerDashboard'])->name('learner.home'); //learner dashboard
  Route::get('learner/profile', [LearnerController::class, 'learnerProfile'])->name('learner.profile');
  Route::get('learner/subscription', [LearnerController::class, 'subscription'])->name('learner.subscription');
  Route::get('learner/request', [LearnerController::class, 'learnerRequest'])->name('learner.request');
  Route::get('learner/IdCard', [LearnerController::class, 'IdCard'])->name('my-library-id');
  Route::get('learner/support', [LearnerController::class, 'support'])->name('support');
  Route::get('learner/blog', [LearnerController::class, 'blog'])->name('learner.blog');
  
  Route::get('learner/blog/detail/show/{slug}', [LearnerController::class, 'blogDetailShow'])->name('blog.detail.show');
  
  Route::get('learner/feadback', [LearnerController::class, 'feadback'])->name('learner.feadback');
  Route::get('learner/suggestions', [LearnerController::class, 'suggestions'])->name('learner.suggestions');
  Route::get('learner/attendance', [LearnerController::class, 'attendance'])->name('my-attendance');
  Route::get('learner/attendance/{date}', [LearnerController::class, 'attendanceDay'])->name('learner.attendance.day');
  Route::get('learner/complaints', [LearnerController::class, 'complaints'])->name('complaints');
  Route::get('learner/transactions', [LearnerController::class, 'transactions'])->name('my-transactions');
  Route::get('learner/books-library', [LearnerController::class, 'booksLibrary'])->name('books-library');
  Route::post('learner/request/store', [LearnerController::class, 'learnerRequestCreate'])->name('requaet.store');
  Route::post('learner/suggestions/store', [LearnerController::class, 'suggestionsStore'])->name('learner.suggestion.store');
  Route::post('learner/complaints/store', [LearnerController::class, 'complaintsStore'])->name('learner.complaint.store');
  Route::post('learner/feadback/store', [LearnerController::class, 'feadbackStore'])->name('learner.feedback.store');

  // End-to-end encrypted chat with the library owner (server relays ciphertext only)
  Route::get('learner/chat', [LearnerChatController::class, 'page'])->name('learner.chat');
  Route::post('learner/chat/key', [LearnerChatController::class, 'registerKey'])->name('learner.chat.key');
  Route::get('learner/chat/messages', [LearnerChatController::class, 'messages'])->name('learner.chat.messages');
  Route::post('learner/chat/send', [LearnerChatController::class, 'send'])->middleware('throttle:30,1')->name('learner.chat.send');
  Route::post('learner/chat/read', [LearnerChatController::class, 'read'])->name('learner.chat.read');
  Route::post('learner/chat/file', [LearnerChatController::class, 'upload'])->middleware('throttle:40,1')->name('learner.chat.upload');
  Route::get('learner/chat/file/{token}', [LearnerChatController::class, 'file'])->name('learner.chat.file');
  Route::get('learner/chat/unread', [LearnerChatController::class, 'unread'])->name('learner.chat.unread');

  // Study corner: exam goal, stopwatch, daily GK quiz, library quizzes
  Route::get('learner/goal', [LearnerStudyController::class, 'goal'])->name('learner.goal');
  Route::post('learner/goal', [LearnerStudyController::class, 'goalSave'])->name('learner.goal.save');
  Route::get('learner/stopwatch', [LearnerStudyController::class, 'stopwatch'])->name('learner.stopwatch');
  Route::post('learner/stopwatch', [LearnerStudyController::class, 'stopwatchSave'])->name('learner.stopwatch.save');
  Route::get('learner/daily-quiz', [LearnerStudyController::class, 'gk'])->name('learner.gk');
  Route::post('learner/daily-quiz', [LearnerStudyController::class, 'gkSubmit'])->name('learner.gk.submit');
  Route::get('learner/quizzes', [LearnerStudyController::class, 'quizzes'])->name('learner.quizzes');
  Route::get('learner/quizzes/result/{attempt}', [LearnerStudyController::class, 'quizResult'])->name('learner.quiz.result');
  Route::get('learner/quizzes/{id}', [LearnerStudyController::class, 'quizPlay'])->name('learner.quiz.play');
  Route::post('learner/quizzes/{id}', [LearnerStudyController::class, 'quizSubmit'])->name('learner.quiz.submit');
});
Route::get('/{slug}', [SiteController::class, 'libraryDetail'])->name('libraryDetail');
// Route::get('updatetest', [LearnerController::class, 'dataTestStatus'])->name('updatetest');

// Limo AI assistant — unanswered questions and the editable knowledge base (platform admin)
Route::middleware(['portal:admin', 'auth:web'])->prefix('administrator/limo')->name('admin.limo.')->group(function () {
  Route::get('questions', [AdminLimoController::class, 'questions'])->name('questions');
  Route::get('knowledge', [AdminLimoController::class, 'knowledge'])->name('knowledge');
  Route::get('knowledge/create', [AdminLimoController::class, 'create'])->name('knowledge.create');
  Route::post('knowledge', [AdminLimoController::class, 'store'])->name('knowledge.store');
  Route::get('knowledge/{id}/edit', [AdminLimoController::class, 'edit'])->whereNumber('id')->name('knowledge.edit');
  Route::put('knowledge/{id}', [AdminLimoController::class, 'update'])->whereNumber('id')->name('knowledge.update');
  Route::post('knowledge/{id}/toggle', [AdminLimoController::class, 'toggle'])->whereNumber('id')->name('knowledge.toggle');
  Route::delete('knowledge/{id}', [AdminLimoController::class, 'destroy'])->whereNumber('id')->name('knowledge.destroy');
});

// Calling CRM — leads, calls with dispositions, follow-ups, WhatsApp / SMS, dashboard (docs/CALLING_CRM.md)
Route::middleware(['portal:admin', 'auth:web'])->prefix('administrator/crm')->name('crm.')->group(function () {
  $can = fn (string $permission) => "can:has-permission,'{$permission}'"; // quoted: a bare word would be read as a route parameter

  Route::get('dashboard', [\App\Http\Controllers\Crm\CrmDashboardController::class, 'index'])->middleware($can('crm-view-dashboard'))->name('dashboard');

  Route::middleware($can('crm-view-leads'))->group(function () use ($can) {
    Route::get('queue', [\App\Http\Controllers\Crm\CrmLeadController::class, 'queue'])->name('queue');
    Route::get('leads', [\App\Http\Controllers\Crm\CrmLeadController::class, 'index'])->name('leads.index');
    Route::get('leads/create', [\App\Http\Controllers\Crm\CrmLeadController::class, 'create'])->middleware($can('crm-create-leads'))->name('leads.create');
    Route::post('leads', [\App\Http\Controllers\Crm\CrmLeadController::class, 'store'])->middleware($can('crm-create-leads'))->name('leads.store');
    Route::get('leads/export', [\App\Http\Controllers\Crm\CrmLeadController::class, 'export'])->middleware($can('crm-export-leads'))->name('leads.export');
    Route::post('leads/assign', [\App\Http\Controllers\Crm\CrmLeadController::class, 'assign'])->middleware($can('crm-assign-leads'))->name('leads.assign');
    Route::post('leads/sync', [\App\Http\Controllers\Crm\CrmLeadController::class, 'sync'])->middleware($can('crm-import-leads'))->name('leads.sync');
    Route::get('leads/{id}', [\App\Http\Controllers\Crm\CrmLeadController::class, 'show'])->whereNumber('id')->name('leads.show');
    Route::get('leads/{id}/edit', [\App\Http\Controllers\Crm\CrmLeadController::class, 'edit'])->whereNumber('id')->middleware($can('crm-edit-leads'))->name('leads.edit');
    Route::put('leads/{id}', [\App\Http\Controllers\Crm\CrmLeadController::class, 'update'])->whereNumber('id')->middleware($can('crm-edit-leads'))->name('leads.update');
    Route::post('leads/{id}/stage', [\App\Http\Controllers\Crm\CrmLeadController::class, 'stage'])->whereNumber('id')->middleware($can('crm-edit-leads'))->name('leads.stage');
    Route::post('leads/{id}/note', [\App\Http\Controllers\Crm\CrmLeadController::class, 'note'])->whereNumber('id')->name('leads.note');
    Route::delete('leads/{id}', [\App\Http\Controllers\Crm\CrmLeadController::class, 'destroy'])->whereNumber('id')->middleware($can('crm-delete-leads'))->name('leads.destroy');

    Route::post('leads/{id}/calls', [\App\Http\Controllers\Crm\CrmCallController::class, 'store'])->whereNumber('id')->middleware($can('crm-log-calls'))->name('calls.store');
    Route::get('calls', [\App\Http\Controllers\Crm\CrmCallController::class, 'index'])->name('calls.index');
    Route::get('leads/{id}/history', [\App\Http\Controllers\Crm\CrmCallController::class, 'history'])->whereNumber('id')->name('calls.history');

    Route::post('leads/{id}/messages', [\App\Http\Controllers\Crm\CrmMessageController::class, 'store'])->whereNumber('id')->middleware($can('crm-send-messages'))->name('messages.store');

    Route::get('follow-ups', [\App\Http\Controllers\Crm\CrmReminderController::class, 'index'])->name('reminders.index');
    Route::get('follow-ups/due', [\App\Http\Controllers\Crm\CrmReminderController::class, 'due'])->name('reminders.due');
    Route::post('leads/{id}/follow-ups', [\App\Http\Controllers\Crm\CrmReminderController::class, 'store'])->whereNumber('id')->middleware($can('crm-manage-reminders'))->name('reminders.store');
    Route::post('follow-ups/{id}/{action}', [\App\Http\Controllers\Crm\CrmReminderController::class, 'update'])->whereNumber('id')->whereIn('action', ['done', 'cancel'])->middleware($can('crm-manage-reminders'))->name('reminders.update');
  });

  Route::middleware($can('crm-import-leads'))->group(function () {
    Route::get('import', [\App\Http\Controllers\Crm\CrmImportController::class, 'create'])->name('import');
    Route::post('import', [\App\Http\Controllers\Crm\CrmImportController::class, 'store'])->name('import.store');
    Route::get('import/template', [\App\Http\Controllers\Crm\CrmImportController::class, 'template'])->name('import.template');
  });

  Route::middleware($can('crm-manage-settings'))->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Crm\CrmSettingsController::class, 'index'])->name('index');
    Route::post('stages', [\App\Http\Controllers\Crm\CrmSettingsController::class, 'storeStage'])->name('stages.store');
    Route::put('stages/{id}', [\App\Http\Controllers\Crm\CrmSettingsController::class, 'updateStage'])->whereNumber('id')->name('stages.update');
    Route::post('dispositions', [\App\Http\Controllers\Crm\CrmSettingsController::class, 'storeDisposition'])->name('dispositions.store');
    Route::put('dispositions/{id}', [\App\Http\Controllers\Crm\CrmSettingsController::class, 'updateDisposition'])->whereNumber('id')->name('dispositions.update');
    Route::post('templates', [\App\Http\Controllers\Crm\CrmSettingsController::class, 'storeTemplate'])->name('templates.store');
    Route::put('templates/{id}', [\App\Http\Controllers\Crm\CrmSettingsController::class, 'updateTemplate'])->whereNumber('id')->name('templates.update');
    Route::delete('templates/{id}', [\App\Http\Controllers\Crm\CrmSettingsController::class, 'destroyTemplate'])->whereNumber('id')->name('templates.destroy');
  });
});

// Design system reference (components in resources/views/components/ui, styles in public/css/ds.css)
Route::middleware(['portal:admin', 'auth:web'])->get('administrator/design-system', fn () => view('admin.design-system'))->name('admin.design-system');
