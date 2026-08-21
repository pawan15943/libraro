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
                        'planTypes' => PlanType::where('library_id', getLibraryId())->get(),
                        'plans' => Plan::where('library_id', getLibraryId())->get(),
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
    }
    public function register()
    {
        $this->app->singleton(PermissionRegistrar::class, CustomPermissionRegistrar::class);
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
                'Dashboard' => route('home'),
                'Library Profile' => route('profile')
            ],


            'get.learner.attendance' => [
                'Dashboard' => route('home'),
                'Daily Attendance Summary' => route('get.learner.attendance')
            ],
            'subscriptions.choosePlan' => [
                'Dashboard' => route('home'),
                'Choose Plan' => route('subscriptions.choosePlan')
            ],
            'subscriptions.payment' => [
                'Dashboard' => route('home'),
                'Make Payment' => route('subscriptions.payment')
            ],
            'library.master' => [
                'Dashboard' => route('home'),
                'Configure Library' => route('library.master')
            ],
            'seats' => [
                'Dashboard' => route('library.home'),
                'Seat Assignment' => route('seats')
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
                'Import Learners' => route('library.upload.form'),
            ],
            'attendance' => [
                'Dashboard' => route('library.home'),
                'Add Learner Attendace' => route('attendance'),
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
                'Plan List' => route('plan.index'),
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
                'Branch List' => route('branch.list', $parameters),
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
                'Branch List' => route('branch.list', $parameters),
                'Add Seat to Library' => route('seat.create', $parameters),
            ],
            'hour.create' => [
                'Dashboard' => route('library.home'),
                'Branch List' => route('branch.list', $parameters),
                'Add Library Operating Hours' => route('hour.create', $parameters),
            ],
            'extendDay.create' => [
                'Dashboard' => route('library.home'),
                'Branch List' => route('branch.list', $parameters),
                'Add Library Extend Period' => route('extendDay.create', $parameters),
            ],
            'lockeramount.create' => [
                'Dashboard' => route('library.home'),
                'Branch List' => route('branch.list', $parameters),
                'Add Library Locker Amount' => route('lockeramount.create', $parameters),
            ],

            'plantype.index' => [
                'Dashboard' => route('library.home'),
                'Plantype List ' => route('plantype.index'),
            ],
            'planPrice.index' => [
                'Dashboard' => route('library.home'),
                'Plantype Price List ' => route('planPrice.index'),
            ],
            'expense.index' => [
                'Dashboard' => route('library.home'),
                'Expense List ' => route('expense.index'),
            ],
            'exam.index' => [
                'Dashboard' => route('library.home'),
                'Exams List ' => route('exam.index'),
            ],
            'learner.search' => [
                'Dashboard' => route('library.home'),
                'Find a Learner' => route('learner.search'),
            ],
            'create.renew.delete.index' => [
                'Dashboard' => route('library.home'),
                'Renew Delete' => route('create.renew.delete.index'),
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
                'QR Attendance' => route('attendance.apply'),
            ],
            'branch.configure.create' => [
                'Dashboard' => route('library.home'),
                'Setup Branch & Floors' => route('branch.configure.create'),
            ],
            'library.configration' => [
                'Dashboard' => route('library.home'),
                'Add Shifts' => route('library.configration'),
            ],

             'library-users.create' => [
                'Dashboard' => route('library.home'),
                'Users List' => route('library-users.index', $parameters),
                'Create Library User' => route('library-users.create'),
            ],
            'library-users.index' => [
                'Dashboard' => route('library.home'),
                'Library Users List' => route('library-users.index'),
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
                'Daily Demo inquiries' => route('demo-users.index'),
            ],
            'activities.all' => [
                'Dashboard' => route('library.home'),
                'All Activities' => route('activities.all'),
            ],
             'demo-users.create' => [
                'Dashboard' => route('library.home'),
                'Daily Demo inquiries' => route('demo-users.index'),
                'Add Demo inquiry' => route('demo-users.create'),
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
        ];

        return $breadcrumbs[$routeName] ?? [];
    }


    private function getPageTitle($routeName, $parameters = [])
    {
        // Ensure $parameters is always an array (not used here but for consistency)
        $parameters = is_array($parameters) ? $parameters : [];

        // Simple logic to convert route name to page title
        $titles = [
            // Administrator Portal
            'home' => 'Dashboard',

            // Library Portal
            'library.home' => 'Library Dashboard',
            'get.learner.attendance' => 'Daily Attendance Summary',
            'profile' => 'Library Profile',
            'subscriptions.choosePlan' => 'Choose Plan',
            'subscriptions.payment' => 'Make Payment',
            'learner.pending.payment' => 'Make Payment',
            'seats' => 'Seat Assignment',
            'learners' => 'Learners List',
            'learners.show' => 'Booking Info',
            'learners.edit' => 'Edit Seat Booking Info',
            'learners.edit.plan' => 'Edit Plan',
            'learners.swap' => 'Swap Seat',
            'learners.upgrade' => 'Upgrade Seat',
            'attendance.summary' => 'Attendance',
            'attendance.logs.page' => 'Attendance Logs',
            'seats.history' => 'Seat Booking History',
            'seats.history.show' => 'Detailed Seat History',
            'library.myplan' => 'My Plan',
            'library.transaction' => 'My Payment Transactions',
            'report.monthly' => 'Monthly Revenue Report',
            'pending.payment.report' => 'Payment Pending Report',
            'learner.report' => 'All Learners Report',
            'upcoming.payment.report' => 'Upcoming Payment Report',
            'expired.learner.report' => 'Expired Learners Report',
            'list.notification' => 'Notifications List',
            'activities.all' => 'Activities Logs',
            'library.master' => 'Configure Library',
            'learners.reactive' => 'Reactive Learner',
            'learnerHistory' => 'Learner History',
            'learner.payment' => 'Make Payment',
            'learners.list.view' => 'Library Counts Details',
            'library.settings' => 'Library Setting',
            'library.upload.form' => 'Import Learners',
            'report.expense' => 'Manage Expanse',
            'library.feedback' => 'Library Feedback',
            'library.video-training' => 'Video Tutorials',
            'learner.expire' => 'Expired The Learner',
            'attendance' => 'Add Learner Attendance',
            'plan.index' => 'Plan List',
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
            'seat.create' => 'Add Seats to Library',
            'learner.other.payment' => 'Library Other Payment',

            'branch.list' => 'Branche List',
            'plantype.index' => 'Plantype | Shifts List',
            'planPrice.index' => 'Plan Type | Shifts Price List',
            'expense.index' => 'Expense List',
            'exam.index' => 'Exams List',
            'learner.search' => 'Find a Learner',
            'create.renew.delete.index' => 'Renew Delete',
            'create.renew.delete.transaction' => 'Renew transaction',
            'hour.create' => 'Add Library Operating Hours',
            'extendDay.create' => 'Add Library Extend Period',
            'lockeramount.create' => 'Add Library Locker Amount',
            'library-users.create' => 'Create Library User',
            'library-users.index' => 'Library Users List',
            'learner.checklist' => 'Print ID card in bulk',
            'notifications.subscription' =>  'Buy Message Subscription',
            'notifications.settings' =>  'Notification Console',
            'notification.dashboard' =>  'Notification Dashbaord',
            'learner.change.plan' => 'Learner Change Plan',
            'general.seat.history' => 'General Seat History',
            'library.how-to-use' => 'How to Use Library',
            'booking.details.approve' => 'QR / Online Bookings',
            'attendance.apply' => 'QR Attendance',
            'branch.configure.create' => 'Setup Branch & Floors',
            'library.configration' => 'Add Shifts',
            'booking.details' => 'Verify and Allot Seat',
            'demo-users.index' => 'Daily Demo Inquiries',
            'demo-users.create' => 'Add Demo Inquiry',
            'activities.all' => 'All Activities',
            'learners.transactions' => 'All Transactions',
            'learners.transactions.edit' => 'Edit Transaction',
            'blogs' => 'Blog Posts Management',
            'add-blog' => 'Create New Blog Post',
            'blog.edit' => 'Edit Blog Post',
            'blog' => 'Blog & Articles',
            'blog-detail' => 'Blog Details',

        ];

        return $titles[$routeName] ?? ucfirst(str_replace('.', ' ', $routeName));
    }
}
