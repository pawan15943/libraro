<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Helpers\HelperService;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $breadcrumbs = HelperService::generateBreadcrumbs();
            $title = $breadcrumbs->last()['name'] ?? 'Default Title'; // Set the title to the last breadcrumb's name
            $view->with('breadcrumbs', $breadcrumbs)->with('title', $title);
        });
    }
}
