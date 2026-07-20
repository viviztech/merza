<?php

namespace App\Filament\Resources\ContactResource\Pages;

use App\Filament\Pages\QuickOrder;
use App\Filament\Resources\ContactResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\OrderResource;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\BotSetting;
use App\Models\Conversation;
use App\Services\BotReplyService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    protected string $view = 'filament.resources.contact-resource.pages.view-contact';

    public string $replyMessage = '';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('convertToOrder')
                ->label('Convert to Order')
                ->icon('heroicon-o-shopping-bag')
                ->color('success')
                ->visible(fn () => $this->record->active_lead !== null)
                ->modalWidth('3xl')
                ->modalHeading('Convert Enquiry to Order')
                ->modalSubmitActionLabel('Create Order')
                ->steps(fn () => LeadResource::convertToOrderSteps($this->record->active_lead))
                ->fillForm(fn () => LeadResource::convertToOrderFormDefaults($this->record->active_lead))
                ->action(function (array $data) {
                    $order = LeadResource::handleConvertToOrder($data, $this->record->active_lead);

                    Notification::make()->title("Order {$order->order_number} created")->success()->send();

                    return redirect(OrderResource::getUrl('view', ['record' => $order]));
                }),

            Action::make('quickOrder')
                ->label('Quick Order')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->visible(fn () => $this->record->active_lead === null)
                ->url(fn () => QuickOrder::getUrl(['phone' => $this->record->phone])),

            Actions\EditAction::make(),
        ];
    }

    public function advanceLeadStage(): void
    {
        $lead = $this->record->active_lead;

        if (! $lead) {
            return;
        }

        $order   = array_keys(\App\Models\Lead::$stages);
        $current = array_search($lead->stage, $order);

        if ($current !== false && isset($order[$current + 1])) {
            $next = $order[$current + 1];
            $lead->update([
                'stage'        => $next,
                'converted_at' => $next === 'converted' ? now() : null,
            ]);
        }
    }

    public function generateAiDraft(): void
    {
        $settings = BotSetting::current();

        if (empty($settings->groq_api_key) && empty($settings->anthropic_api_key)) {
            Notification::make()->title('No AI provider configured in Bot Settings')->warning()->send();
            return;
        }

        $lastInbound = Conversation::where('contact_id', $this->record->id)
            ->where('channel', 'whatsapp')
            ->where('direction', 'inbound')
            ->latest('sent_at')
            ->first();

        if (! $lastInbound) {
            Notification::make()->title('No inbound message to reply to yet')->warning()->send();
            return;
        }

        $service = new BotReplyService($settings);
        $reply   = $service->generateReply($this->record, $lastInbound->message, $lastInbound);

        if ($reply) {
            $this->replyMessage = $reply;
        } else {
            Notification::make()->title('Could not generate a reply')->danger()->send();
        }
    }

    public function sendReply(): void
    {
        $message = trim($this->replyMessage);

        if ($message === '') {
            return;
        }

        if (empty($this->record->phone)) {
            Notification::make()->title('This contact has no phone number')->danger()->send();
            return;
        }

        $conversation = Conversation::create([
            'contact_id' => $this->record->id,
            'channel'    => 'whatsapp',
            'direction'  => 'outbound',
            'message'    => $message,
            'is_bot'     => false,
            'status'     => 'sent',
        ]);

        SendWhatsAppMessageJob::dispatch($conversation->id);

        $this->replyMessage = '';

        Notification::make()->title('Message queued for sending')->success()->send();
    }
}
