<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Show / Hide Options (Setup → Show / Hide Options): every feature a library owner can switch off for a branch.
 *
 * Storage is unchanged: `branches.hide_field` is a JSON list of what is HIDDEN. Older options keep their
 * `toggle_features.id` (the views check them as in_array('3', toggleHideField())), newer ones use a string
 * key ('chat', 'quiz' …) defined only here, so no table row is needed for them. Unknown ids found in
 * `toggle_features` (rows added later, e.g. Orders) still show, under their own category.
 *
 * A switch that is ON means the feature is shown. `routes` are blocked (GET pages redirect to the dashboard)
 * and dropped from the library sidebar; `learner` routes are dropped from the learner portal menu and blocked.
 */
class FeatureToggles
{
    /** Request-scoped caches (queue workers / tests run many requests in one process). */
    private static array $cache = [];

    /**
     * [key => [label, hint, icon, category, routes[], learner routes[], where]]. Order = order on the page.
     * where: 'library' | 'learner' | 'both'.
     */
    public static function registry(): array
    {
        return [
            // ---- Modules & menus -------------------------------------------------------------
            '10' => ['Attendance', 'Attendance menu, QR / scanner attendance and the attendance report. Learners lose the Attendance page too.', 'clipboard-check', 'modules',
                ['attendance', 'attendance.summary', 'attendance.logs.page', 'get.learner.attendance', 'attendance.apply', 'attendance.qrcode', 'attendance.qrcode.poster', 'attendance.report', 'library.attendance.scan'],
                ['my-attendance', 'learner.attendance.day', 'learner.attendance.scan', 'learner.attendance.scan.store'], 'both'],
            '25' => ['Reports', 'Reports hub: collections, dues, renewals, profit & loss, growth, occupancy and activity.', 'chart-column', 'modules',
                ['reports.hub', 'reports.growth', 'reports.occupancy', 'payment.collection.report', 'monthly.payment.collection.report', 'report.monthly', 'partial.payment.collection.report', 'upcoming.payment.report', 'pending.payment.report', 'expired.learner.report', 'learner.report', 'activity.report', 'report.expense'],
                [], 'library'],
            'chat' => ['Student chat', 'Chat between the library and learners, with the chat button and new-message alerts.', 'message-circle', 'modules',
                ['chat.index', 'chat.thread'], ['learner.chat'], 'both'],
            'requests' => ['Learner requests', 'Requests learners send from their portal (seat change, leave …).', 'inbox', 'modules',
                ['learner.requests.index'], ['learner.request'], 'both'],
            'enquiries' => ['Enquiries & demo', 'Enquiries and the demo-learner pages under Learners.', 'message-square-text', 'modules',
                ['library.enquiry', 'demo-users.index', 'demo-users.create'], [], 'library'],
            '9' => ['Learner history', 'Past bookings: history and the seat register.', 'scroll-text', 'modules',
                ['learnerHistory', 'seats.history', 'seats.history.show', 'general.seat.history'], [], 'library'],
            'notifications' => ['Notifications & messages', 'Notification console, message templates and message subscription.', 'bell', 'modules',
                ['notifications.settings', 'notification.dashboard', 'notifications.subscription', 'message.templates', 'list.notification', 'create.notification'], [], 'library'],
            'quiz' => ['Student quiz', 'Quizzes the library makes for learners.', 'list-checks', 'modules',
                ['quiz.index', 'quiz.create', 'quiz.edit'], ['learner.quizzes', 'learner.quiz.play', 'learner.quiz.result'], 'both'],
            '27' => ['Library corner', 'Complaints, suggestions, learner feedback and book categories.', 'lightbulb', 'modules',
                ['library.learner.complaints', 'library.learner.feedback', 'library.learner.suggestions', 'book.category.index', 'book.category.create'],
                ['complaints', 'learner.suggestions', 'learner.feadback', 'books-library'], 'both'],
            '26' => ['Staff & roles', 'Staff users, roles and permissions.', 'users-round', 'modules',
                ['library-users.index', 'library-users.create', 'library-users.roles', 'library-users.permission-list', 'library-users.permissions'], [], 'library'],
            'referral' => ['Referral & rewards', 'Refer other libraries and earn reward points.', 'gift', 'modules',
                ['referral.dashboard'], [], 'library'],
            'global_discount' => ['Global discount', 'Library-wide offers applied to new bookings and renewals.', 'tag', 'modules',
                ['global-discount.index'], [], 'library'],
            '28' => ['Feedback to Libraro', 'The feedback button and page for the Libraro team.', 'message-square', 'modules',
                ['library.feedback'], [], 'library'],
            '21' => ['Settings shortcut', 'The settings icon in the top bar (this page stays reachable from the menu).', 'settings', 'modules',
                [], [], 'library'],

            // ---- Learner portal ----------------------------------------------------------------
            'lp_study' => ['Study tools', 'My goal, stopwatch and the daily GK quiz.', 'timer', 'learner',
                [], ['learner.goal', 'learner.stopwatch', 'learner.gk'], 'learner'],
            'lp_payments' => ['Payments page', 'Learners see their bills and receipts.', 'wallet', 'learner',
                [], ['my-transactions'], 'learner'],
            'lp_idcard' => ['ID card page', 'Learners open and download their ID card.', 'id-card', 'learner',
                [], ['my-library-id'], 'learner'],
            'lp_blog' => ['Blog', 'Study articles from Libraro.', 'newspaper', 'learner',
                [], ['learner.blog', 'blog.detail.show'], 'learner'],

            // ---- Learner operations --------------------------------------------------------------
            '13' => ['Upgrade plan', 'Upgrade a learner to a bigger plan.', 'circle-arrow-up', 'operations', [], [], 'library'],
            '14' => ['Change plan', 'Change the plan of the running booking.', 'repeat', 'operations', [], [], 'library'],
            '17' => ['Edit seat plan', 'Edit a booking (dates, price, plan).', 'pen-line', 'operations', [], [], 'library'],
            '16' => ['Close plan', 'Close a learner\'s seat before the plan ends.', 'circle-x', 'operations', [], [], 'library'],
            '33' => ['Gift days', 'Add free days to a plan.', 'gift', 'operations', [], [], 'library'],
            '34' => ['Freeze days', 'Put a plan on hold.', 'snowflake', 'operations', [], [], 'library'],
            '15' => ['ID cards', 'Generate and print ID cards (one learner or in bulk).', 'id-card', 'operations',
                ['learner.checklist', 'learner.idcard.bulk'], [], 'library'],

            // ---- Learners list & buttons ---------------------------------------------------------
            '12' => ['General seat booking', 'Book a learner without a fixed seat.', 'armchair', 'list', [], [], 'library'],
            '24' => ['Seat & shift counts', 'Total seats and shift-wise counts above the learner list.', 'layout-grid', 'list', [], [], 'library'],
            '11' => ['Import learners', 'Import learners from a CSV file.', 'upload', 'list', ['library.upload.form'], [], 'library'],
            '22' => ['Export learners', 'Download the learner list.', 'download', 'list', ['learners.export-csv'], [], 'library'],

            // ---- Booking form --------------------------------------------------------------------
            '1' => ['Email', 'Email field.', 'mail', 'booking', [], [], 'library'],
            '2' => ['Date of birth', 'Date of birth field.', 'cake', 'booking', [], [], 'library'],
            '30' => ['Alternate mobile', 'Second mobile number.', 'phone', 'booking', [], [], 'library'],
            '29' => ['Father\'s name', 'Father\'s name field.', 'user-round', 'booking', [], [], 'library'],
            '32' => ['Address', 'Address field.', 'map-pin', 'booking', [], [], 'library'],
            '31' => ['Remark', 'Remark / note field.', 'notebook-pen', 'booking', [], [], 'library'],
            '8' => ['Profile photo', 'Profile picture upload.', 'camera', 'booking', [], [], 'library'],
            '5' => ['ID proof', 'ID proof upload.', 'file-up', 'booking', [], [], 'library'],
            '4' => ['Exam', 'Exam the learner is preparing for.', 'graduation-cap', 'booking', [], [], 'library'],
            '3' => ['Locker', 'Locker option and amount.', 'lock', 'booking', [], [], 'library'],
            '6' => ['Discount', 'Discount on the booking.', 'badge-indian-rupee', 'booking', [], [], 'library'],
            '7' => ['Other details section', 'The extra details section (photo, ID, family, address).', 'list', 'booking', [], [], 'library'],

            // ---- Payments ------------------------------------------------------------------------
            '19' => ['Token money', 'Token money options.', 'hand-coins', 'payments', [], [], 'library'],
            '20' => ['Service charges', 'Service charge options.', 'receipt', 'payments', [], [], 'library'],

            // ---- Dashboard -----------------------------------------------------------------------
            '18' => ['This month\'s counts', 'Current-month numbers on the dashboard.', 'calendar-days', 'dashboard', [], [], 'library'],
            '23' => ['Activity & revenue', 'Recent activity and the revenue report on the dashboard.', 'activity', 'dashboard', [], [], 'library'],
        ];
    }

