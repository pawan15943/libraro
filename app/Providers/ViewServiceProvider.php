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
            // Blade components have their own $title prop; a page title here would overwrite it
            if (str_starts_with($view->getName(), 'components.')) {
                return;
            }
            $breadcrumbs = HelperService::generateBreadcrumbs();
            $title = $breadcrumbs->last()['name'] ?? 'Default Title'; // Set the title to the last breadcrumb's name
            $view->with('breadcrumbs', $breadcrumbs)->with('title', $title);
        });
    }
}
