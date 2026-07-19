<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversationResource\Pages;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\BotSetting;
use App\Models\Conversation;
use App\Models\User;
use App\Services\AiProviderService;
use App\Services\BotReplyService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|\UnitEnum|null $navigationGroup = 'Sales & CRM';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make()->schema([
                Forms\Components\Select::make('contact_id')
                    ->label('Contact')
                    ->relationship('contact', 'name')
                    ->searchable()->preload()->required(),

                Forms\Components\Select::make('channel')
                    ->options([
                        'whatsapp'  => 'WhatsApp',
                        'facebook'  => 'Facebook',
                        'instagram' => 'Instagram',
                        'phone'     => 'Phone',
                        'email'     => 'Email',
                        'other'     => 'Other',
                    ])
                    ->default('whatsapp')->required(),

                Forms\Components\Select::make('direction')
                    ->options(['inbound' => 'Inbound', 'outbound' => 'Outbound'])
                    ->default('outbound')->required(),

                Forms\Components\Select::make('status')
                    ->options(['sent' => 'Sent', 'delivered' => 'Delivered', 'read' => 'Read', 'failed' => 'Failed'])
                    ->default('sent'),

                Forms\Components\Select::make('handled_by')
                    ->label('Handled By')
                    ->options(User::pluck('name', 'id'))
                    ->nullable(),

                Forms\Components\Toggle::make('is_bot')->label('Bot message'),

                Forms\Components\DateTimePicker::make('sent_at')->label('Sent At')->nullable(),

                Forms\Components\Textarea::make('message')
                    ->required()->rows(4)->columnSpanFull(),

                Forms\Components\TextInput::make('media_url')
                    ->label('Media URL')->url()->nullable()->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make('Message')->schema([
                TextEntry::make('contact.name')->label('Contact'),
                TextEntry::make('contact.phone')->label('Phone'),
                TextEntry::make('channel')->badge()
                    ->color(fn ($state) => match ($state) {
                        'whatsapp' => 'success', 'facebook' => 'info',
                        'instagram' => 'warning', default => 'gray',
                    }),
                TextEntry::make('direction')->badge()
                    ->color(fn ($state) => $state === 'inbound' ? 'info' : 'success'),
                TextEntry::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'read' => 'success', 'delivered' => 'info',
                        'failed' => 'danger', default => 'gray',
                    }),
                TextEntry::make('sent_at')->dateTime('d M Y, h:i A')->placeholder('Not sent yet'),
                TextEntry::make('wa_message_id')->label('WA Message ID')->placeholder('—'),
                TextEntry::make('message')->columnSpanFull(),
            ])->columns(3),

            SchemaSection::make('Click-to-WhatsApp Ad Source')
                ->visible(fn ($record) => ! empty($record->ctwa_referral))
                ->schema([
                    TextEntry::make('ctwa_referral.headline')
                        ->label('Ad Headline')->placeholder('—'),
                    TextEntry::make('ctwa_referral.source_type')
                        ->label('Source Type')->badge()->color('info'),
                    TextEntry::make('ctwa_referral.media_type')
                        ->label('Media Type')->placeholder('—'),
                    TextEntry::make('ctwa_referral.body')
                        ->label('Ad Body')->columnSpanFull()->placeholder('—'),
                    TextEntry::make('ctwa_referral.source_url')
                        ->label('Ad URL')->columnSpanFull()->placeholder('—')
                        ->url(fn ($record) => $record->ctwa_referral['source_url'] ?? null),
                    TextEntry::make('ctwa_referral.ctwa_clid')
                        ->label('Click ID (ctwa_clid)')->placeholder('—'),
                    TextEntry::make('ctwa_referral.source_id')
                        ->label('Ad ID')->placeholder('—'),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contact.name')
                    ->searchable()->sortable()
                    ->description(fn (Conversation $r) => $r->contact?->phone),

                Tables\Columns\TextColumn::make('channel')->badge()
                    ->color(fn ($state) => match ($state) {
                        'whatsapp'  => 'success',
                        'facebook'  => 'info',
                        'instagram' => 'warning',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('direction')->badge()
                    ->color(fn ($state) => $state === 'inbound' ? 'info' : 'success'),

                Tables\Columns\TextColumn::make('message')
                    ->limit(60)->searchable(),

                Tables\Columns\IconColumn::make('is_bot')->boolean()->label('Bot'),

                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'read'      => 'success',
                        'delivered' => 'info',
                        'failed'    => 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()->label('Sent')
                    ->placeholder('Draft'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook',
                        'instagram' => 'Instagram', 'phone' => 'Phone', 'email' => 'Email',
                    ]),
                Tables\Filters\SelectFilter::make('direction')
                    ->options(['inbound' => 'Inbound', 'outbound' => 'Outbound']),
                Tables\Filters\TernaryFilter::make('is_bot')->label('Bot'),
                Tables\Filters\Filter::make('drafts')
                    ->label('Unsent Drafts')
                    ->query(fn ($query) => $query->where('is_bot', true)->whereNull('sent_at')),
            ])
            ->actions([
                Action::make('aiReply')
                    ->label('AI Reply')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->visible(fn (Conversation $r) => $r->direction === 'inbound' && $r->channel === 'whatsapp')
                    ->fillForm(function (Conversation $r): array {
                        $settings = BotSetting::current();
                        $ai       = new AiProviderService($settings);
                        if (! $ai->isConfigured()) {
                            return ['reply_text' => "No {$ai->providerLabel()} API key configured. Go to Bot Settings to add one.", 'send_now' => false];
                        }
                        $service = new BotReplyService($settings);
                        $reply   = $service->generateReply($r->contact, $r->message, $r);
                        return [
                            'reply_text' => $reply ?? 'Could not generate a reply. Please write manually.',
                            'send_now'   => false,
                        ];
                    })
                    ->form([
                        Forms\Components\Textarea::make('reply_text')
                            ->label('AI Generated Reply')
                            ->rows(5)
                            ->required(),
                        Forms\Components\Toggle::make('send_now')
                            ->label('Send immediately via WhatsApp')
                            ->helperText('Off = save as draft for review')
                            ->default(false)
                            ->onColor('success'),
                    ])
                    ->modalHeading('AI Reply')
                    ->modalDescription(fn (Conversation $r) => "Reply to {$r->contact?->name} ({$r->contact?->phone})")
                    ->modalSubmitActionLabel('Save Draft')
                    ->action(function (Conversation $r, array $data) {
                        $draft = Conversation::create([
                            'contact_id'    => $r->contact_id,
                            'channel'       => $r->channel,
                            'direction'     => 'outbound',
                            'message'       => $data['reply_text'],
                            'is_bot'        => true,
                            'replied_to_id' => $r->id,
                            'status'        => 'sent',
                        ]);

                        if ($data['send_now'] ?? false) {
                            SendWhatsAppMessageJob::dispatch($draft->id);
                            Notification::make()->title('Reply sent via WhatsApp')->success()->send();
                        } else {
                            Notification::make()->title('Reply saved as draft')->success()->send();
                        }
                    }),

                Action::make('send')
                    ->label('Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (Conversation $r) => $r->is_bot && is_null($r->sent_at))
                    ->requiresConfirmation()
                    ->modalHeading('Send WhatsApp Message')
                    ->modalDescription(fn (Conversation $r) => "Send this message to {$r->contact?->name} ({$r->contact?->phone})?")
                    ->action(fn (Conversation $r) => SendWhatsAppMessageJob::dispatch($r->id)),

                Action::make('thread')
                    ->label('')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('gray')
                    ->url(fn (Conversation $r) => static::getUrl('view', ['record' => $r])),

                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('sendAll')
                        ->label('Send Selected Drafts')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records
                            ->filter(fn ($r) => $r->is_bot && is_null($r->sent_at))
                            ->each(fn ($r) => SendWhatsAppMessageJob::dispatch($r->id))),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListConversations::route('/'),
            'create' => Pages\CreateConversation::route('/create'),
            'view'   => Pages\ViewConversation::route('/{record}'),
            'edit'   => Pages\EditConversation::route('/{record}/edit'),
        ];
    }
}
