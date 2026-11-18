<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Schroeffundering Holland')
            ->brandLogo(asset('schroeffunderingholland.svg'))
            ->darkModeBrandLogo(asset('schroeffunderingholland.svg'))
            ->favicon(asset('favicon.ico'))
            ->font('Inter')
            ->colors([
                // Primary: oranje — 500 = oklch(70.5% .213 47.604)
                'primary' => Color::Orange,
                // Secondary: #150c2f (SFH donkerpaars)
                // Verschoven 500/600 slots zodat witte knoptekst contrast >= 4.5:1 haalt.
                'secondary' => [
                    50 => 'oklch(0.977 0.014 290.523)',
                    100 => 'oklch(0.950 0.033 290.523)',
                    200 => 'oklch(0.905 0.063 290.523)',
                    300 => 'oklch(0.840 0.106 290.523)',
                    400 => 'oklch(0.754 0.150 290.523)',
                    500 => 'oklch(0.515 0.149 290.523)',  // = gegenereerde 700 (6.01:1 vs wit ✅)
                    600 => 'oklch(0.446 0.123 290.523)',  // = gegenereerde 800 (8.05:1 vs wit ✅)
                    700 => 'oklch(0.395 0.100 290.523)',
                    800 => 'oklch(0.330 0.080 290.523)',
                    900 => 'oklch(0.278 0.071 290.523)',
                    950 => 'oklch(0.180 0.045 290.523)',
                ],
            ])
            ->navigationGroups([
                'Verkoop',
                'Administratie',
                'Systeem',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Systeem'),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn () => view('filament.admin-theme'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn () => <<<'HTML'
                <style>
                    .fi-ta-text-summary {
                        text-align: end;
                        padding-inline: 0;
                    }
                    .fi-ta-summary-row td {
                        vertical-align: top;
                    }
                </style>
                HTML,
        );
    }
}
