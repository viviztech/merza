<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->record->name === 'Admin'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissionNames = $this->record->permissions->pluck('name')->toArray();

        foreach ($permissionNames as $perm) {
            $group = explode('_', $perm, 2)[1] ?? 'general';
            $data["permissions_{$group}"][] = $perm;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $selected = [];
        foreach ($this->data as $key => $value) {
            if (str_starts_with($key, 'permissions_') && is_array($value)) {
                $selected = array_merge($selected, $value);
            }
        }
        $this->record->syncPermissions($selected);
    }
}