    /** [category key => [title, hint, icon]] in page order. */
    public static function categories(): array
    {
        return [
            'modules' => ['Modules & menus', 'Whole sections of the library panel', 'layout-grid'],
            'learner' => ['Learner portal', 'What learners see in their app', 'smartphone'],
            'operations' => ['Learner operations', 'Actions on a learner\'s booking', 'repeat'],
            'list' => ['Learners list', 'Buttons and counts on the learners page', 'users'],
            'booking' => ['Booking form', 'Fields on the new-booking form', 'file-text'],
            'payments' => ['Payments', 'Money options on forms', 'wallet'],
            'dashboard' => ['Dashboard', 'Cards on the dashboard', 'layout-dashboard'],
            'orders' => ['Canteen & orders', 'Ordering & Services menus', 'coffee'],
            'privacy' => ['Privacy', 'Learner contact details', 'shield-check'],
            'more' => ['More options', 'Other switches', 'sliders-horizontal'],
        ];
    }

    /**
     * Page data: [category => [title, hint, icon, items[]]], item = [key, label, hint, icon, where, shown].
     * Rows of `toggle_features` not in the registry are added (Orders by name, Privacy id 35, anything new).
     */
    public static function grouped(array $hidden): array
    {
        $hidden = array_map('strval', $hidden);
        $cats = [];
        foreach (self::categories() as $c => [$title, $hint, $icon]) {
            $cats[$c] = ['title' => $title, 'hint' => $hint, 'icon' => $icon, 'items' => []];
        }

        foreach (self::registry() as $key => [$label, $hint, $icon, $cat, , , $where]) {
            $key = (string) $key;
            $cats[$cat]['items'][] = ['key' => $key, 'label' => $label, 'hint' => $hint, 'icon' => $icon, 'where' => $where, 'shown' => ! in_array($key, $hidden, true)];
        }

        foreach (self::extraRows() as $row) {
            $key = (string) $row->id;
            $cat = $row->category === 'Orders' ? 'orders' : ((int) $row->id === 35 ? 'privacy' : 'more');
            $label = trim(preg_replace('/^hide\s+/i', '', (string) $row->name));
            $label = preg_replace('/\s+(menu|option|field)$/i', '', $label) ?: $row->name;
            $isHidden = in_array($key, $hidden, true);
            if ($cat === 'privacy') {
                // id 35 ON = mask learner contacts; as a "show" switch it reads the other way round
                $cats[$cat]['items'][] = ['key' => $key, 'label' => 'Full mobile & email', 'hint' => 'Off: learner mobile and email are masked in lists (98xxxx1234).', 'icon' => 'eye', 'where' => 'library', 'shown' => ! $isHidden];
                continue;
            }
            $icon = $cat === 'orders' ? (str_contains(strtolower($row->name), 'report') ? 'chart-column' : (str_contains(strtolower($row->name), 'dashboard') ? 'layout-dashboard' : 'coffee')) : 'sliders-horizontal';
            $cats[$cat]['items'][] = ['key' => $key, 'label' => Str::ucfirst($label), 'hint' => (string) ($row->description ?? ''), 'icon' => $icon, 'where' => 'library', 'shown' => ! $isHidden];
        }

        return array_filter($cats, fn ($c) => ! empty($c['items']));
    }

