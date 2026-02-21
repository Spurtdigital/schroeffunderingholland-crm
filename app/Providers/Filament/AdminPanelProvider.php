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
            ->colors([
                // Primary: #150c2f (SFH donkerpaars) — huisstijl primary buttons
                // 500/600 slots zijn verschoven naar 700/800 waarden zodat
                // Filament's ButtonComponent witte tekst kiest (contrast >= 4.5:1).
                'primary' => [
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
                // Secondary: #EF7D00 (SFH oranje)
                // Zelfde verschuiving voor witte knoptekst.
                'secondary' => [
                    50 => 'oklch(0.977 0.014 55.475)',
                    100 => 'oklch(0.950 0.033 55.475)',
                    200 => 'oklch(0.905 0.063 55.475)',
                    300 => 'oklch(0.840 0.106 55.475)',
                    400 => 'oklch(0.754 0.150 55.475)',
                    500 => 'oklch(0.515 0.149 55.475)',  // = gegenereerde 700 (5.92:1 vs wit ✅)
                    600 => 'oklch(0.446 0.123 55.475)',  // = gegenereerde 800 (7.98:1 vs wit ✅)
                    700 => 'oklch(0.395 0.100 55.475)',
                    800 => 'oklch(0.330 0.080 55.475)',
                    900 => 'oklch(0.278 0.071 55.475)',
                    950 => 'oklch(0.180 0.045 55.475)',
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
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
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
}
