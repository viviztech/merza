<?php

namespace App\Filament\Pages;

use App\Models\IntegrationSetting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;

class IntegrationSettingsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket-square';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Integrations';
    protected static ?string $title = 'Analytics & Tracking Integrations';
    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = IntegrationSetting::current()->toArray();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([
                SchemaSection::make('Google Tag Manager')
                    ->description('Loaded on every storefront page. Find your container ID in Google Tag Manager under Admin > Container Settings.')
                    ->schema([
                        Forms\Components\TextInput::make('gtm_container_id')
                            ->label('GTM Container ID')
                            ->placeholder('GTM-XXXXXXX'),
                    ]),

                SchemaSection::make('Meta Pixel')
                    ->description('Get your Pixel ID from Meta Events Manager > Data Sources.')
                    ->schema([
                        Forms\Components\TextInput::make('meta_pixel_id')
                            ->label('Meta Pixel ID')
                            ->placeholder('1234567890123456'),
                    ]),

                SchemaSection::make('Facebook Domain Verification')
                    ->description('The meta tag content from Meta Business Settings > Brand Safety > Domains.')
                    ->schema([
                        Forms\Components\TextInput::make('facebook_domain_verification')
                            ->label('Verification Code')
                            ->placeholder('e.g. z11x4bpkeypc1aj7dz9p2i5p08n7vl'),
                    ]),

                SchemaSection::make('Custom Scripts')
                    ->description('For any other integration (e.g. Hotjar, Microsoft Clarity, LinkedIn Insight). Pasted as-is — head scripts render just before </head>, body scripts render just after <body>.')
                    ->schema([
                        Forms\Components\Textarea::make('custom_head_scripts')
                            ->label('Head Scripts')
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('custom_body_scripts')
                            ->label('Body Scripts')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                SchemaActions::make([
                    Action::make('save')
                        ->label('Save Settings')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action('save'),
                ]),
            ])->statePath('data'),
        ]);
    }

    public function save(): void
    {
        IntegrationSetting::current()->update($this->data);

        Notification::make()
            ->title('Integration settings saved')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
        ];
    }
}
