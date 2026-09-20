<?php

namespace App\Filament\Pages;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\Conversation;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class WhatsAppInbox extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales & CRM';

    protected static ?string $navigationLabel = 'WhatsApp Inbox';

    protected static ?string $title = 'WhatsApp Inbox';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'whatsapp-inbox';

    protected string $view = 'filament.pages.whatsapp-inbox';

    public ?int $selectedContactId = null;

    public string $search = '';

    public string $filter = 'all';

    public string $replyText = '';

    public bool $mobileThreadOpen = false;

    public function mount(): void
    {
        $this->selectedContactId = $this->threadQuery()->value('contacts.id');

        if ($this->selectedContactId) {
            $this->markSelectedThreadSeen();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Conversation::query()
            ->where('channel', 'whatsapp')
            ->where('direction', 'inbound')
            ->whereNull('seen_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public function updatedSearch(): void
    {
        $this->selectFirstVisibleThread();
    }

    public function setFilter(string $filter): void
    {
        abort_unless(in_array($filter, ['all', 'unread', 'needs_reply'], true), 404);

        $this->filter = $filter;
        $this->selectFirstVisibleThread();
    }

    public function selectThread(int $contactId): void
    {
        abort_unless(
            Conversation::where('contact_id', $contactId)->where('channel', 'whatsapp')->exists(),
            404,
        );

        $this->selectedContactId = $contactId;
        $this->mobileThreadOpen = true;
        $this->replyText = '';
        $this->markSelectedThreadSeen();
        $this->dispatch('scroll-chat-to-bottom');
    }

    public function closeThread(): void
    {
        $this->mobileThreadOpen = false;
    }

    public function sendReply(): void
    {
        $data = $this->validate([
            'replyText' => ['required', 'string', 'max:4096'],
        ]);

        $contact = Contact::findOrFail($this->selectedContactId);

        if ($contact->is_blocked || $contact->wa_opted_out) {
            Notification::make()
                ->title('Message not sent')
                ->body('This contact is blocked or has opted out of WhatsApp messages.')
                ->danger()
                ->send();

            return;
        }

        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'handled_by' => auth()->id(),
            'channel' => 'whatsapp',
            'direction' => 'outbound',
            'message' => trim($data['replyText']),
            'status' => 'sent',
            'is_bot' => false,
        ]);

        $contact->update(['last_contacted_at' => now()]);
        SendWhatsAppMessageJob::dispatch($conversation->id);

        $this->replyText = '';
        $this->dispatch('scroll-chat-to-bottom');

        Notification::make()
            ->title('Reply queued')
            ->body('The message will appear here as soon as Meta accepts it.')
            ->success()
            ->send();
    }

    public function getViewData(): array
    {
        $threads = $this->threadQuery()->limit(100)->get();
        $selectedContact = $this->selectedContactId
            ? Contact::with('assignedTo')->find($this->selectedContactId)
            : null;

        $messages = collect();

        if ($selectedContact) {
            $messages = Conversation::with('handledBy')
                ->where('contact_id', $selectedContact->id)
                ->where('channel', 'whatsapp')
                ->latest('created_at')
                ->limit(200)
                ->get()
                ->reverse()
                ->values();
        }

        return [
            'threads' => $threads,
            'selectedContact' => $selectedContact,
            'messages' => $messages,
            'counts' => $this->filterCounts(),
        ];
    }

    private function threadQuery(): Builder
    {
        $latestMessage = fn (string $column) => Conversation::query()
            ->select($column)
            ->whereColumn('conversations.contact_id', 'contacts.id')
            ->where('channel', 'whatsapp')
            ->latest('created_at')
            ->limit(1);

        $query = Contact::query()
            ->whereHas('conversations', fn (Builder $query) => $query->where('channel', 'whatsapp'))
            ->addSelect([
                'last_whatsapp_at' => $latestMessage('created_at'),
                'last_message' => $latestMessage('message'),
                'last_direction' => $latestMessage('direction'),
                'last_status' => $latestMessage('status'),
                'last_is_bot' => $latestMessage('is_bot'),
            ])
            ->withCount([
                'conversations as unread_count' => fn (Builder $query) => $query
                    ->where('channel', 'whatsapp')
                    ->where('direction', 'inbound')
                    ->whereNull('seen_at'),
            ])
            ->orderByDesc('last_whatsapp_at');

        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function (Builder $query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhereHas('conversations', fn (Builder $messages) => $messages
                        ->where('channel', 'whatsapp')
                        ->where('message', 'like', $term));
            });
        }

        if ($this->filter === 'unread') {
            $query->whereHas('conversations', fn (Builder $messages) => $messages
                ->where('channel', 'whatsapp')
                ->where('direction', 'inbound')
                ->whereNull('seen_at'));
        }

        if ($this->filter === 'needs_reply') {
            $query->whereRaw("(select direction from conversations where conversations.contact_id = contacts.id and channel = 'whatsapp' order by created_at desc limit 1) = 'inbound'");
        }

        return $query;
    }

    private function filterCounts(): array
    {
        $all = Contact::whereHas('conversations', fn (Builder $query) => $query->where('channel', 'whatsapp'))->count();
        $unread = Contact::whereHas('conversations', fn (Builder $query) => $query
            ->where('channel', 'whatsapp')
            ->where('direction', 'inbound')
            ->whereNull('seen_at'))->count();
        $needsReply = Contact::whereHas('conversations', fn (Builder $query) => $query->where('channel', 'whatsapp'))
            ->whereRaw("(select direction from conversations where conversations.contact_id = contacts.id and channel = 'whatsapp' order by created_at desc limit 1) = 'inbound'")
            ->count();

        return compact('all', 'unread', 'needsReply');
    }

    private function selectFirstVisibleThread(): void
    {
        $this->selectedContactId = $this->threadQuery()->value('contacts.id');
        $this->markSelectedThreadSeen();
    }

    private function markSelectedThreadSeen(): void
    {
        if (! $this->selectedContactId) {
            return;
        }

        Conversation::where('contact_id', $this->selectedContactId)
            ->where('channel', 'whatsapp')
            ->where('direction', 'inbound')
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);
    }
}
