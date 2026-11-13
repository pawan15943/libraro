<?php

namespace App\Support;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * Navigation of the platform admin portal (guard "web").
 *
 * One list drives the sidebar, the mobile tab bar and the page title, so every
 * admin page is reachable and highlights the right item. Each item:
 *   route   – route the item opens
 *   active  – route name patterns (request()->routeIs) that mark it as current
 *   perm    – permission checked with the "has-permission" gate (null = everyone)
 */
class AdminNav
{
    public static function sections(): array
    {
        return [
            [
                'title' => null,
                'items' => [
                    ['label' => 'Dashboard', 'icon' => 'fa-solid fa-gauge-high', 'tint' => 'blue', 'route' => 'home', 'active' => ['home'], 'perm' => 'view-dashboard'],
                ],
            ],
            [
                'title' => 'Libraries', 'icon' => 'fa-solid fa-building-columns', 'tint' => 'indigo',
                'items' => [
                    ['label' => 'All Libraries', 'icon' => 'fa-solid fa-building-columns', 'tint' => 'indigo', 'route' => 'library',
                        'active' => ['library', 'library.show', 'library.branches', 'library.branch.*', 'library.payment', 'library.upgrade', 'library.count.view'], 'perm' => 'manage-libraries'],
                    ['label' => 'Add Library', 'icon' => 'fa-solid fa-plus', 'tint' => 'green', 'route' => 'library.create', 'active' => ['library.create'], 'perm' => 'manage-libraries'],
                ],
            ],
            [
                'title' => 'Calling CRM', 'icon' => 'fa-solid fa-headset', 'tint' => 'blue',
                'items' => [
                    ['label' => 'Lead Dashboard', 'icon' => 'fa-solid fa-chart-line', 'route' => 'crm.dashboard', 'active' => ['crm.dashboard'], 'perm' => 'crm-view-dashboard'],
                    ['label' => 'Call Queue', 'icon' => 'fa-solid fa-phone-volume', 'route' => 'crm.queue', 'active' => ['crm.queue'], 'perm' => 'crm-view-leads'],
                    ['label' => 'All Leads', 'icon' => 'fa-solid fa-address-book', 'route' => 'crm.leads.index', 'active' => ['crm.leads.*'], 'perm' => 'crm-view-leads'],
                    ['label' => 'Follow-ups', 'icon' => 'fa-regular fa-bell', 'route' => 'crm.reminders.index', 'active' => ['crm.reminders.*'], 'perm' => 'crm-view-leads'],
                    ['label' => 'Call Log', 'icon' => 'fa-solid fa-clock-rotate-left', 'route' => 'crm.calls.index', 'active' => ['crm.calls.*'], 'perm' => 'crm-view-leads'],
                    ['label' => 'Import Leads', 'icon' => 'fa-solid fa-file-arrow-up', 'route' => 'crm.import', 'active' => ['crm.import*'], 'perm' => 'crm-import-leads'],
                    ['label' => 'CRM Settings', 'icon' => 'fa-solid fa-sliders', 'route' => 'crm.settings.index', 'active' => ['crm.settings.*'], 'perm' => 'crm-manage-settings'],
                ],
            ],
            [
                'title' => 'Plans & Billing', 'icon' => 'fa-solid fa-layer-group', 'tint' => 'purple',
                'items' => [
                    ['label' => 'Subscription Plans', 'icon' => 'fa-solid fa-layer-group', 'tint' => 'purple', 'route' => 'admin.subscriptions',
                        'active' => ['admin.subscriptions', 'admin.subscriptions.*', 'subscription.master', 'subscriptions.edit', 'subscriptions.edit.legacy'], 'perm' => 'view-subscriptions'],
                    ['label' => 'Library Permissions', 'icon' => 'fa-solid fa-key', 'tint' => 'yellow', 'route' => 'permissions',
                        'active' => ['permissions', 'permissions.*'], 'perm' => 'manage-permissions'],
                    ['label' => 'Permission Groups', 'icon' => 'fa-solid fa-folder-tree', 'tint' => 'gray', 'route' => 'permission-categories.index',
                        'active' => ['permission-categories.*'], 'perm' => 'manage-permissions'],
                    ['label' => 'Features', 'icon' => 'fa-solid fa-star', 'tint' => 'pink', 'route' => 'feature.create',
                        'active' => ['feature.*'], 'perm' => 'manage-features'],
                ],
            ],
            [
                'title' => 'Website', 'icon' => 'fa-solid fa-globe', 'tint' => 'blue',
                'items' => [
                    ['label' => 'Pages', 'icon' => 'fa-solid fa-file-lines', 'tint' => 'blue', 'route' => 'page', 'active' => ['page', 'page.*', 'add-page'], 'perm' => 'manage-website-pages'],
                    ['label' => 'Blog', 'icon' => 'fa-solid fa-pen-nib', 'tint' => 'orange', 'route' => 'blogs', 'active' => ['blogs', 'blog.*', 'add-blog'], 'perm' => 'manage-blogs'],
                    ['label' => 'Videos', 'icon' => 'fa-solid fa-circle-play', 'tint' => 'red', 'route' => 'videos.index', 'active' => ['videos.*'], 'perm' => 'manage-videos'],
                    ['label' => 'Demo Requests', 'icon' => 'fa-solid fa-calendar-check', 'tint' => 'green', 'route' => 'demo', 'active' => ['demo', 'demo.list'], 'perm' => 'manage-demo-requests'],
                    ['label' => 'Inquiries', 'icon' => 'fa-solid fa-envelope', 'tint' => 'teal', 'route' => 'inquiry', 'active' => ['inquiry', 'inquery.list'], 'perm' => 'manage-inquiries'],
                ],
            ],
            [
                'title' => 'Engage', 'icon' => 'fa-solid fa-bullhorn', 'tint' => 'orange',
                'items' => [
                    ['label' => 'Notifications', 'icon' => 'fa-solid fa-bell', 'tint' => 'red', 'route' => 'admin.notifications.index',
                        'active' => ['admin.notifications.*', 'create.notification', 'notifications.edit'], 'perm' => null],
                    ['label' => 'Limo Questions', 'icon' => 'fa-solid fa-circle-question', 'tint' => 'indigo', 'route' => 'admin.limo.questions', 'active' => ['admin.limo.questions'], 'perm' => null],
                    ['label' => 'Limo Knowledge', 'icon' => 'fa-solid fa-book-open', 'tint' => 'purple', 'route' => 'admin.limo.knowledge', 'active' => ['admin.limo.knowledge*'], 'perm' => null],
                ],
            ],
            [
                'title' => 'Access', 'icon' => 'fa-solid fa-user-shield', 'tint' => 'green',
                'items' => [
                    ['label' => 'Admin Users', 'icon' => 'fa-solid fa-user', 'tint' => 'blue', 'route' => 'admin.users', 'active' => ['admin.users*'], 'perm' => 'view-users'],
                    ['label' => 'Roles', 'icon' => 'fa-solid fa-shield-halved', 'tint' => 'green', 'route' => 'admin.roles', 'active' => ['admin.roles*'], 'perm' => 'view-roles'],
                    ['label' => 'Admin Permissions', 'icon' => 'fa-solid fa-lock', 'tint' => 'gray', 'route' => 'admin-permissions', 'active' => ['admin-permissions*'], 'perm' => 'view-permissions'],
                    ['label' => 'Menus', 'icon' => 'fa-solid fa-bars-staggered', 'tint' => 'yellow', 'route' => 'menu.create', 'active' => ['menu.*'], 'perm' => 'manage-users'],
                    ['label' => 'Design System', 'icon' => 'fa-solid fa-swatchbook', 'route' => 'admin.design-system', 'active' => ['admin.design-system'], 'perm' => 'manage-users'],
                ],
            ],
        ];
    }

