<?php

namespace App\Filament\Exports;

use App\Models\Contact;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ContactExporter extends Exporter
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name'),
            ExportColumn::make('phone'),
            ExportColumn::make('email'),
            ExportColumn::make('source')
                ->formatStateUsing(fn (?string $state) => $state ? Contact::SOURCE_LABELS[$state] ?? $state : null),
            ExportColumn::make('customer_category')
                ->label('Customer Category')
                ->formatStateUsing(fn (?string $state) => $state ? Contact::CATEGORY_LABELS[$state] ?? $state : null),
            ExportColumn::make('city'),
            ExportColumn::make('state'),
            ExportColumn::make('is_customer')->label('Is Customer'),
            ExportColumn::make('assignedTo.name')->label('Assigned To'),
            ExportColumn::make('tags')->listAsJson(),
            ExportColumn::make('last_contacted_at'),
            ExportColumn::make('created_at')->label('Added On'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your contact export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
