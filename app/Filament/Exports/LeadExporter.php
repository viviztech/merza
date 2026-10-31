<?php

namespace App\Filament\Exports;

use App\Models\Contact;
use App\Models\Lead;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class LeadExporter extends Exporter
{
    protected static ?string $model = Lead::class;

    public function getFormats(): array
    {
        return [
            ExportFormat::Xlsx,
            ExportFormat::Csv,
        ];
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('Lead ID'),
            ExportColumn::make('contact.name')->label('Customer Name'),
            ExportColumn::make('contact.phone')->label('Phone'),
            ExportColumn::make('contact.email')->label('Email'),
            ExportColumn::make('stage')
                ->label('Lead Status')
                ->formatStateUsing(fn ($state) => Lead::$stages[$state] ?? $state),
            ExportColumn::make('source')->label('Lead Source'),
            ExportColumn::make('meta_ad_name')->label('Meta Ad'),
            ExportColumn::make('product_interest')->label('Product Interest'),
            ExportColumn::make('estimated_value')->label('Estimated Value'),
            ExportColumn::make('contact.customer_category')
                ->label('Contact Status')
                ->formatStateUsing(fn (?string $state) => $state ? Contact::CATEGORY_LABELS[$state] ?? $state : null),
            ExportColumn::make('assignedTo.name')->label('Assigned To'),
            ExportColumn::make('due_at')->label('Follow-up Date'),
            ExportColumn::make('notes')->label('Notes'),
            ExportColumn::make('created_at')->label('Added On'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your lead export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

