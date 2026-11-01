<?php

namespace App\Filament\Exports;

use App\Models\Order;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class OrderExporter extends Exporter
{
    protected static ?string $model = Order::class;

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
            ExportColumn::make('order_number')->label('Order Number'),
            ExportColumn::make('channel')->label('Channel'),
            ExportColumn::make('customer_name')->label('Customer Name'),
            ExportColumn::make('customer_phone')->label('Phone'),
            ExportColumn::make('customer_email')->label('Email'),
            ExportColumn::make('delivery_address')->label('Delivery Address'),
            ExportColumn::make('city')->label('City'),
            ExportColumn::make('state')->label('State'),
            ExportColumn::make('postcode')->label('Pincode'),
            ExportColumn::make('status')->label('Order Status'),
            ExportColumn::make('payment_method')->label('Payment Method'),
            ExportColumn::make('payment_status')->label('Payment Status'),
            ExportColumn::make('payment_reference')->label('Payment Reference'),
            ExportColumn::make('attribution_origin')->label('Origin'),
            ExportColumn::make('attribution_source_type')->label('Origin Type'),
            ExportColumn::make('attribution_campaign')->label('Campaign'),
            ExportColumn::make('attribution_device_type')->label('Device'),
            ExportColumn::make('subtotal')->label('Subtotal (INR)'),
            ExportColumn::make('delivery_fee')->label('Delivery Fee (INR)'),
            ExportColumn::make('total')->label('Total (INR)'),
            ExportColumn::make('courier_name')->label('Courier'),
            ExportColumn::make('tracking_number')->label('Tracking Number'),
            ExportColumn::make('created_at')->label('Ordered At'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your order export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
