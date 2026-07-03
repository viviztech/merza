<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Roles & Permissions';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('Admin');
    }

    public static function form(Schema $schema): Schema
    {
        $permissionsByGroup = Permission::all()
            ->groupBy(fn ($p) => explode('_', $p->name, 2)[1] ?? 'general');

        return $schema->components([
            SchemaSection::make('Role Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->helperText('e.g. Admin, Sales, Operations, Delivery'),

                Forms\Components\TextInput::make('guard_name')
                    ->default('web')
                    ->disabled()
                    ->dehydrated(),
            ])->columns(2),

            SchemaSection::make('Permissions')->schema(
                $permissionsByGroup->map(function ($perms, $group) {
                    return Forms\Components\CheckboxList::make("permissions_{$group}")
                        ->label(ucwords(str_replace('_', ' ', $group)))
                        ->options($perms->pluck('name', 'name')->toArray())
                        ->columns(2)
                        ->bulkToggleable();
                })->values()->toArray()
            ),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Admin'      => 'danger',
                        'Sales'      => 'info',
                        'Operations' => 'warning',
                        'Delivery'   => 'success',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->hidden(fn (Role $record) => $record->name === 'Admin'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit'   => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
