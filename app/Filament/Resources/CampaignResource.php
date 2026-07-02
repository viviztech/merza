<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Jobs\DispatchCampaignJob;
use App\Models\Campaign;
use App\Models\Contact;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';
    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make('Campaign Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()->maxLength(255)->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->rows(2)->columnSpanFull(),

                Forms\Components\Select::make('type')
                    ->options([
                        'broadcast'  => 'Broadcast — one message to all matching contacts',
                        'drip'       => 'Drip Sequence — series of messages over time',
                        'follow_up'  => 'Follow-up — re-engage contacts with no recent activity',
                    ])
                    ->default('broadcast')
                    ->required()
                    ->live(),

                Forms\Components\Select::make('channel')
                    ->options([
                        'whatsapp'  => 'WhatsApp',
                        'facebook'  => 'Facebook',
                        'email'     => 'Email',
                        'sms'       => 'SMS',
                    ])
                    ->default('whatsapp')
                    ->required(),
            ])->columns(2),

            SchemaSection::make('Target Audience')
                ->description('Leave all filters blank to target all non-blocked contacts.')
                ->schema([
                    Forms\Components\TagsInput::make('filter_tags')
                        ->label('Contact Tags (match any)')
                        ->placeholder('e.g. whatsapp_inbound, premium')
                        ->helperText('Contacts must have at least one of these tags.')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('filter_source')
                        ->label('Contact Source')
                        ->options([
                            'whatsapp'  => 'WhatsApp',
                            'facebook'  => 'Facebook Lead Ad',
                            'instagram' => 'Instagram',
                            'referral'  => 'Referral',
                            'walk_in'   => 'Walk-in',
                            'website'   => 'Website',
                            'other'     => 'Other',
                        ])
                        ->nullable(),

                    Forms\Components\TextInput::make('filter_city')
                        ->label('City (partial match)')
                        ->placeholder('e.g. Mumbai'),
                ])->columns(3),

            // Single message — shown for broadcast and follow_up
            SchemaSection::make('Message')
                ->description('Use {{customer_name}}, {{city}}, {{phone}} as placeholders.')
                ->schema([
                    Forms\Components\Textarea::make('message')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                ])
                ->visible(fn ($get) => in_array($get('type'), ['broadcast', 'follow_up', null, ''])),

            // Broadcast schedule
            SchemaSection::make('Schedule')
                ->schema([
                    Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Send At')
                        ->helperText('Leave blank to send immediately when launched.')
                        ->nullable(),
                ])
                ->visible(fn ($get) => $get('type') === 'broadcast'),

            // Follow-up trigger
            SchemaSection::make('Follow-up Settings')
                ->schema([
                    Forms\Components\TextInput::make('follow_up_after_days')
                        ->label('Days since last contact')
                        ->numeric()->minValue(1)->default(3)
                        ->helperText('Re-engage contacts who haven\'t been messaged in this many days. Checked daily.'),
                ])
                ->visible(fn ($get) => $get('type') === 'follow_up'),

            // Drip steps repeater
            SchemaSection::make('Drip Sequence Steps')
                ->description('Steps are sent in order. Delay is the number of days after the previous step (step 1 delay = days after enrollment).')
                ->schema([
                    Forms\Components\Repeater::make('steps')
                        ->relationship('steps')
                        ->schema([
                            Forms\Components\TextInput::make('delay_days')
                                ->label('Delay (days)')
                                ->numeric()->minValue(0)->default(0)
                                ->helperText('Days after previous step'),

                            Forms\Components\Textarea::make('message')
                                ->required()->rows(3)->columnSpanFull()
                                ->placeholder('Use {{customer_name}}, {{city}}, {{phone}}'),
                        ])
                        ->columns(2)
                        ->addActionLabel('Add Step')
                        ->columnSpanFull(),
                ])
                ->visible(fn ($get) => $get('type') === 'drip'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            SchemaSection::make('Campaign')->schema([
                TextEntry::make('name'),
                TextEntry::make('type')->badge()
                    ->color(fn ($state) => match ($state) {
                        'broadcast' => 'info',
                        'drip'      => 'warning',
                        'follow_up' => 'success',
                        default     => 'gray',
                    }),
                TextEntry::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'    => 'success',
                        'scheduled' => 'info',
                        'draft'     => 'gray',
                        'paused'    => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
                TextEntry::make('channel')->badge()->color('gray'),
                TextEntry::make('total_contacts')->label('Contacts'),
                TextEntry::make('sent_count')->label('Sent'),
                TextEntry::make('failed_count')->label('Failed'),
                TextEntry::make('scheduled_at')->dateTime('d M Y, h:i A')->placeholder('—'),
                TextEntry::make('started_at')->dateTime('d M Y, h:i A')->placeholder('—'),
                TextEntry::make('completed_at')->dateTime('d M Y, h:i A')->placeholder('—'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()->sortable()
                    ->description(fn (Campaign $r) => $r->description ? str($r->description)->limit(60) : null),

                Tables\Columns\TextColumn::make('type')->badge()
                    ->color(fn ($state) => match ($state) {
                        'broadcast' => 'info',
                        'drip'      => 'warning',
                        'follow_up' => 'success',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'    => 'success',
                        'scheduled' => 'info',
                        'draft'     => 'gray',
                        'paused'    => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('channel')->badge()->color('gray'),

                Tables\Columns\TextColumn::make('total_contacts')->label('Contacts')->alignCenter(),
                Tables\Columns\TextColumn::make('sent_count')->label('Sent')->alignCenter(),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->dateTime('d M Y H:i')
                    ->label('Scheduled')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->label('Created')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'broadcast'  => 'Broadcast',
                        'drip'       => 'Drip',
                        'follow_up'  => 'Follow-up',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'scheduled' => 'Scheduled',
                        'active'    => 'Active',
                        'paused'    => 'Paused',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                // Launch: draft → scheduled (if scheduled_at set) or active (if no schedule)
                Action::make('launch')
                    ->label('Launch')
                    ->icon('heroicon-o-rocket-launch')
                    ->color('success')
                    ->visible(fn (Campaign $r) => $r->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading('Launch Campaign')
                    ->modalDescription(fn (Campaign $r) => $r->scheduled_at
                        ? "Schedule '{$r->name}' to send on {$r->scheduled_at->format('d M Y, h:i A')}?"
                        : "Launch '{$r->name}' immediately to all matching contacts?")
                    ->action(function (Campaign $campaign) {
                        if ($campaign->scheduled_at && $campaign->scheduled_at->isFuture()) {
                            $campaign->update(['status' => 'scheduled']);
                        } else {
                            DispatchCampaignJob::dispatch($campaign->id);
                        }
                    }),

                Action::make('pause')
                    ->label('Pause')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (Campaign $r) => $r->status === 'active')
                    ->action(fn (Campaign $r) => $r->update(['status' => 'paused'])),

                Action::make('resume')
                    ->label('Resume')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Campaign $r) => $r->status === 'paused')
                    ->action(fn (Campaign $r) => $r->update(['status' => 'active'])),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Campaign $r) => in_array($r->status, ['draft', 'scheduled', 'paused']))
                    ->requiresConfirmation()
                    ->action(fn (Campaign $r) => $r->update(['status' => 'cancelled'])),

                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'view'   => Pages\ViewCampaign::route('/{record}'),
            'edit'   => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
