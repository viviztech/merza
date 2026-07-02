<?php

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Conversation;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewConversation extends ViewRecord
{
    protected static string $resource = ConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Now')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->is_bot && is_null($this->record->sent_at))
                ->requiresConfirmation()
                ->modalDescription(fn () => "Send this message to {$this->record->contact?->name}?")
                ->action(function () {
                    SendWhatsAppMessageJob::dispatch($this->record->id);
                    $this->refreshFormData(['sent_at', 'status', 'wa_message_id']);
                }),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getViewData(): array
    {
        // Fetch the full thread for this contact on this channel
        $thread = Conversation::where('contact_id', $this->record->contact_id)
            ->where('channel', $this->record->channel)
            ->orderBy('created_at', 'asc')
            ->get();

        return ['thread' => $thread];
    }

    protected string $view = 'filament.resources.conversation-resource.pages.view-conversation';
}
