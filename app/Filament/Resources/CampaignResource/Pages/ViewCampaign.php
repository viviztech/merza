<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Jobs\DispatchCampaignJob;
use App\Jobs\ProcessCampaignStepJob;
use App\Models\CampaignContact;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCampaign extends ViewRecord
{
    protected static string $resource = CampaignResource::class;

    protected string $view = 'filament.resources.campaign-resource.pages.view-campaign';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('launch')
                ->label('Launch Now')
                ->icon('heroicon-o-rocket-launch')
                ->color('success')
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation()
                ->action(function () {
                    $campaign = $this->record;
                    if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
                        $campaign->update(['status' => 'scheduled']);
                        Notification::make()->title('Campaign scheduled.')->success()->send();
                    } else {
                        DispatchCampaignJob::dispatch($campaign->id);
                        Notification::make()->title('Campaign dispatched to queue.')->success()->send();
                    }
                    $this->refreshFormData(['status', 'total_contacts', 'sent_count']);
                }),

            Action::make('pause')
                ->label('Pause')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'active')
                ->action(function () {
                    $this->record->update(['status' => 'paused']);
                    Notification::make()->title('Campaign paused.')->warning()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('resume')
                ->label('Resume')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => $this->record->status === 'paused')
                ->action(function () {
                    $this->record->update(['status' => 'active']);

                    // Re-dispatch any still-pending due contacts
                    CampaignContact::where('campaign_id', $this->record->id)
                        ->where('status', 'pending')
                        ->where('next_send_at', '<=', now())
                        ->get()
                        ->each(fn ($cc) => ProcessCampaignStepJob::dispatch($cc->id));

                    Notification::make()->title('Campaign resumed.')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, ['draft', 'scheduled', 'paused']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'cancelled']);
                    Notification::make()->title('Campaign cancelled.')->danger()->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
