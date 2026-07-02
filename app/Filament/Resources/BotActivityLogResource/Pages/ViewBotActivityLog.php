<?php

namespace App\Filament\Resources\BotActivityLogResource\Pages;

use App\Filament\Resources\BotActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBotActivityLog extends ViewRecord
{
    protected static string $resource = BotActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
