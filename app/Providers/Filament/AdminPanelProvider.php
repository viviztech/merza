<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use App\Filament\Pages\AnalyticsDashboard;
use App\Filament\Pages\Auth\Login;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Filament\Widgets\CrmStatsWidget;
use App\Filament\Widgets\LeadsByStageWidget;
use App\Filament\Widgets\RecentLeadsWidget;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->revealablePasswords(false)
            ->brandName('Merza')
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('2rem')
            ->darkModeBrandLogo(asset('images/icon-192.png'))
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::hex('#D97706'),
                'success' => Color::hex('#059669'),
                'warning' => Color::hex('#F59E0B'),
                'danger'  => Color::hex('#DC2626'),
                'info'    => Color::hex('#0EA5E9'),
                'gray'    => Color::Zinc,
            ])
            ->font('Plus Jakarta Sans', provider: \Filament\FontProviders\GoogleFontProvider::class)
            ->darkMode(false)
            ->navigationGroups([
                NavigationGroup::make('Sales & CRM'),
                NavigationGroup::make('Catalogue'),
                NavigationGroup::make('Orders & Delivery'),
                NavigationGroup::make('Marketing'),
                NavigationGroup::make('Analytics'),
                NavigationGroup::make('Settings')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                AnalyticsDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                CrmStatsWidget::class,
                LeadsByStageWidget::class,
                RecentLeadsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
