<?php

namespace App\Providers\Filament;

use App\Filament\Resources\RoleResource;
use App\Filament\Resources\StaffResource;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->breadcrumbs(false)
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon(asset('images/logo.png'))
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Setting')
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->resources([
                RoleResource::class,
                StaffResource::class,
            ])
            ->colors([
                'primary' => Color::hex('#5347CE'),
                'info' => Color::hex('#4896FE'),
                'success' => Color::hex('#16C8C7'),
            ])
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3rem')
            ->topNavigation()
            ->font('Figtree')
            // ->font(
            //     'Myriad Pro',
            //     url: asset('fonts/myriad-pro/fonts.css'),
            //     provider: LocalFontProvider::class,
            // )
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
