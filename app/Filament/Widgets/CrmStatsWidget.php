<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $newLeads       = Lead::where('stage', 'new')->count();
        $activeLeads    = Lead::whereNotIn('stage', ['converted', 'lost'])->count();
        $convertedMonth = Lead::where('stage', 'converted')
                              ->whereMonth('converted_at', now()->month)->count();
        $revenueMonth   = Order::where('status', '!=', 'cancelled')
                               ->whereMonth('created_at', now()->month)->sum('total');
        $totalContacts  = Contact::count();
        $pendingOrders  = Order::where('status', 'pending')->count();

        return [
            Stat::make('New Leads', $newLeads)
                ->description('Awaiting contact')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),

            Stat::make('Active Pipeline', $activeLeads)
                ->description('Leads in progress')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('info'),

            Stat::make('Converted This Month', $convertedMonth)
                ->description('Leads turned customers')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Revenue This Month', "\u{20B9}" . number_format($revenueMonth, 2))
                ->description('From confirmed orders')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Contacts', $totalContacts)
                ->description('In CRM database')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Pending Orders', $pendingOrders)
                ->description('Awaiting confirmation')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'gray'),
        ];
    }
}