    /** Every key the page may save (registry keys + toggle_features ids). */
    public static function allowedKeys(): array
    {
        $keys = array_map('strval', array_keys(self::registry()));
        foreach (self::allRowIds() as $id) {
            $keys[] = (string) $id;
        }

        return array_values(array_unique($keys));
    }

    /** Is this option hidden for the current branch? */
    public static function hidden(string|int $key): bool
    {
        return in_array((string) $key, self::branchHidden(), true);
    }

    /** Library route names blocked / dropped from the sidebar for the current branch. */
    public static function hiddenRoutes(): array
    {
        return self::remember('routes', function () {
            $out = [];
            foreach (self::registry() as $key => $def) {
                if (self::hidden($key)) {
                    $out = array_merge($out, $def[4], $def[5]);
                }
            }

            return array_values(array_unique($out));
        });
    }

    public static function routeHidden(?string $routeName): bool
    {
        return $routeName !== null && $routeName !== '' && in_array($routeName, self::hiddenRoutes(), true);
    }

    /** Stored value for a branch: hidden keys, keeping order and dropping unknown junk. */
    public static function normalise(array $hidden): array
    {
        $allowed = self::allowedKeys();

        return array_values(array_unique(array_filter(array_map('strval', $hidden), fn ($k) => in_array($k, $allowed, true))));
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    private static function branchHidden(): array
    {
        return self::remember('hidden', function () {
            $branchId = (int) (function_exists('getCurrentBranch') ? getCurrentBranch() : 0);
            if (! $branchId) {
                return [];
            }
            try {
                $raw = DB::table('branches')->where('id', $branchId)->value('hide_field');
            } catch (\Throwable $e) {
                return [];
            }

            return array_map('strval', (array) (json_decode((string) $raw, true) ?: []));
        });
    }

    private static function extraRows()
    {
        return self::remember('extra', function () {
            $known = array_map('strval', array_keys(self::registry()));
            try {
                return DB::table('toggle_features')->orderBy('id')->get()
                    ->reject(fn ($r) => in_array((string) $r->id, $known, true))->values()->all();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    private static function allRowIds(): array
    {
        return self::remember('ids', function () {
            try {
                return DB::table('toggle_features')->pluck('id')->all();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    private static function remember(string $key, callable $fn)
    {
        $scope = app()->bound('request') ? spl_object_id(app('request')) : 0;
        $user = function_exists('getCurrentBranch') ? (string) getCurrentBranch() : '';
        $k = $key . ':' . $user;
        if (! isset(self::$cache[$scope])) {
            self::$cache = [$scope => []];
        }
        if (! array_key_exists($k, self::$cache[$scope])) {
            self::$cache[$scope][$k] = $fn();
        }

        return self::$cache[$scope][$k];
    }
}
