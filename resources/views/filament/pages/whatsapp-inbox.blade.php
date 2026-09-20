<x-filament-panels::page>
    <div @class(['wa-inbox', 'is-thread-open' => $mobileThreadOpen]) wire:poll.15s>
        <aside class="wa-inbox__sidebar">
            <div class="wa-inbox__sidebar-header">
                <div class="wa-inbox__eyebrow">
                    <span class="wa-inbox__live-dot"></span>
                    Live business inbox
                </div>

                <label class="wa-inbox__search">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" />
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search name, phone or message"
                        aria-label="Search WhatsApp conversations"
                    />
                </label>

                <div class="wa-inbox__filters" role="group" aria-label="Conversation filters">
                    <button type="button" wire:click="setFilter('all')" @class(['is-active' => $filter === 'all'])>
                        All <span>{{ $counts['all'] }}</span>
                    </button>
                    <button type="button" wire:click="setFilter('unread')" @class(['is-active' => $filter === 'unread'])>
                        Unread <span>{{ $counts['unread'] }}</span>
                    </button>
                    <button type="button" wire:click="setFilter('needs_reply')" @class(['is-active' => $filter === 'needs_reply'])>
                        Needs reply <span>{{ $counts['needsReply'] }}</span>
                    </button>
                </div>
            </div>

            <div class="wa-inbox__thread-list">
                @forelse ($threads as $thread)
                    <button
                        type="button"
                        wire:key="thread-{{ $thread->id }}"
                        wire:click="selectThread({{ $thread->id }})"
                        @class(['wa-inbox__thread', 'is-selected' => $selectedContactId === $thread->id])
                    >
                        <span class="wa-inbox__avatar">{{ mb_strtoupper(mb_substr($thread->name ?: 'W', 0, 1)) }}</span>
                        <span class="wa-inbox__thread-copy">
                            <span class="wa-inbox__thread-topline">
                                <strong>{{ $thread->name ?: 'WhatsApp contact' }}</strong>
                                <time>{{ $thread->last_whatsapp_at ? \Illuminate\Support\Carbon::parse($thread->last_whatsapp_at)->diffForHumans(short: true) : '' }}</time>
                            </span>
                            <span class="wa-inbox__thread-phone">+{{ ltrim($thread->phone, '+') }}</span>
                            <span class="wa-inbox__preview">
                                @if ($thread->last_direction === 'outbound')
                                    <span class="wa-inbox__sent-mark">{{ $thread->last_status === 'read' ? '✓✓' : '✓' }}</span>
                                @endif
                                {{ \Illuminate\Support\Str::limit($thread->last_message ?: 'Attachment', 52) }}
                            </span>
                        </span>
                        @if ($thread->unread_count > 0)
                            <span class="wa-inbox__unread">{{ $thread->unread_count > 99 ? '99+' : $thread->unread_count }}</span>
                        @endif
                    </button>
                @empty
                    <div class="wa-inbox__empty-list">
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" />
                        <strong>No conversations found</strong>
                        <span>New WhatsApp messages will appear here automatically.</span>
                    </div>
                @endforelse
            </div>
        </aside>

        <section class="wa-inbox__conversation">
            @if ($selectedContact)
                <header class="wa-inbox__contact-bar">
                    <button type="button" class="wa-inbox__back" wire:click="closeThread" aria-label="Back to conversations">
                        <x-filament::icon icon="heroicon-o-arrow-left" />
                    </button>
                    <div class="wa-inbox__contact-identity">
                        <span class="wa-inbox__avatar wa-inbox__avatar--large">{{ mb_strtoupper(mb_substr($selectedContact->name ?: 'W', 0, 1)) }}</span>
                        <div>
                            <h2>{{ $selectedContact->name ?: 'WhatsApp contact' }}</h2>
                            <p>+{{ ltrim($selectedContact->phone, '+') }} · {{ \App\Models\Contact::SOURCE_LABELS[$selectedContact->source] ?? ucfirst(str_replace('_', ' ', $selectedContact->source ?? 'WhatsApp')) }}</p>
                        </div>
                    </div>
                    <div class="wa-inbox__contact-actions">
                        @if ($selectedContact->wa_opted_out)
                            <span class="wa-inbox__warning">Opted out</span>
                        @endif
                        <a href="{{ $selectedContact->whatsapp_url }}" target="_blank" rel="noopener" title="Open in WhatsApp">
                            <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" />
                            Open WhatsApp
                        </a>
                        <a href="{{ \App\Filament\Resources\ContactResource::getUrl('edit', ['record' => $selectedContact]) }}" title="Open contact">
                            <x-filament::icon icon="heroicon-o-user" />
                            Contact
                        </a>
                    </div>
                </header>

                <div
                    class="wa-inbox__messages"
                    id="whatsapp-thread"
                    x-data
                    x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                    x-on:scroll-chat-to-bottom.window="$nextTick(() => $el.scrollTo({ top: $el.scrollHeight, behavior: 'smooth' }))"
                >
                    <div class="wa-inbox__encryption-note">
                        <x-filament::icon icon="heroicon-o-lock-closed" />
                        Messages captured through the connected Meta WhatsApp Business number
                    </div>

                    @foreach ($messages as $message)
                        <article wire:key="message-{{ $message->id }}" @class([
                            'wa-inbox__message-row',
                            'is-outbound' => $message->direction === 'outbound',
                        ])>
                            <div @class([
                                'wa-inbox__bubble',
                                'is-bot' => $message->direction === 'outbound' && $message->is_bot,
                                'is-human' => $message->direction === 'outbound' && ! $message->is_bot,
                            ])>
                                @if ($message->ctwa_referral)
                                    <div class="wa-inbox__referral">
                                        <x-filament::icon icon="heroicon-o-megaphone" />
                                        <div>
                                            <strong>Started from a Meta ad</strong>
                                            <span>{{ $message->ctwa_referral['headline'] ?? $message->ctwa_referral['body'] ?? 'Click-to-WhatsApp ad' }}</span>
                                        </div>
                                    </div>
                                @endif

                                @if ($message->media_url)
                                    <a class="wa-inbox__media" href="{{ $message->media_url }}" target="_blank" rel="noopener">
                                        <img src="{{ $message->media_url }}" alt="WhatsApp attachment" />
                                    </a>
                                @endif

                                <div class="wa-inbox__message-text">{!! nl2br(e($message->message)) !!}</div>
                                <footer>
                                    @if ($message->direction === 'outbound')
                                        <span>{{ $message->is_bot ? 'Bot' : ($message->handledBy?->name ?? 'Team') }}</span>
                                    @endif
                                    <time>{{ ($message->sent_at ?? $message->created_at)->format('d M, g:i A') }}</time>
                                    @if ($message->direction === 'outbound')
                                        <span @class(['wa-inbox__delivery', 'is-read' => $message->status === 'read', 'is-failed' => $message->status === 'failed']) title="{{ ucfirst($message->status) }}">
                                            {{ $message->status === 'read' ? '✓✓' : ($message->status === 'failed' ? '!' : '✓') }}
                                        </span>
                                    @endif
                                </footer>
                            </div>
                        </article>
                    @endforeach
                </div>

                <form class="wa-inbox__composer" wire:submit="sendReply">
                    <div class="wa-inbox__composer-field">
                        <textarea
                            wire:model="replyText"
                            rows="2"
                            maxlength="4096"
                            placeholder="Type a WhatsApp reply…"
                            @disabled($selectedContact->wa_opted_out || $selectedContact->is_blocked)
                        ></textarea>
                        @error('replyText') <span class="wa-inbox__field-error">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled" @disabled($selectedContact->wa_opted_out || $selectedContact->is_blocked)>
                        <x-filament::icon icon="heroicon-o-paper-airplane" />
                        <span>Send</span>
                    </button>
                    <p>Free-form replies are subject to Meta's 24-hour customer service window.</p>
                </form>
            @else
                <div class="wa-inbox__blank-state">
                    <span><x-filament::icon icon="heroicon-o-chat-bubble-oval-left-ellipsis" /></span>
                    <h2>Your WhatsApp conversations, together</h2>
                    <p>Select a thread to see incoming messages, bot replies, campaign messages and team responses in one timeline.</p>
                </div>
            @endif
        </section>
    </div>

    <style>
        .fi-main:has(.wa-inbox) { max-width: none; }
        .fi-page:has(.wa-inbox), .fi-page-main:has(.wa-inbox), .fi-page-content:has(.wa-inbox) { min-height: 0; }
        .wa-inbox { display: grid; grid-template-columns: clamp(19rem, 26vw, 23rem) minmax(0, 1fr); width: 100%; height: calc(100dvh - 10.5rem); min-height: 32rem; overflow: hidden; border: 1px solid #e7e5e4; border-radius: .9rem; background: #fff; box-shadow: 0 10px 24px rgba(28, 25, 23, .06); }
        .wa-inbox__sidebar { display: flex; min-width: 0; min-height: 0; flex-direction: column; overflow: hidden; border-right: 1px solid #e7e5e4; background: #fafaf9; }
        .wa-inbox__sidebar-header { padding: 1rem; border-bottom: 1px solid #e7e5e4; background: rgba(255, 255, 255, .8); }
        .wa-inbox__eyebrow { display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem; color: #57534e; font-size: .7rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .wa-inbox__live-dot { width: .5rem; height: .5rem; border-radius: 999px; background: #16a34a; box-shadow: 0 0 0 4px #dcfce7; }
        .wa-inbox__search { display: flex; align-items: center; gap: .6rem; padding: .65rem .75rem; border: 1px solid #d6d3d1; border-radius: .75rem; background: #fff; color: #78716c; transition: border-color .15s, box-shadow .15s; }
        .wa-inbox__search:focus-within { border-color: #f59e0b; box-shadow: 0 0 0 3px #fef3c7; }
        .wa-inbox__search svg { width: 1.05rem; flex: none; }
        .wa-inbox__search input { width: 100%; border: 0; outline: 0; background: transparent; color: #1c1917; font-size: .8rem; }
        .wa-inbox__filters { display: flex; gap: .35rem; margin-top: .75rem; overflow-x: auto; }
        .wa-inbox__filters button { display: flex; flex: none; align-items: center; gap: .35rem; padding: .4rem .55rem; border-radius: .55rem; color: #78716c; font-size: .7rem; font-weight: 700; }
        .wa-inbox__filters button:hover, .wa-inbox__filters button.is-active { background: #fef3c7; color: #92400e; }
        .wa-inbox__filters span { min-width: 1.1rem; padding: .08rem .28rem; border-radius: 999px; background: rgba(255,255,255,.8); text-align: center; font-size: .62rem; }
        .wa-inbox__thread-list { min-height: 0; flex: 1 1 0; overflow-y: auto; overscroll-behavior: contain; scrollbar-gutter: stable; }
        .wa-inbox__thread { position: relative; display: grid; width: 100%; grid-template-columns: 2.5rem minmax(0, 1fr); gap: .7rem; padding: .85rem 1rem; border-bottom: 1px solid #eceae8; text-align: left; transition: background .15s; }
        .wa-inbox__thread:hover { background: #fff; }
        .wa-inbox__thread.is-selected { background: #fff; box-shadow: inset 3px 0 #16a34a; }
        .wa-inbox__avatar { display: grid; width: 2.5rem; height: 2.5rem; place-items: center; border-radius: .8rem; background: linear-gradient(145deg, #166534, #16a34a); color: #fff; font-size: .85rem; font-weight: 800; box-shadow: inset 0 0 0 1px rgba(255,255,255,.18); }
        .wa-inbox__avatar--large { width: 2.8rem; height: 2.8rem; border-radius: .9rem; }
        .wa-inbox__thread-copy { min-width: 0; }
        .wa-inbox__thread-topline { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; }
        .wa-inbox__thread-topline strong { overflow: hidden; color: #292524; font-size: .79rem; text-overflow: ellipsis; white-space: nowrap; }
        .wa-inbox__thread-topline time { flex: none; color: #a8a29e; font-size: .62rem; }
        .wa-inbox__thread-phone { display: block; margin-top: .05rem; color: #a8a29e; font-size: .65rem; }
        .wa-inbox__preview { display: block; overflow: hidden; margin-top: .22rem; color: #78716c; font-size: .72rem; text-overflow: ellipsis; white-space: nowrap; }
        .wa-inbox__sent-mark { color: #0ea5e9; font-weight: 800; }
        .wa-inbox__unread { position: absolute; right: 1rem; bottom: .75rem; display: grid; min-width: 1.25rem; height: 1.25rem; padding: 0 .3rem; place-items: center; border-radius: 999px; background: #16a34a; color: #fff; font-size: .6rem; font-weight: 800; }
        .wa-inbox__conversation { display: flex; min-width: 0; min-height: 0; flex-direction: column; overflow: hidden; background-color: #f5f1ea; background-image: radial-gradient(rgba(120,113,108,.10) .65px, transparent .65px); background-size: 12px 12px; }
        .wa-inbox__contact-bar { display: flex; flex: 0 0 auto; align-items: center; justify-content: space-between; gap: 1rem; padding: .72rem 1rem; border-bottom: 1px solid #e7e5e4; background: rgba(255,255,255,.98); }
        .wa-inbox__back { display: none; width: 2rem; height: 2rem; flex: none; place-items: center; border-radius: .55rem; color: #57534e; }
        .wa-inbox__back:hover { background: #f5f5f4; color: #166534; }
        .wa-inbox__back svg { width: 1.05rem; }
        .wa-inbox__contact-identity { display: flex; min-width: 0; align-items: center; gap: .75rem; }
        .wa-inbox__contact-identity h2 { overflow: hidden; color: #1c1917; font-size: .9rem; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
        .wa-inbox__contact-identity p { color: #78716c; font-size: .68rem; }
        .wa-inbox__contact-actions { display: flex; align-items: center; gap: .45rem; }
        .wa-inbox__contact-actions a { display: flex; align-items: center; gap: .35rem; padding: .48rem .65rem; border: 1px solid #d6d3d1; border-radius: .6rem; background: #fff; color: #57534e; font-size: .68rem; font-weight: 700; }
        .wa-inbox__contact-actions a:hover { border-color: #16a34a; color: #166534; }
        .wa-inbox__contact-actions svg { width: .9rem; }
        .wa-inbox__warning { padding: .3rem .5rem; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: .65rem; font-weight: 800; }
        .wa-inbox__messages { min-height: 0; flex: 1 1 0; overflow-x: hidden; overflow-y: scroll; overscroll-behavior: contain; scrollbar-gutter: stable; padding: 1.1rem clamp(.8rem, 3vw, 2.25rem); scroll-behavior: smooth; }
        .wa-inbox__encryption-note { display: flex; width: fit-content; align-items: center; gap: .35rem; margin: 0 auto 1.2rem; padding: .38rem .65rem; border-radius: .5rem; background: #fffbeb; color: #92400e; font-size: .62rem; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .wa-inbox__encryption-note svg { width: .75rem; }
        .wa-inbox__message-row { display: flex; margin: .42rem 0; justify-content: flex-start; }
        .wa-inbox__message-row.is-outbound { justify-content: flex-end; }
        .wa-inbox__bubble { max-width: min(78%, 42rem); padding: .65rem .75rem .42rem; border-radius: .25rem .85rem .85rem .85rem; background: #fff; color: #292524; box-shadow: 0 1px 2px rgba(28,25,23,.12); }
        .wa-inbox__message-row.is-outbound .wa-inbox__bubble { border-radius: .85rem .25rem .85rem .85rem; background: #dcfce7; }
        .wa-inbox__message-row.is-outbound .wa-inbox__bubble.is-bot { background: #fef3c7; }
        .wa-inbox__message-text { overflow-wrap: anywhere; font-size: .8rem; line-height: 1.5; }
        .wa-inbox__bubble footer { display: flex; align-items: center; justify-content: flex-end; gap: .35rem; margin-top: .3rem; color: #78716c; font-size: .58rem; }
        .wa-inbox__delivery { font-size: .67rem; font-weight: 900; }
        .wa-inbox__delivery.is-read { color: #0284c7; }
        .wa-inbox__delivery.is-failed { color: #dc2626; }
        .wa-inbox__referral { display: flex; gap: .55rem; margin: -.25rem -.3rem .65rem; padding: .6rem; border-left: 3px solid #f59e0b; border-radius: .45rem; background: #fffbeb; color: #78350f; }
        .wa-inbox__referral svg { width: 1rem; flex: none; }
        .wa-inbox__referral strong, .wa-inbox__referral span { display: block; font-size: .66rem; }
        .wa-inbox__referral span { margin-top: .12rem; color: #a16207; }
        .wa-inbox__media { display: block; margin-bottom: .55rem; }
        .wa-inbox__media img { max-height: 18rem; border-radius: .55rem; object-fit: cover; }
        .wa-inbox__composer { display: grid; flex: 0 0 auto; grid-template-columns: minmax(0, 1fr) auto; gap: .55rem; padding: .7rem .9rem; border-top: 1px solid #e7e5e4; background: #fff; }
        .wa-inbox__composer-field textarea { display: block; width: 100%; resize: none; border: 1px solid #d6d3d1; border-radius: .75rem; padding: .65rem .8rem; outline: 0; color: #292524; font-size: .8rem; }
        .wa-inbox__composer-field textarea:focus { border-color: #16a34a; box-shadow: 0 0 0 3px #dcfce7; }
        .wa-inbox__composer button { display: flex; align-items: center; align-self: start; gap: .4rem; padding: .67rem .85rem; border-radius: .7rem; background: #166534; color: #fff; font-size: .75rem; font-weight: 800; box-shadow: 0 2px 5px rgba(22,101,52,.22); }
        .wa-inbox__composer button:hover { background: #15803d; }
        .wa-inbox__composer button:disabled { cursor: not-allowed; opacity: .45; }
        .wa-inbox__composer button svg { width: .95rem; }
        .wa-inbox__composer > p { grid-column: 1 / -1; margin: -.18rem 0 0 .15rem; color: #a8a29e; font-size: .6rem; }
        .wa-inbox__field-error { display: block; margin: .25rem 0 0 .2rem; color: #dc2626; font-size: .65rem; }
        .wa-inbox__empty-list, .wa-inbox__blank-state { display: flex; flex-direction: column; align-items: center; justify-content: center; color: #78716c; text-align: center; }
        .wa-inbox__empty-list { min-height: 15rem; padding: 2rem; }
        .wa-inbox__empty-list svg { width: 2rem; margin-bottom: .65rem; color: #a8a29e; }
        .wa-inbox__empty-list strong { color: #44403c; font-size: .8rem; }
        .wa-inbox__empty-list span { max-width: 15rem; margin-top: .3rem; font-size: .7rem; }
        .wa-inbox__blank-state { height: 100%; padding: 3rem; }
        .wa-inbox__blank-state > span { display: grid; width: 4rem; height: 4rem; place-items: center; border-radius: 1.2rem; background: #dcfce7; color: #166534; }
        .wa-inbox__blank-state svg { width: 2rem; }
        .wa-inbox__blank-state h2 { margin-top: 1rem; color: #292524; font-size: 1rem; font-weight: 800; }
        .wa-inbox__blank-state p { max-width: 28rem; margin-top: .4rem; font-size: .78rem; line-height: 1.6; }
        @media (max-width: 900px) {
            .wa-inbox { grid-template-columns: 18rem minmax(0, 1fr); }
            .wa-inbox__contact-actions a { padding: .5rem; }
            .wa-inbox__contact-actions a { font-size: 0; }
            .wa-inbox__contact-actions a svg { width: 1rem; }
        }
        @media (max-width: 640px) {
            .fi-header { margin-bottom: .75rem; }
            .wa-inbox { display: block; height: calc(100dvh - 9.5rem); min-height: 31rem; }
            .wa-inbox__sidebar { height: 100%; border: 0; }
            .wa-inbox__conversation { display: none; height: 100%; }
            .wa-inbox.is-thread-open .wa-inbox__sidebar { display: none; }
            .wa-inbox.is-thread-open .wa-inbox__conversation { display: flex; }
            .wa-inbox__back { display: grid; }
            .wa-inbox__contact-bar { gap: .55rem; padding: .65rem .7rem; }
            .wa-inbox__contact-identity { gap: .55rem; }
            .wa-inbox__avatar--large { width: 2.35rem; height: 2.35rem; border-radius: .7rem; }
            .wa-inbox__contact-actions { gap: .25rem; }
            .wa-inbox__contact-actions a { border: 0; background: #f5f5f4; }
            .wa-inbox__messages { padding: .8rem .65rem; }
            .wa-inbox__bubble { max-width: 90%; }
            .wa-inbox__composer { padding: .6rem; }
            .wa-inbox__composer > p { display: none; }
        }
    </style>
</x-filament-panels::page>
