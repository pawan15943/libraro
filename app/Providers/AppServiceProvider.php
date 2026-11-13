<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Hour;
use App\Models\LearnerDetail;
use App\Models\Plan;
use App\Models\PlanType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\PermissionRegistrar;
use App\Extensions\CustomPermissionRegistrar;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

    public function boot()
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();
        // if (app()->environment(['production'])) {
        //     DB::listen(function ($query) {
        //         \Log::info('SQL', [
        //             'sql'      => $query->sql,
        //             'bindings' => $query->bindings,
        //             'time'     => $query->time
        //         ]);
        //     });
        // }
        foreach (array_keys(Config::get('auth.guards')) as $guard) {
            if (Auth::guard($guard)->check()) {
                Config::set('auth.defaults.guard', $guard);

                break;
            }
        }

        View::composer('*', function ($view) {
            $routeName = Route::currentRouteName();
            // Define breadcrumb and page title logic based on the route
            $breadcrumb = $this->getBreadcrumb($routeName);
            $pageTitle = $this->getPageTitle($routeName);

            $data = compact('breadcrumb', 'pageTitle');

            if (getAuthenticatedUser() && function_exists('getLibraryId')) {
                // This composer fires once per Blade view instance rendered (including every
                // @include'd partial), so without caching this query block re-runs many times
                // per single page load. It only depends on the current request's auth/library
                // state, which doesn't change mid-request, so compute it once and reuse.
                static $cachedAuthData = null;

                if ($cachedAuthData === null) {
                    $authData = [
                        // shift filters list the shifts, not the hidden timing rows of advanced shifts
                        'planTypes' => PlanType::where('library_id', getLibraryId())->listable()->get(),
                        'plans' => Plan::where('library_id', getLibraryId())->listable()->get(),
                    ];
                    $first_record = Hour::first();
                    $authData['totalSeats'] = $first_record ? $first_record->seats : null;
                    $authData['total_hour'] = $first_record ? $first_record->hour : null;

                    if (!$first_record) {
                        $cachedAuthData = false;
                    } else {
                        $totalHour = $first_record->hour;
                        $totalSeats = $first_record->seats;

                        // Step 1: Get used hours for each seat
                        $usedSeats = LearnerDetail::select('seat_no', DB::raw('SUM(hour) as used_hours'))
                            ->whereNotNull('seat_no')
                            ->groupBy('seat_no')->where('status', 1)
                            ->pluck('used_hours', 'seat_no'); // [seat_no => used_hours]

                        $availableSeats = collect();

                        // Step 2: Loop through all seat numbers and apply logic
                        for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
                            $usedHours = $usedSeats[$seatNo] ?? 0;

                            if ($usedHours < $totalHour) {
                                $availableSeats->push($seatNo);
                            }
                        }
                        $authData['exams'] = DB::table('exams')->get();
                        $authData['availableseats'] = $availableSeats;

                        $cachedAuthData = $authData;
                    }
                }

                if ($cachedAuthData === false) return collect();

                $data = array_merge($data, $cachedAuthData);
            }

            $view->with($data);
        });
        View::composer('layouts.library', function ($view) {
            $branches = [];

            if (Auth::guard('library')->check()) {
                $user = Auth::guard('library')->user();
                $branches = $user->branches; // Assuming a 'branches' relationship exists
            } elseif (Auth::guard('library_user')->check()) {
                $user = Auth::guard('library_user')->user();

                // Assuming $user->branch_id is already an array
                $branchIds = $user->branch_id;

                if (is_array($branchIds)) {
                    $branches = Branch::whereIn('id', $branchIds)->get();
                }
            }


            $view->with('branches', $branches);
        });

        // Learner portal: one summary (plan, seat, dues, today's attendance) for the
        // layout and every learner page. Only built when a learner is signed in.
        View::composer(['layouts.learner', 'dashboard.learner', 'learner.*'], function ($view) {
            if (!Auth::guard('learner')->check() || array_key_exists('lr', $view->getData())) {
                return;
            }
            $view->with('lr', app(\App\Services\LearnerPortalService::class)
                ->summary(Auth::guard('learner')->user()));
        });
    }
    public function register()
    {
        $this->app->singleton(PermissionRegistrar::class, CustomPermissionRegistrar::class);
        // one learner summary per request, shared by the layout and the page
        $this->app->scoped(\App\Services\LearnerPortalService::class);
    }
    private function getBreadcrumb($routeName, $parameters = [])
    {
        // Ensure $parameters is always an array
        $parameters = is_array($parameters) ? $parameters : [];

        $breadcrumbs = [
            // Administrator Links
            'home' => ['Dashboard' => route('home')],

            // Library Links
            'library.home' => ['Dashboard' => route('library.home')],
            'list.notification' => [
                'Dashboard' => route('library.home'),
                'Notifications List' => route('list.notification')
            ],
            'activities.all' => [
                'Dashboard' => route('library.home'),
                'Activities Logs' => route('activities.all')
            ],
            'profile' => [
                'Dashboard' => route('library.home'),
                'Library Profile' => route('profile')
            ],
            'library.feedback' => [
                'Dashboard' => route('library.home'),
                'Feedback' => route('library.feedback')
            ],
            'library.video-training' => [
                'Dashboard' => route('library.home'),
                'Video Tutorials' => route('library.video-training')
            ],
            'library.settings' => [
                'Dashboard' => route('library.home'),
                'Library Settings' => route('library.settings')
            ],


            'get.learner.attendance' => [
                'Dashboard' => route('library.home'),
                'Attendance Summary' => route('get.learner.attendance')
            ],
            'subscriptions.choosePlan' => [
                'Dashboard' => route('library.home'),
                'Choose Plan' => route('subscriptions.choosePlan')
            ],
            'subscriptions.payment' => [
                'Dashboard' => route('library.home'),
                'Make Payment' => route('subscriptions.payment')
            ],
            'library.master' => [
                'Dashboard' => route('library.home'),
                'Configure Library' => route('library.master')
            ],
            'seats' => [
                'Dashboard' => route('library.home'),
                'Seat Map' => route('seats')
            ],
            'learners' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners')
            ],
            'learners.show' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Booking Info' => route('learners.show', $parameters)
            ],
            'learner.pending.payment' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Make Payment' => route('learner.pending.payment', $parameters)
            ],
            'learner.pending.payment.list' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Pending Payment' => route('learner.pending.payment.list')
            ],
            'learners.edit' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Edit Seat Booking Info' => route('learners.edit', $parameters)
            ],
            'learners.edit.plan' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Edit Plan' => route('learners.edit.plan', $parameters)
            ],
            'learners.transactions' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'All Transactions' => route('learners.transactions', ['learner' => request()->route('learner') ?? false])
            ],
            'learners.transactions.edit' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Edit Transaction' => route('learners.transactions.edit', request()->route('transaction') ?? 0)
            ],
            'learners.swap' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Swap Seat' => route('learners.swap', $parameters)
            ],
            'learner.change.plan' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Change Plan' => route('learner.change.plan', $parameters)
            ],
            'learners.upgrade' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Upgrade Seat' => route('learners.upgrade', $parameters)
            ],
            'learners.booking.done' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Booking confirmed' => route('learners.booking.done', ['detailId' => request()->route('detailId') ?? 0]),
            ],
            'learners.settlement.page' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Settlement' => route('learners.settlement.page', [
                    'learnerId' => request()->route('learnerId') ?? 0,
                    'detailId' => request()->route('detailId')
                ])
            ],
            'attendance.summary' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Attendance' => route('attendance.summary', request()->route('learner') ?? 0)
            ],
            'attendance.logs.page' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Attendance' => route('attendance.summary', request()->route('learner') ?? 0),
                'Attendance Logs' => route('attendance.logs.page', [request()->route('learner') ?? 0, request()->route('date') ?? '1970-01-01'])
            ],
            'seats.history' => [
                'Dashboard' => route('library.home'),
                'Seat Booking History' => route('seats.history')
            ],
            'seats.history.show' => [
                'Dashboard' => route('library.home'),
                'Seat Booking History' => route('seats.history'),
                'Detailed History' => route('seats.history.show', $parameters)
            ],
            'learners.reactive' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Reactive Learner' => route('learners.reactive', $parameters)
            ],
            'library.myplan' => [
                'Dashboard' => route('library.home'),
                'My Plan' => route('library.myplan')
            ],
            'library.transaction' => [
                'Dashboard' => route('library.home'),
                'My Payment Transactions' => route('library.transaction')
            ],
            'library.transaction.view' => [
                'Dashboard' => route('library.home'),
                'Transaction Activity' => route('library.transaction.view', request()->query())
            ],
            'report.monthly' => [
                'Dashboard' => route('library.home'),
                'Monthly Revenue Report' => route('report.monthly')
            ],
            'pending.payment.report' => [
                'Dashboard' => route('library.home'),
                'Payment Pending Report' => route('pending.payment.report')
            ],
            'learner.report' => [
                'Dashboard' => route('library.home'),
                'All Learners Report' => route('learner.report')
            ],
            'upcoming.payment.report' => [
                'Dashboard' => route('library.home'),
                'Upcoming Payment Report' => route('upcoming.payment.report')
            ],
            'expired.learner.report' => [
                'Dashboard' => route('library.home'),
                'Expired Learners Report' => route('expired.learner.report')
            ],
            'library.settings' => [
                'Dashboard' => route('library.home'),
                'Library Setting' => route('library.settings')
            ],
            'learnerHistory' => [
                'Dashboard' => route('library.home'),
                'Learner History' => route('learnerHistory'),
            ],
            'learner.payment' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Make Payment' => route('learner.payment', $parameters),
            ],
            'learners.list.view' => [
                'Dashboard' => route('library.home'),
                'Library Counts Details' => route('learners.list.view'),
            ],
            'learner.expire' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Expired The Learner' => route('learner.expire'),
            ],
            'library.feedback' => [
                'Dashboard' => route('library.home'),
                'Library Feedback' => route('library.feedback'),
            ],
            'library.video-training' => [
                'Dashboard' => route('library.home'),
                'Video Tutorials' => route('library.video-training'),
            ],
            'library.upload.form' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Import Data' => route('library.upload.form'),
            ],
            'attendance' => [
                'Dashboard' => route('library.home'),
                'Manual Attendance' => route('attendance'),
            ],

            'report.expense' => [
                'Dashboard' => route('library.home'),
                'Monthly Revenue Reports' => route('report.monthly'),
                'Manage Expense' => route('report.expense', [
                    'year' => request()->year ?? now()->year, // Default to current year if not found
                    'month' => request()->month ?? now()->month, // Default to current month if not found
                ]),
            ],

            'plan.index' => [
                'Dashboard' => route('library.home'),
                'Plan' => route('plan.index'),
            ],
            'floor.index' => [
                'Dashboard' => route('library.home'),
                'Floors' => route('floor.index'),
            ],
            'learner.checklist' => [
                'Dashboard' => route('library.home'),
                'Print ID card in bulk' => route('learner.checklist'),
            ],
            'plan.create' => [
                'Dashboard' => route('library.home'),
                'Plan List' => route('plan.index'),
                (request()->route('id') ? 'Edit Plan' : 'Add Plan') => route('plan.create', $parameters),
            ],
            'branch.list' => [
                'Dashboard' => route('library.home'),
                'Branches' => route('branch.list', $parameters),
            ],
            'branch.edit' => [
                'Dashboard' => route('library.home'),
                'Branches' => route('branch.list'),
                'Edit Branch' => url()->current(), // route needs an id; breadcrumbs are built for every page
            ],
            'learner.other.payment' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Other Library Payment' => route('learner.other.payment', $parameters),
            ],

            'planType.create' => [
                'Dashboard' => route('library.home'),
                'Plantype List' => route('plantype.index'),
                'Add Plan Type' => route('planType.create', $parameters),
            ],
            'planPrice.create' => [
                'Dashboard' => route('library.home'),
                'Plantype Price List' => route('planPrice.index'),
                'Add Plantype Price' => route('planPrice.create', $parameters),
            ],

            'add.expense.list' => [
                'Dashboard' => route('library.home'),
                'Daily Expenses' => route('add.expense.list'),
            ],
            'expense.create' => [
                'Dashboard' => route('library.home'),
                'Expense List' => route('expense.index'),
                'Add Expense' => route('expense.create', $parameters),
            ],
            'exam.create' => [
                'Dashboard' => route('library.home'),
                'Exams List' => route('exam.index'),
                'Add Exam' => route('exam.create', $parameters),
            ],
            'branch.create' => [
                'Dashboard' => route('library.home'),
                'Branch List' => route('branch.list', $parameters),
                'Add Branch' => route('branch.create', $parameters),
            ],
            'seat.create' => [
                'Dashboard' => route('library.home'),
                'Branches' => route('branch.list'),
                'Total Seats' => route('seat.create', $parameters),
            ],
            'hour.create' => [
                'Dashboard' => route('library.home'),
                'Branches' => route('branch.list'),
                'Operating Hours' => route('hour.create', $parameters),
            ],
            'extendDay.create' => [
                'Dashboard' => route('library.home'),
                'Branches' => route('branch.list'),
                'Extend Days' => route('extendDay.create', $parameters),
            ],
            'lockeramount.create' => [
                'Dashboard' => route('library.home'),
                'Branches' => route('branch.list'),
                'Locker Amount' => route('lockeramount.create', $parameters),
            ],
            'tokenAmount.create' => [
                'Dashboard' => route('library.home'),
                'Branches' => route('branch.list'),
                'Token Money' => route('tokenAmount.create', $parameters),
            ],

            'plantype.index' => [
                'Dashboard' => route('library.home'),
                'Plan Type' => route('plantype.index'),
            ],
            'planPrice.index' => [
                'Dashboard' => route('library.home'),
                'Plan Price' => route('planPrice.index'),
            ],
            'expense.index' => [
                'Dashboard' => route('library.home'),
                'Expense Names' => route('expense.index'),
            ],
            'exam.index' => [
                'Dashboard' => route('library.home'),
                'Exam Names' => route('exam.index'),
            ],
            'chat.index' => [
                'Dashboard' => route('library.home'),
                'Student Chat' => route('chat.index'),
            ],
            'chat.thread' => [
                'Dashboard' => route('library.home'),
                'Student Chat' => route('chat.index'),
                'Conversation' => '#',
            ],
            'quiz.index' => [
                'Dashboard' => route('library.home'),
                'Student Quiz' => route('quiz.index'),
            ],
            'quiz.create' => [
                'Dashboard' => route('library.home'),
                'Student Quiz' => route('quiz.index'),
                'New quiz' => route('quiz.create'),
            ],
            'quiz.edit' => [
                'Dashboard' => route('library.home'),
                'Student Quiz' => route('quiz.index'),
                'Edit quiz' => '#',
            ],
            'learner.search' => [
                'Dashboard' => route('library.home'),
                'Find a Learner' => route('learner.search'),
            ],
            'create.renew.delete.index' => [
                'Dashboard' => route('library.home'),
                'Renew Delete' => route('create.renew.delete.index'),
            ],
            'toggle.feature' => [
                'Dashboard' => route('library.home'),
                'Show / Hide Options' => route('toggle.feature'),
            ],
            'notifications.subscription' => [
                'Dashboard' => route('library.home'),
                'Buy Message Subscription' => route('notifications.subscription'),
            ],
            'notifications.settings' => [
                'Dashboard' => route('library.home'),
                'Notification Console' => route('notifications.settings'),
            ],
            'notification.dashboard' => [
                'Dashboard' => route('library.home'),
                'Notification Dashboard' => route('notification.dashboard'),
            ],
            'message.templates' => [
                'Dashboard' => route('library.home'),
                'Notification Console' => route('notifications.settings'),
                'Message Templates' => route('message.templates'),
            ],
            'general.seat.history' => [
                'Dashboard' => route('library.home'),
                'Seat Booking History' => route('seats.history'),
                'Expired Learner History' => route('seats.history.show'),
            ],
            'library.how-to-use' => [
                'Dashboard' => route('library.home'),
                'How to Use Libraro' => route('library.how-to-use'),
            ],
            'booking.details.approve' => [
                'Dashboard' => route('library.home'),
                'QR / Online Bookings' => route('booking.details.approve'),
            ],
            'attendance.apply' => [
                'Dashboard' => route('library.home'),
                'Attendance via QR / Scanner' => route('attendance.apply'),
            ],
            'branch.configure.create' => [
                'Dashboard' => route('library.home'),
                'Setup Branch & Floors' => route('branch.configure.create'),
            ],
            'library.configration' => [
                'Dashboard' => route('library.home'),
                'Shifts & fees' => route('library.configration'),
            ],

             'library-users.create' => [
                'Dashboard' => route('library.home'),
                'Users' => route('library-users.index'),
                (request()->route('id') ? 'Edit User' : 'Add User') => route('library-users.create', $parameters),
            ],
            'library-users.index' => [
                'Dashboard' => route('library.home'),
                'Users' => route('library-users.index'),
            ],
            'library-users.permissions' => [
                'Dashboard' => route('library.home'),
                'Users' => route('library-users.index'),
                'Permissions' => url()->current(), // route needs an id; breadcrumbs are built for every page
            ],
            'booking.details' => [
                'Dashboard' => route('library.home'),
                'Verify and Allot Seat' => route('booking.details', [
                    'id' => is_array($parameters)
                        ? ($parameters['id'] ?? reset($parameters))
                        : $parameters
                ]),
            ],
             'demo-users.index' => [
                'Dashboard' => route('library.home'),
                'Demo Inquiry' => route('demo-users.index'),
            ],
            'activities.all' => [
                'Dashboard' => route('library.home'),
                'All Activities' => route('activities.all'),
            ],
             'demo-users.create' => [
                'Dashboard' => route('library.home'),
                'Demo Inquiry' => route('demo-users.index'),
                'Add Demo inquiry' => route('demo-users.create'),
            ],
            'change.password' => [
                'Dashboard' => route('library.home'),
                'Change Password' => '#',
            ],

            // Learner Bread crumb

            // Administrator Links
            'learner.home' => ['Dashboard' => route('home')],
            'learner.request' => [
                'Dashboard' => route('learner.home'),
                'Learner Request' => route('learner.request'),
            ],
            'learner.profile' => [
                'Dashboard' => route('learner.home'),
                'Learner profile' => route('learner.profile'),
            ],
            'my-library-id' => [
                'Dashboard' => route('learner.home'),
                'My Library ID' => route('my-library-id'),
            ],
            'my-attendance' => [
                'Dashboard' => route('learner.home'),
                'My Library Attendance' => route('my-attendance'),
            ],
            'my-transactions' => [
                'Dashboard' => route('learner.home'),
                'My Transactions' => route('my-transactions'),
            ],
            'complaints' => [
                'Dashboard' => route('learner.home'),
                'Complaints' => route('complaints'),
            ],
            'learner.suggestions' => [
                'Dashboard' => route('learner.home'),
                'Suggestions' => route('learner.suggestions'),
            ],
            'learner.blog' => [
                'Dashboard' => route('learner.home'),
                'Blog' => route('learner.blog'),
            ],
            'books-library' => [
                'Dashboard' => route('learner.home'),
                'Library Books' => route('books-library'),
            ],
            'learner.feadback' => [
                'Dashboard' => route('learner.home'),
                'Feedback' => route('learner.feadback'),
            ],
            // Blog Module Links
            'blogs' => [
                'Dashboard' => route('home'),
                'Blog Posts Management' => route('blogs'),
            ],
            'add-blog' => [
                'Dashboard' => route('home'),
                'Blog Posts Management' => route('blogs'),
                'Create New Blog Post' => route('add-blog'),
            ],
            'blog.edit' => [
                'Dashboard' => route('home'),
                'Blog Posts Management' => route('blogs'),
                'Edit Blog Post' => route('blog.edit', request()->route('id') ?? 0),
            ],
            'blog' => [
                'Home' => route('/'),
                'Blog' => route('blog'),
            ],
            'blog-detail' => [
                'Home' => route('/'),
                'Blog' => route('blog'),
                'Post Detail' => route('blog-detail', request()->route('slug') ?? ''),
            ],
            'admin.users' => [
                'Home' => route('home'),
                'User Management' => route('admin.users'),
                'Users List' => route('admin.users'),
            ],
            'admin.users.create' => [
                'Home' => route('home'),
                'User Management' => route('admin.users'),
                'Add User' => route('admin.users.create'),
            ],
            'admin.users.edit' => [
                'Home' => route('home'),
                'User Management' => route('admin.users'),
                'Edit User' => '#',
            ],
            'admin.roles' => [
                'Home' => route('home'),
                'User Management' => route('admin.users'),
                'Roles' => route('admin.roles'),
            ],
            'admin.roles.create' => [
                'Home' => route('home'),
                'User Management' => route('admin.users'),
                'Add Role' => route('admin.roles.create'),
            ],
            'admin.roles.edit' => [
                'Home' => route('home'),
                'User Management' => route('admin.users'),
                'Edit Role' => '#',
            ],
            'admin-permissions' => [
                'Home' => route('home'),
                'User Management' => route('admin.users'),
                'Admin Permissions' => route('admin-permissions'),
            ],
            'admin-permissions.create' => [
                'Home' => route('home'),
                'User Management' => route('admin.users'),
                'Add Admin Permission' => route('admin-permissions.create'),
            ],
            'admin-permissions.edit' => [
                'Home' => route('home'),
                'User Management' => route('admin.users'),
                'Edit Admin Permission' => '#',
            ],
            'admin.subscriptions' => [
                'Home' => route('home'),
                'Manage Subscriptions' => route('admin.subscriptions'),
                'Subscriptions List' => route('admin.subscriptions'),
            ],
            'subscription.master' => [
                'Home' => route('home'),
                'Manage Subscriptions' => route('admin.subscriptions'),
                'Subscriptions List' => route('admin.subscriptions'),
            ],
            'admin.subscriptions.create' => [
                'Home' => route('home'),
                'Manage Subscriptions' => route('admin.subscriptions'),
                'Add Subscription' => route('admin.subscriptions.create'),
            ],
            'subscriptions.edit' => [
                'Home' => route('home'),
                'Manage Subscriptions' => route('admin.subscriptions'),
                'Edit Subscription' => '#',
            ],
            'permission-categories.index' => [
                'Home' => route('home'),
                'Manage Permissions' => route('permissions'),
                'Categories List' => route('permission-categories.index'),
            ],
            'permission-categories.create' => [
                'Home' => route('home'),
                'Manage Permissions' => route('permissions'),
                'Add Category' => route('permission-categories.create'),
            ],
            'permission-categories.edit' => [
                'Home' => route('home'),
                'Manage Permissions' => route('permissions'),
                'Edit Category' => '#',
            ],
            'permissions' => [
                'Home' => route('home'),
                'Permissions List' => route('permissions'),
            ],
            'permissions.create' => [
                'Home' => route('home'),
                'Permissions List' => route('permissions'),
                'Add Permission' => route('permissions.create'),
            ],
            'permissions.edit' => [
                'Home' => route('home'),
                'Permissions List' => route('permissions'),
                'Edit Permission' => '#',
            ],
            'admin.notifications.index' => [
                'Home' => route('home'),
                'Notification' => route('admin.notifications.index'),
            ],
            'create.notification' => [
                'Home' => route('home'),
                'Notification' => route('admin.notifications.index'),
                'Add Notification' => route('create.notification'),
            ],
            'notifications.edit' => [
                'Home' => route('home'),
                'Notification' => route('admin.notifications.index'),
                'Edit Notification' => '#',
            ],
        ];

        // pages that had no trail yet (they used to show their own crumb)
        $dash = ['Dashboard' => route('library.home')];
        $reports = $dash + ['Reports' => route('reports.hub')];
        $staff = $dash + ['Users' => route('library-users.index')];
        $breadcrumbs += [
            'library-users.permission-list' => $staff + ['Permission' => '#'],
            'library-users.roles' => $staff + ['Role' => '#'],
            'reports.hub' => $dash + ['Reports' => '#'],
            'reports.growth' => $reports + ['Growth & retention' => '#'],
            'reports.occupancy' => $reports + ['Seat occupancy' => '#'],
            'attendance.report' => $reports + ['Attendance' => '#'],
            'payment.collection.report' => $reports + ['Collections' => '#'],
            'monthly.payment.collection.report' => $reports + ['Cash book' => '#'],
            'partial.payment.collection.report' => $reports + ['Dues to collect' => '#'],
            'library.enquiry' => $dash + ['Website enquiries' => '#'],
            'global-discount.index' => $dash + ['Global Discount' => '#'],
            'referral.dashboard' => $dash + ['Refer & earn' => '#'],
            'daily_dashboard' => $dash + ['Daily Dashboard' => '#'],
            'library.learner.complaints' => $dash + ['Learner Complaints' => '#'],
            'library.learner.feedback' => $dash + ['Learner Feedback' => '#'],
            'library.learner.suggestions' => $dash + ['Learner Suggestions' => '#'],
            'idCard' => $dash + ['Learners List' => route('learners'), 'ID Card' => '#'],
            'learner.idcard.bulk' => $dash + ['Print ID card in bulk' => route('learner.checklist'), 'ID cards' => '#'],
        ];

        $breadcrumbs['learner.renew.plan'] = [
            'Dashboard' => route('library.home'),
            'Learners List' => route('learners'),
            'Renew Plan' => '#',
        ];

        return $this->withLearnerCrumb($routeName, $breadcrumbs[$routeName] ?? []);
    }

    /**
     * Operation pages on one learner (upgrade, swap, settlement, …): put the learner's name,
     * linked to their profile, before the operation, e.g. Dashboard › Learners List › Ravi › Swap Seat.
     */
    private function withLearnerCrumb($routeName, array $trail): array
    {
        $byLearner = ['learners.edit.plan', 'learners.edit', 'learners.swap', 'learner.change.plan', 'learners.reactive',
            'learner.payment', 'learners.upgrade', 'learner.renew.plan', 'learner.other.payment', 'learner.pending.payment'];
        $ops = array_merge($byLearner, ['learners.settlement.page', 'learners.transactions', 'learners.transactions.edit', 'learners.booking.done', 'idCard']);

        if (count($trail) < 2 || ! in_array($routeName, $ops, true)) {
            return $trail;
        }

        static $cache = [];
        if (! array_key_exists($routeName, $cache)) {
            $cache[$routeName] = null;
            try {
                $route = request()->route();
                $learnerId = null;
                if (in_array($routeName, $byLearner, true)) {
                    $learnerId = $route?->parameter('id');
                } elseif ($routeName === 'learners.settlement.page') {
                    $learnerId = $route?->parameter('learnerId');
                } elseif ($routeName === 'learners.transactions') {
                    $p = $route?->parameter('learner');
                    $learnerId = is_object($p) ? $p->id : $p;
                } elseif ($routeName === 'learners.transactions.edit') {
                    $p = $route?->parameter('transaction');
                    $learnerId = is_object($p) ? $p->learner_id : \App\Models\LearnerTransaction::withoutGlobalScopes()->whereKey($p)->value('learner_id');
                } elseif ($routeName === 'learners.booking.done') {
                    $learnerId = \App\Models\LearnerDetail::withoutGlobalScopes()->whereKey($route?->parameter('detailId'))->value('learner_id');
                } elseif ($routeName === 'idCard') {
                    $learnerId = \App\Models\LearnerDetail::withoutGlobalScopes()->whereKey($route?->parameter('id'))->value('learner_id');
                }
                if ($learnerId && is_numeric($learnerId)) {
                    $learner = \App\Models\Learner::withTrashed()->where('library_id', getLibraryId())->find($learnerId, ['id', 'name']);
                    if ($learner) {
                        $cache[$routeName] = [$learner->name ?: 'Learner', route('learners.show', $learner->id)];
                    }
                }
            } catch (\Throwable $e) {
                $cache[$routeName] = null;
            }
        }

        if (! $cache[$routeName] || isset($trail[$cache[$routeName][0]])) {
            return $trail;
        }

        // learner just before the last item (the operation itself)
        $last = array_slice($trail, -1, 1, true);
        $head = array_slice($trail, 0, -1, true);
        $head[$cache[$routeName][0]] = $cache[$routeName][1];

        return $head + $last;
    }


    private function getPageTitle($routeName, $parameters = [])
    {
        // Ensure $parameters is always an array (not used here but for consistency)
        $parameters = is_array($parameters) ? $parameters : [];

        // Simple logic to convert route name to page title
        $titles = [
            // pages that had no title yet (they used to show their own)
            'library-users.permission-list' => 'Permission',
            'library-users.roles' => 'Role',
            'reports.hub' => 'Reports',
            'reports.growth' => 'Growth & retention',
            'reports.occupancy' => 'Seat occupancy',
            'attendance.report' => 'Attendance Report',
            'payment.collection.report' => 'Collections',
            'monthly.payment.collection.report' => 'Cash book',
            'partial.payment.collection.report' => 'Dues to collect',
            'library.enquiry' => 'Website enquiries',
            'global-discount.index' => 'Global Discount',
            'referral.dashboard' => 'Refer & earn',
            'daily_dashboard' => 'Daily Dashboard',
            'library.learner.complaints' => 'Learner Complaints',
            'library.learner.feedback' => 'Learner Feedback',
            'library.learner.suggestions' => 'Learner Suggestions',
            'learner.renew.plan' => 'Renew Plan',
            'idCard' => 'ID Card',

            // Administrator Portal
            'home' => 'Dashboard',

            // Library Portal
            'library.home' => 'Library Dashboard',
            'get.learner.attendance' => 'Attendance Summary',
            'profile' => 'Library Profile',
            'subscriptions.choosePlan' => 'Choose Plan',
            'subscriptions.payment' => 'Make Payment',
            'learner.pending.payment' => 'Make Payment',
            'seats' => 'Seat Map',
            'learners' => 'Learners List',
            'learners.show' => 'Booking Info',
            'learners.edit' => 'Edit Seat Booking Info',
            'learners.edit.plan' => 'Edit Plan',
            'learners.swap' => 'Swap Seat',
            'learners.upgrade' => 'Upgrade Seat',
            'learners.settlement.page' => 'Settlement',
            'learners.booking.done' => 'Booking confirmed',
            'attendance.summary' => 'Attendance',
            'attendance.logs.page' => 'Attendance Logs',
            'seats.history' => 'Seat Booking History',
            'seats.history.show' => 'Detailed Seat History',
            'library.myplan' => 'My Plan',
            'library.transaction' => 'My Payment Transactions',
            'library.transaction.view' => 'Transaction Activity',
            'report.monthly' => 'Monthly Revenue Report',
            'pending.payment.report' => 'Payment Pending Report',
            'learner.report' => 'All Learners Report',
            'upcoming.payment.report' => 'Upcoming Payment Report',
            'expired.learner.report' => 'Expired Learners Report',
            'list.notification' => 'Notifications List',
            'notifications.settings' => 'Notification Console',
            'message.templates' => 'Message Templates',
            'activities.all' => 'Activities Logs',
            'library.master' => 'Configure Library',
            'learners.reactive' => 'Reactive Learner',
            'learnerHistory' => 'Learner History',
            'learner.pending.payment' => 'Make Payment',
            'learner.pending.payment.list' => 'Pending Payment',
            'learners.list.view' => 'Library Counts Details',
            'library.settings' => 'Library Settings',
            'library.upload.form' => 'Import Data',
            'report.expense' => 'Manage Expanse',
            'library.feedback' => 'Library Feedback',
            'library.video-training' => 'Video Tutorials',
            'learner.expire' => 'Expired The Learner',
            'attendance' => 'Manual Attendance',
            'plan.index' => 'Plan (Duration)',
            'floor.index' => 'Floors',
            'plan.create' => request()->route('id') ? 'Edit Plan' : 'Add Plan',

            // leaner
            'learner.home' => 'Learner Dashboard',
            'learner.request' => 'Learner Request',
            'learner.profile' => 'Learner Profile',
            'my-library-id' => 'My Library ID',
            'my-attendance' => 'My Library Attendance',
            'my-transactions' => 'My Transactions',
            'complaints' => 'Complaints',
            'learner.suggestions' => 'Suggestions',
            'learner.blog' => 'Blog',
            'books-library' => 'Library Books',
            'learner.feadback' => 'Feedback',
            'support' => 'Support',
            'seat.create' => 'Total Seats',
            'learner.other.payment' => 'Library Other Payment',

            'branch.list' => 'Branches',
            'branch.edit' => 'Edit Branch',
            'plantype.index' => 'Plan Type (Shifts)',
            'planPrice.index' => 'Plan Price (Shift Price)',
            'add.expense.list' => 'Daily Expense Records',
            'expense.index' => 'Expense Names (Show in Expense list)',
            'exam.index' => 'Exam Names (Show in Booking Form)',
            'quiz.index' => 'Student Quiz',
            'chat.index' => 'Student Chat',
            'chat.thread' => 'Student Chat',
            'quiz.create' => 'New Quiz',
            'quiz.edit' => 'Edit Quiz',
            'learner.search' => 'Find a Learner',
            'create.renew.delete.index' => 'Renew Delete',
            'create.renew.delete.transaction' => 'Renew transaction',
            'hour.create' => 'Operating Hours',
            'extendDay.create' => 'Extend Days',
            'lockeramount.create' => 'Locker Amount',
            'tokenAmount.create' => 'Token Money',
            'library-users.create' => request()->route('id') ? 'Edit User' : 'Add User',
            'library-users.index' => 'Users',
            'library-users.permissions' => 'User Permissions',
            'learner.checklist' => 'Print ID card in bulk',
            'learner.idcard.bulk' => 'Print ID cards',
            'toggle.feature' => 'Show / Hide Options',
            'notifications.subscription' =>  'Buy Message Subscription',
            'notifications.settings' =>  'Notification Console',
            'notification.dashboard' =>  'Notification Dashbaord',
            'learner.change.plan' => 'Learner Change Plan',
            'general.seat.history' => 'General Seat History',
            'library.how-to-use' => 'How to Use Library',
            'booking.details.approve' => 'QR / Online Bookings',
            'attendance.apply' => 'Attendance via QR / Scanner',
            'branch.configure.create' => 'Setup Branch & Floors',
            'library.configration' => 'Shifts & fees',
            'booking.details' => 'Verify and Allot Seat',
            'demo-users.index' => 'Demo Inquiry',
            'demo-users.create' => 'Add Demo Inquiry',
            'activities.all' => 'All Activities',
            'learners.transactions' => 'All Transactions',
            'learners.transactions.edit' => 'Edit Transaction',
            'blogs' => 'Blog Posts Management',
            'add-blog' => 'Create New Blog Post',
            'blog.edit' => 'Edit Blog Post',
            'blog' => 'Blog & Articles',
            'blog-detail' => 'Blog Details',
            'change.password' => 'Change Password',
            'admin.notifications.index' => 'Notification List',
            'create.notification' => 'Add Notification',
            'notifications.edit' => 'Edit Notification',

        ];

        return $titles[$routeName] ?? ucfirst(str_replace('.', ' ', $routeName));
    }
}
