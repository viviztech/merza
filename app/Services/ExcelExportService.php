<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelExportService
{
    /**
     * Export Leads query to an Excel (.xlsx) file and return download response.
     */
    public function exportLeads(Builder $query, string $filename = 'leads_export'): BinaryFileResponse
    {
        $headers = [
            'Lead ID',
            'Customer Name',
            'Phone',
            'Email',
            'Lead Status',
            'Lead Source',
            'Meta Ad',
            'Product Interest',
            'Estimated Value (INR)',
            'Contact Status',
            'Orders Count',
            'Assigned To',
            'Follow-up Date',
            'Notes',
            'Created Date',
        ];

        return $this->streamExport($filename, $headers, function (Writer $writer) use ($query) {
            $query->with(['contact.orders', 'assignedTo'])->chunk(250, function ($leads) use ($writer) {
                foreach ($leads as $lead) {
                    /** @var Lead $lead */
                    $contact = $lead->contact;
                    $ordersCount = $contact ? $contact->orders->count() : 0;
                    $contactStatus = $contact
                        ? (Contact::CATEGORY_LABELS[$contact->customer_category] ?? $contact->customer_category)
                        : '—';

                    $row = [
                        (string) $lead->id,
                        (string) ($contact?->name ?? '—'),
                        (string) ($contact?->phone ?? '—'),
                        (string) ($contact?->email ?? '—'),
                        (string) ($lead->stage_label),
                        (string) ($lead->source ? ucfirst(str_replace('_', ' ', $lead->source)) : '—'),
                        (string) ($lead->meta_ad_name ?: $lead->meta_ad_id ?: '—'),
                        (string) ($lead->product_interest ?: '—'),
                        $lead->estimated_value ? (float) $lead->estimated_value : 0.0,
                        (string) $contactStatus,
                        $ordersCount,
                        (string) ($lead->assignedTo?->name ?? 'Unassigned'),
                        $lead->due_at ? $lead->due_at->format('Y-m-d H:i') : '—',
                        (string) ($lead->notes ?: '—'),
                        $lead->created_at ? $lead->created_at->format('Y-m-d H:i') : '—',
                    ];

                    $writer->addRow(Row::fromValues($row));
                }
            });
        });
    }

    /**
     * Export Contacts query to an Excel (.xlsx) file and return download response.
     */
    public function exportContacts(Builder $query, string $filename = 'contacts_export'): BinaryFileResponse
    {
        $headers = [
            'Contact ID',
            'Customer Name',
            'Phone',
            'Email',
            'Lead Source',
            'Latest Meta Ad',
            'Contact Status',
            'Active Lead Status',
            'Product Interest',
            'City',
            'State',
            'Is Customer',
            'Is Blocked',
            'Total Orders',
            'Total Spent (INR)',
            'Assigned To',
            'Last Contacted',
            'Created Date',
        ];

        return $this->streamExport($filename, $headers, function (Writer $writer) use ($query) {
            $query->with(['leads', 'orders', 'assignedTo'])->chunk(250, function ($contacts) use ($writer) {
                foreach ($contacts as $contact) {
                    /** @var Contact $contact */
                    $activeLead = $contact->active_lead;
                    $ordersCount = $contact->orders->count();
                    $totalSpent = (float) $contact->orders->sum('total');

                    $row = [
                        (string) $contact->id,
                        (string) $contact->name,
                        (string) $contact->phone,
                        (string) ($contact->email ?? '—'),
                        (string) (Contact::SOURCE_LABELS[$contact->source] ?? $contact->source),
                        (string) ($contact->meta_ad_name ?: $contact->meta_ad_id ?: '—'),
                        (string) (Contact::CATEGORY_LABELS[$contact->customer_category] ?? $contact->customer_category),
                        (string) ($activeLead ? $activeLead->stage_label : '—'),
                        (string) ($activeLead?->product_interest ?: '—'),
                        (string) ($contact->city ?: '—'),
                        (string) ($contact->state ?: '—'),
                        $contact->is_customer ? 'Yes' : 'No',
                        $contact->is_blocked ? 'Yes' : 'No',
                        $ordersCount,
                        $totalSpent,
                        (string) ($contact->assignedTo?->name ?? 'Unassigned'),
                        $contact->last_contacted_at ? $contact->last_contacted_at->format('Y-m-d H:i') : '—',
                        $contact->created_at ? $contact->created_at->format('Y-m-d H:i') : '—',
                    ];

                    $writer->addRow(Row::fromValues($row));
                }
            });
        });
    }

    /**
     * Export Orders query to an Excel (.xlsx) file and return download response.
     */
    public function exportOrders(Builder $query, string $filename = 'orders_export'): BinaryFileResponse
    {
        $headers = [
            'Order Number',
            'Channel',
            'Customer Name',
            'Phone',
            'Email',
            'Delivery Address',
            'City',
            'State',
            'Pincode',
            'Order Status',
            'Payment Method',
            'Payment Status',
            'Payment Reference / UTR',
            'Origin',
            'Campaign',
            'Device',
            'Subtotal (INR)',
            'Delivery Fee (INR)',
            'Total (INR)',
            'Items Summary',
            'Courier',
            'Tracking Number',
            'Ordered At',
            'Confirmed At',
            'Dispatched At',
            'Delivered At',
        ];

        return $this->streamExport($filename, $headers, function (Writer $writer) use ($query) {
            $query->with(['items'])->chunk(250, function ($orders) use ($writer) {
                foreach ($orders as $order) {
                    /** @var Order $order */
                    $itemsSummary = $order->items->map(function ($item) {
                        return "{$item->product_name} (" . ($item->variant_name ?: 'Default') . ") x {$item->quantity}";
                    })->implode('; ');

                    $row = [
                        (string) $order->order_number,
                        (string) ucfirst($order->channel),
                        (string) $order->customer_name,
                        (string) $order->customer_phone,
                        (string) ($order->customer_email ?: '—'),
                        (string) ($order->delivery_address ?: '—'),
                        (string) ($order->city ?: '—'),
                        (string) ($order->state ?: '—'),
                        (string) ($order->postcode ?: '—'),
                        (string) ucfirst($order->status),
                        (string) match ($order->payment_method) {
                            'cod' => 'Cash on Delivery',
                            'upi' => 'UPI Payment',
                            'bank_transfer' => 'Bank Transfer',
                            'whatsapp' => 'WhatsApp Order',
                            default => $order->payment_method,
                        },
                        (string) ucfirst($order->payment_status),
                        (string) ($order->payment_reference ?: '—'),
                        (string) ($order->attribution_origin ?: 'Direct'),
                        (string) ($order->attribution_campaign ?: '—'),
                        (string) ($order->attribution_device_type ?: '—'),
                        (float) $order->subtotal,
                        (float) $order->delivery_fee,
                        (float) $order->total,
                        (string) ($itemsSummary ?: '—'),
                        (string) ($order->courier_name ?: '—'),
                        (string) ($order->tracking_number ?: '—'),
                        $order->created_at ? $order->created_at->format('Y-m-d H:i') : '—',
                        $order->confirmed_at ? $order->confirmed_at->format('Y-m-d H:i') : '—',
                        $order->dispatched_at ? $order->dispatched_at->format('Y-m-d H:i') : '—',
                        $order->delivered_at ? $order->delivered_at->format('Y-m-d H:i') : '—',
                    ];

                    $writer->addRow(Row::fromValues($row));
                }
            });
        });
    }

    /**
     * Streams writer rows into a temporary file and prepares a download response.
     */
    private function streamExport(string $filename, array $headers, callable $rowWriter): BinaryFileResponse
    {
        $tempDir = storage_path('framework/cache');
        if (! is_dir($tempDir)) {
            $tempDir = sys_get_temp_dir();
        }

        $tempFile = $tempDir . DIRECTORY_SEPARATOR . uniqid('merza_export_', true) . '.xlsx';

        $options = new Options();
        if (is_dir($tempDir) && is_writable($tempDir)) {
            $options->setTempFolder($tempDir);
        }
        $writer = new Writer($options);
        $writer->openToFile($tempFile);

        $headerStyle = (new Style())->setFontBold();
        $writer->addRow(Row::fromValues($headers, $headerStyle));

        $rowWriter($writer);

        $writer->close();

        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $filename) . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->download($tempFile, $safeName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