    /** Sections with only the items the signed-in admin may open. */
    public static function visible(): array
    {
        $out = [];
        foreach (self::sections() as $section) {
            $items = array_values(array_filter($section['items'], function ($item) {
                return self::canOpen($item['route'])
                    && (empty($item['perm']) || Gate::allows('has-permission', [$item['perm']]));
            }));
            if ($items) {
                $section['items'] = $items;
                $out[] = $section;
            }
        }

        return $out;
    }

    /**
     * The route exists and its "role:" middleware (e.g. role:superadmin) lets
     * the signed-in admin in, so a menu item never leads to a 403.
     */
    public static function canOpen(string $name): bool
    {
        $route = Route::getRoutes()->getByName($name);
        if (! $route) {
            return false;
        }

        $user = auth('web')->user();
        foreach ($route->gatherMiddleware() as $mw) {
            if (is_string($mw) && str_starts_with($mw, 'role:')) {
                $roles = explode('|', substr($mw, 5));
                if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole($roles)) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function isActive(array $item): bool
    {
        return request()->routeIs(...$item['active']);
    }

    /** Large page title + one-line subtitle for every admin screen. */
    public static function page(): array
    {
        $titles = [
            'home' => ['Dashboard', 'Live view of libraries, subscriptions and revenue'],
            'library' => ['Libraries', 'Every library registered on Libraro'],
            'library.create' => ['Add Library', 'Register a new library and send the verification code'],
            'library.show' => ['Library Details', 'Profile, plan and payment history'],
            'library.branches' => ['Branches', 'Branches of this library'],
            'library.branch.plans' => ['Branch Plans', 'Plans offered at this branch'],
            'library.branch.plantypes' => ['Branch Shifts', 'Shift types offered at this branch'],
            'library.branch.prices' => ['Branch Prices', 'Price of each plan and shift'],
            'library.branch.seatHour' => ['Seats & Hours', 'Seat count and opening hours'],
            'library.branch.edit' => ['Edit Branch', 'Branch name, address and contact'],
            'library.payment' => ['Library Payment', 'Record a subscription payment'],
            'library.upgrade' => ['Upgrade Plan', 'Move a library to another plan'],
            'library.count.view' => ['Libraries', 'Filtered library list'],
            'crm.dashboard' => ['Lead Dashboard', 'Calls, conversions and team performance'],
            'crm.queue' => ['Call Queue', 'Leads to call now — enrolled and not-interested leads never show here'],
            'crm.leads.index' => ['All Leads', 'Search, filter, assign and export leads'],
            'crm.leads.create' => ['Add Lead', 'Add one lead by hand'],
            'crm.leads.show' => ['Lead', 'Timeline, calls and follow-ups'],
            'crm.leads.edit' => ['Edit Lead', 'Contact details of the lead'],
            'crm.reminders.index' => ['Follow-ups', 'Calls you promised to make'],
            'crm.calls.index' => ['Call Log', 'Every call with its disposition'],
            'crm.import' => ['Import Leads', 'Upload a CSV file or paste a list'],
            'crm.settings.index' => ['CRM Settings', 'Stages, dispositions and message templates'],
            'admin.subscriptions' => ['Subscription Plans', 'Plans libraries can buy'],
            'subscription.master' => ['Subscription Plans', 'Plans libraries can buy'],
            'admin.subscriptions.create' => ['New Plan', 'Create a subscription plan'],
            'subscriptions.edit' => ['Edit Plan', 'Change price, limits and permissions'],
            'subscriptions.edit.legacy' => ['Edit Plan', 'Change price, limits and permissions'],
            'subscriptions.permissions' => ['Plan Permissions', 'What each plan unlocks'],
            'planwise.permissions' => ['Plan Permissions', 'Permissions included in this plan'],
            'permissions' => ['Library Permissions', 'Permissions used by library plans'],
            'permissions.create' => ['New Permission', 'Add a library permission'],
            'permissions.edit' => ['Edit Permission', 'Change a library permission'],
            'permission-categories.index' => ['Permission Groups', 'Groups that organise permissions'],
            'permission-categories.create' => ['New Group', 'Add a permission group'],
            'permission-categories.edit' => ['Edit Group', 'Change a permission group'],
            'feature.create' => ['Features', 'Features shown on plans and the website'],
            'feature.edit' => ['Edit Feature', 'Change a feature'],
            'page' => ['Pages', 'Website pages'],
            'add-page' => ['New Page', 'Create a website page'],
            'page.edit' => ['Edit Page', 'Change a website page'],
            'blogs' => ['Blog', 'Articles on the Libraro website'],
            'add-blog' => ['New Article', 'Write a blog article'],
            'blog.edit' => ['Edit Article', 'Change a blog article'],
            'videos.index' => ['Videos', 'Help and tutorial videos'],
            'demo' => ['Demo Requests', 'People who asked for a demo'],
            'inquiry' => ['Inquiries', 'Messages from the contact form'],
            'admin.notifications.index' => ['Notifications', 'Messages sent to libraries'],
            'create.notification' => ['New Notification', 'Send a message to libraries'],
            'notifications.edit' => ['Edit Notification', 'Change a sent message'],
            'admin.limo.questions' => ['Limo Questions', 'Questions Limo could not answer'],
            'admin.limo.knowledge' => ['Limo Knowledge', 'Answers Limo can give'],
            'admin.limo.knowledge.create' => ['New Answer', 'Teach Limo a new answer'],
            'admin.limo.knowledge.edit' => ['Edit Answer', 'Change what Limo says'],
            'admin.users' => ['Admin Users', 'People who can sign in to this portal'],
            'admin.users.create' => ['New Admin User', 'Invite someone to the admin portal'],
            'admin.users.edit' => ['Edit Admin User', 'Profile, role and access'],
            'admin.roles' => ['Roles', 'Groups of admin permissions'],
            'admin.roles.create' => ['New Role', 'Create an admin role'],
            'admin.roles.edit' => ['Edit Role', 'Change an admin role'],
            'admin-permissions' => ['Admin Permissions', 'Actions admins can be allowed'],
            'admin-permissions.create' => ['New Admin Permission', 'Add an admin permission'],
            'admin-permissions.edit' => ['Edit Admin Permission', 'Change an admin permission'],
            'admin.design-system' => ['Design System', 'Colours, type and components of the admin portal'],
            'menu.create' => ['Menus', 'Sidebar menus of the library portal'],
            'menu.edit' => ['Edit Menu', 'Change a sidebar menu'],
            'configration.upload' => ['Import', 'Upload a CSV file'],
        ];

        $name = (string) Route::currentRouteName();
        $current = self::current();

        [$title, $subtitle] = $titles[$name] ?? [$current['label'] ?? ucwords(str_replace(['.', '-', '_'], ' ', $name)), null];

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'section' => $current['section'] ?? null,
            'parent' => ($current && $current['label'] !== $title) ? $current : null,
        ];
    }

    /** The item for the current page, with its section title. */
    public static function current(): ?array
    {
        foreach (self::sections() as $section) {
            foreach ($section['items'] as $item) {
                if (self::isActive($item)) {
                    return $item + ['section' => $section['title']];
                }
            }
        }

        return null;
    }
}
