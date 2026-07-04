<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Lead;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Observers\CompanyObserver;
use App\Observers\LeadObserver;
use App\Observers\ProjectItemObserver;
use App\Observers\ProjectObserver;
use App\Observers\QuoteItemObserver;
use App\Observers\QuoteObserver;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        // Super admin heeft toegang tot alles
        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        // BCC alle uitgaande mails naar de eigenaar
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $event->message->bcc('bob@schroeffunderingholland.nl');
        });

        // Register observers
        Company::observe(CompanyObserver::class);
        Lead::observe(LeadObserver::class);
        Quote::observe(QuoteObserver::class);
        QuoteItem::observe(QuoteItemObserver::class);
        Project::observe(ProjectObserver::class);
        ProjectItem::observe(ProjectItemObserver::class);
    }
}
