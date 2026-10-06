<?php

namespace App\Filament\Pages;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Contact;
use App\Models\BotSetting;
use App\Models\Conversation;
use App\Models\User;
use App\Models\WhatsAppConsent;
use App\Models\WhatsAppSavedReply;
use App\Services\WhatsAppMessagePolicy;
use App\Services\WhatsAppService;
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

    public string $consentCategory = 'utility';

    public string $consentSource = '';

    public string $consentEvidence = '';

    public array $approvedTemplates = [];

    public string $selectedTemplate = '';

    public string $assignmentId = '';

    public string $internalNote = '';

    public string $savedReplyTitle = '';

    public string $savedReplyBody = '';

    public function mount(): void
    {
        $this->selectedContactId = $this->threadQuery()->value('contacts.id');

        if ($this->selectedContactId) {
            $this->assignmentId = (string) (Contact::find($this->selectedContactId)?->assigned_to ?? '');
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
        abort_unless(in_array($filter, ['all', 'unread', 'needs_reply', 'mine'], true), 404);

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
        $this->selectedTemplate = '';
        $this->assignmentId = (string) (Contact::find($contactId)?->assigned_to ?? '');
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

        abort_unless($contact->conversations()->where('channel', 'whatsapp')->exists(), 404);

        if ($contact->is_blocked || $contact->wa_opted_out) {
            Notification::make()
                ->title('Message not sent')
                ->body('This contact is blocked or has opted out of WhatsApp messages.')
                ->danger()
                ->send();

            return;
        }

        if (! app(WhatsAppMessagePolicy::class)->canSendFreeform($contact)) {
            Notification::make()
                ->title('Reply window closed')
                ->body('A free-form reply needs a customer message within the last 24 hours. Use an approved template after the window closes.')
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

    public function recordConsent(): void
    {
        $this->consentSource = trim($this->consentSource);
        $this->consentEvidence = trim($this->consentEvidence);
        $data = $this->validate([
            'consentCategory' => ['required', 'in:utility,marketing'],
            'consentSource' => ['required', 'string', 'max:80'],
            'consentEvidence' => ['required', 'string', 'max:2000'],
        ]);

        $contact = Contact::findOrFail($this->selectedContactId);
        abort_unless($contact->conversations()->where('channel', 'whatsapp')->exists(), 404);

        if ($contact->is_blocked || $contact->wa_opted_out) {
            Notification::make()->title('Consent cannot be recorded for a blocked or opted-out contact')->danger()->send();
            return;
        }

        $contact->whatsAppConsents()
            ->where('category', $data['consentCategory'])
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        WhatsAppConsent::create([
            'contact_id' => $contact->id,
            'recorded_by' => auth()->id(),
            'category' => $data['consentCategory'],
            'source' => trim($data['consentSource']),
            'evidence' => trim($data['consentEvidence']),
            'granted_at' => now(),
        ]);

        $this->consentSource = '';
        $this->consentEvidence = '';
        Notification::make()->title('Consent evidence recorded')->success()->send();
    }

    public function loadApprovedTemplates(): void
    {
        $this->approvedTemplates = (new WhatsAppService(BotSetting::current()))->approvedInboxTemplates();
        Notification::make()
            ->title(count($this->approvedTemplates).' approved text templates available')
            ->body('Only templates without variables, media, or buttons are shown here.')
            ->send();
    }

    public function sendApprovedTemplate(): void
    {
        $this->validate(['selectedTemplate' => ['required', 'string', 'max:200']]);
        $contact = Contact::findOrFail($this->selectedContactId);
        abort_unless($contact->conversations()->where('channel', 'whatsapp')->exists(), 404);

        $service = new WhatsAppService(BotSetting::current());
        $template = collect($service->approvedInboxTemplates())
            ->first(fn ($item) => $item['name'].'|'.$item['language'] === $this->selectedTemplate);

        if (! $template || ! app(WhatsAppMessagePolicy::class)->hasOutreachConsent($contact, $template['category'])) {
            Notification::make()
                ->title('Template not sent')
                ->body('The template must still be approved by Meta and the customer must have active consent for its category.')
                ->danger()->send();
            return;
        }

        $waMessageId = $service->sendTemplateMessage($contact->phone, $template['name'], [], $template['language']);
        if (! $waMessageId) {
            Notification::make()->title('Meta did not accept this template message')->danger()->send();
            return;
        }

        Conversation::create([
            'contact_id' => $contact->id,
            'handled_by' => auth()->id(),
            'channel' => 'whatsapp',
            'direction' => 'outbound',
            'message' => $template['body'],
            'wa_message_id' => $waMessageId,
            'sent_at' => now(),
            'status' => 'sent',
            'is_bot' => false,
        ]);
        $contact->update(['last_contacted_at' => now()]);
        $this->dispatch('scroll-chat-to-bottom');
        Notification::make()->title('Approved template sent')->success()->send();
    }

    public function assignSelectedContact(): void
    {
        $data = $this->validate(['assignmentId' => ['nullable', 'integer', 'exists:users,id']]);
        $contact = $this->selectedThreadContact();
        $contact->update(['assigned_to' => $data['assignmentId'] === '' ? null : (int) $data['assignmentId']]);
        Notification::make()->title('Assignment updated')->success()->send();
    }

    public function setInboxStatus(string $status): void
    {
        abort_unless(in_array($status, ['open', 'pending', 'resolved'], true), 422);
        $this->selectedThreadContact()->update(['whatsapp_inbox_status' => $status]);
        Notification::make()->title('Inbox status updated')->success()->send();
    }

    public function addInternalNote(): void
    {
        $this->internalNote = trim($this->internalNote);
        $data = $this->validate(['internalNote' => ['required', 'string', 'max:2000']]);
        $this->selectedThreadContact()->whatsAppInboxNotes()->create([
            'user_id' => auth()->id(),
            'body' => trim($data['internalNote']),
        ]);
        $this->internalNote = '';
        Notification::make()->title('Private note saved')->success()->send();
    }

    public function saveQuickReply(): void
    {
        $this->savedReplyTitle = trim($this->savedReplyTitle);
        $this->savedReplyBody = trim($this->savedReplyBody);
        $data = $this->validate([
            'savedReplyTitle' => ['required', 'string', 'max:80'],
            'savedReplyBody' => ['required', 'string', 'max:4096'],
        ]);
        WhatsAppSavedReply::create([
            'title' => trim($data['savedReplyTitle']),
            'body' => trim($data['savedReplyBody']),
            'created_by' => auth()->id(),
        ]);
        $this->savedReplyTitle = '';
        $this->savedReplyBody = '';
        Notification::make()->title('Saved reply added')->success()->send();
    }

    public function useSavedReply(int $id): void
    {
        $this->selectedThreadContact();
        $this->replyText = WhatsAppSavedReply::findOrFail($id)->body;
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
            'replyWindowExpiresAt' => $selectedContact ? app(WhatsAppMessagePolicy::class)->freeformExpiresAt($selectedContact) : null,
            'canSendFreeform' => $selectedContact ? app(WhatsAppMessagePolicy::class)->canSendFreeform($selectedContact) : false,
            'activeConsents' => $selectedContact ? $selectedContact->whatsAppConsents()->whereNull('revoked_at')->latest()->get() : collect(),
            'internalNotes' => $selectedContact ? $selectedContact->whatsAppInboxNotes()->with('user')->latest()->limit(10)->get() : collect(),
            'agents' => User::query()->orderBy('name')->get(['id', 'name']),
            'savedReplies' => WhatsAppSavedReply::query()->orderBy('title')->limit(100)->get(),
        ];
    }

    private function selectedThreadContact(): Contact
    {
        $contact = Contact::findOrFail($this->selectedContactId);
        abort_unless($contact->conversations()->where('channel', 'whatsapp')->exists(), 404);
        return $contact;
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

        if ($this->filter === 'mine') {
            $query->where('assigned_to', auth()->id());
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
        $this->assignmentId = (string) (Contact::find($this->selectedContactId)?->assigned_to ?? '');
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
