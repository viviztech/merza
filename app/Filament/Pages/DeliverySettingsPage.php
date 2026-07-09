<?php

namespace App\Filament\Pages;

use App\Models\DeliverySetting;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Actions\Action;

class DeliverySettingsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Delivery Settings';
    protected static ?string $title = 'Delivery & Courier Settings';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = DeliverySetting::current()->toArray();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([
                SchemaSection::make('Packing & Weight Rules')
                    ->description('Applied to every order based on total order weight.')
                    ->schema([
                        Forms\Components\TextInput::make('packing_charge')
                            ->label('Packing Charge (₹)')
                            ->helperText('Fixed packing fee added for orders below the free-weight threshold.')
                            ->numeric()->prefix('₹')->required(),

                        Forms\Components\TextInput::make('packing_weight_kg')
                            ->label('Packing Weight (kg)')
                            ->helperText('Extra weight added to every order for packing materials.')
                            ->numeric()->suffix('kg')->required(),

                        Forms\Components\TextInput::make('free_weight_threshold_kg')
                            ->label('Free Weight Threshold (kg)')
                            ->helperText('Orders at or above this weight qualify for the free-weight discount.')
                            ->numeric()->suffix('kg')->required(),

                        Forms\Components\TextInput::make('free_weight_kg')
                            ->label('Free Weight (kg)')
                            ->helperText('Weight deducted from chargeable weight on qualifying orders (offsets packing weight).')
                            ->numeric()->suffix('kg')->required(),
                    ])->columns(2),

                SchemaSection::make('How Charges Are Calculated')
                    ->schema([
                        Forms\Components\Placeholder::make('formula_below')
                            ->label('Orders below threshold')
                            ->content('Chargeable weight = Order weight + Packing weight  |  Fee = (Chargeable weight × Zone rate) + Packing charge'),
                        Forms\Components\Placeholder::make('formula_above')
                            ->label('Orders at/above threshold')
                            ->content('Chargeable weight = Order weight + Packing weight − Free weight  |  Fee = Chargeable weight × Zone rate  (no packing charge)'),
                    ]),
            ])->statePath('data'),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $setting = DeliverySetting::current();
        $setting->update($this->data);

        Notification::make()->title('Delivery settings saved.')->success()->send();
    }
}
