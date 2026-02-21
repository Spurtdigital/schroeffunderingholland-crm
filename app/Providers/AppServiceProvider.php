<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Observers\LeadObserver;
use App\Observers\ProjectItemObserver;
use App\Observers\ProjectObserver;
use App\Observers\QuoteItemObserver;
use App\Observers\QuoteObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        Lead::observe(LeadObserver::class);
        Quote::observe(QuoteObserver::class);
        QuoteItem::observe(QuoteItemObserver::class);
        Project::observe(ProjectObserver::class);
        ProjectItem::observe(ProjectItemObserver::class);
    }
}
