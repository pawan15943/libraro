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

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

    public function boot()
    {
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
                $data['planTypes'] = PlanType::where('library_id', getLibraryId())->get();
                $data['plans'] = Plan::where('library_id', getLibraryId())->get();
                $first_record = Hour::first();
                $data['totalSeats'] = $first_record ? $first_record->seats : null;
                $data['total_hour'] = $first_record ? $first_record->hour : null;


                if (!$first_record) return collect();

                $totalHour = $first_record->hour;
                $totalSeats = $first_record->seats;

                // Step 1: Get used hours for each seat
                $usedSeats = LearnerDetail::select('seat_no', DB::raw('SUM(hour) as used_hours'))
                    ->whereNotNull('seat_no')
                    ->groupBy('seat_no')
                    ->pluck('used_hours', 'seat_no'); // [seat_no => used_hours]

                $availableSeats = collect();

                // Step 2: Loop through all seat numbers and apply logic
                for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
                    $usedHours = $usedSeats[$seatNo] ?? 0;

                    if ($usedHours < $totalHour) {
                        $availableSeats->push($seatNo);
                    }
                }
                $exams = DB::table('exams')->get();
                $data['exams'] = $exams;
                $data['availableseats'] = $availableSeats;
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
            'profile' => [
                'Dashboard' => route('home'),
                'Library Profile' => route('profile')
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
            'learners.edit' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Edit Seat Booking Info' => route('learners.edit', $parameters)
            ],
            'learners.swap' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Swap Seat' => route('learners.swap', $parameters)
            ],
            'learners.upgrade.renew' => [
                'Dashboard' => route('library.home'),
                'Learners List' => route('learners'),
                'Upgrade Seat' => route('learners.upgrade.renew', $parameters)
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
            'plan.create' => [
                'Dashboard' => route('library.home'),
                'Plan List' => route('plan.index'),
                'Add Plan' => route('plan.create'),
            ],
            'plan.create' => [
                'Dashboard' => route('library.home'),
                'Plan List' => route('plan.index'),
                'Edit Plan' => route('plan.create', $parameters),
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
            'support' => [
                'Dashboard' => route('learner.home'),
                'Support' => route('support'),
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
            'profile' => 'Library Profile',
            'subscriptions.choosePlan' => 'Choose Plan',
            'subscriptions.payment' => 'Make Payment',
            'seats' => 'Seat Assignment',
            'learners' => 'Learners List',
            'learners.show' => 'Booking Info',
            'learners.edit' => 'Edit Seat Booking Info',
            'learners.swap' => 'Swap Seat',
            'learners.upgrade.renew' => 'Upgrade Seat',
            'seats.history' => 'Seat Booking History',
            'seats.history.show' => 'Detailed Seat History',
            'library.myplan' => 'My Plan',
            'library.transaction' => 'My Payment Transactions',
            'report.monthly' => 'Monthly Revenue Report',
            'pending.payment.report' => 'Payment Pending Report',
            'learner.report' => 'All Learners Report',
            'upcoming.payment.report' => 'Upcoming Payment Report',
            'expired.learner.report' => 'Expired Learners Report',
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
            'attendance' => 'Add Learner Attendace',
            'plan.index' => 'Plan List',
            'plan.create' => 'Add Plan',

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

        ];

        return $titles[$routeName] ?? ucfirst(str_replace('.', ' ', $routeName));
    }
}
