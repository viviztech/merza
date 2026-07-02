<?php

namespace App\Filament\Widgets;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStatsWidget extends BaseWidget
{
    protected static bool $isDiscovered = false;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $monthRevenue = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $lastMonthRevenue = Order::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->where('status', '!=', 'cancelled')
            ->sum('total');

        $revenueChange = $lastMonthRevenue > 0
            ? round((($monthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        $thisWeekContacts = Contact::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $activeLeads      = Lead::whereNotIn('stage', ['won', 'lost'])->count();
        $activeOrders     = Order::whereIn('status', ['pending', 'confirmed', 'preparing', 'delivering'])->count();
        $activeCampaigns  = Campaign::where('status', 'active')->count();
        $totalMessages    = Conversation::where('channel', 'whatsapp')->count();

        return [
            Stat::make('Total Contacts', Contact::count())
                ->description("+{$thisWeekContacts} this week")
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),

            Stat::make('Active Leads', $activeLeads)
                ->description('In pipeline (not won/lost)')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('warning'),

            Stat::make('Active Orders', $activeOrders)
                ->description('Pending → delivering')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color($activeOrders > 0 ? 'info' : 'gray'),

            Stat::make('Monthly Revenue', "\u{20B9}" . number_format($monthRevenue, 0))
                ->description(($revenueChange >= 0 ? '+' : '') . $revenueChange . '% vs last month')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger'),

            Stat::make('WhatsApp Messages', $totalMessages)
                ->description('All time (in + out)')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('success'),

            Stat::make('Active Campaigns', $activeCampaigns)
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color($activeCampaigns > 0 ? 'warning' : 'gray'),
        ];
    }
}
