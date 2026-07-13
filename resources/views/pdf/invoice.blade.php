<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; }

  .page { padding: 36px 40px; }

  /* Header */
  .header-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
  .brand-name { font-size: 26px; font-weight: 700; color: #1B6B2F; letter-spacing: 1px; }
  .brand-tagline { font-size: 10px; color: #666; margin-top: 2px; }
  .invoice-title { font-size: 32px; font-weight: 700; color: #1B6B2F; text-align: right; letter-spacing: 2px; }
  .invoice-meta { text-align: right; font-size: 11px; color: #444; margin-top: 4px; line-height: 1.6; }

  /* Divider */
  .divider { border: none; border-top: 2px solid #1B6B2F; margin: 16px 0; }
  .divider-light { border: none; border-top: 1px solid #e0e0e0; margin: 12px 0; }

  /* Info boxes */
  .info-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  .info-box { vertical-align: top; width: 50%; padding-right: 16px; }
  .info-box-right { vertical-align: top; width: 50%; text-align: right; padding-left: 16px; }
  .info-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; margin-bottom: 6px; }
  .info-name { font-size: 14px; font-weight: 700; color: #1a1a1a; margin-bottom: 3px; }
  .info-text { font-size: 11px; color: #444; line-height: 1.6; }

  /* Status badges */
  .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
  .badge-green  { background: #d1fae5; color: #065f46; }
  .badge-yellow { background: #fef9c3; color: #854d0e; }
  .badge-blue   { background: #dbeafe; color: #1e3a8a; }
  .badge-red    { background: #fee2e2; color: #991b1b; }
  .badge-gray   { background: #f3f4f6; color: #374151; }

  /* Items table */
  .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  .items-table th { background: #1B6B2F; color: #fff; padding: 9px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
  .items-table th.right { text-align: right; }
  .items-table td { padding: 9px 10px; border-bottom: 1px solid #f0f0f0; font-size: 11px; vertical-align: middle; }
  .items-table td.right { text-align: right; }
  .items-table tr:last-child td { border-bottom: none; }
  .items-table tr:nth-child(even) td { background: #f9fafb; }
  .product-name { font-weight: 600; color: #1a1a1a; }
  .variant-name { font-size: 10px; color: #888; margin-top: 2px; }

  /* Totals */
  .totals-table { width: 100%; border-collapse: collapse; }
  .totals-table td { padding: 5px 10px; font-size: 12px; }
  .totals-table .label { text-align: right; color: #555; width: 70%; }
  .totals-table .amount { text-align: right; font-weight: 600; width: 30%; }
  .totals-row-total td { border-top: 2px solid #1B6B2F; padding-top: 9px; font-size: 15px; font-weight: 700; color: #1B6B2F; }

  /* Payment section */
  .payment-section { margin-top: 20px; padding: 14px 16px; background: #f0fdf4; border-left: 4px solid #1B6B2F; border-radius: 4px; }
  .payment-row { margin-bottom: 4px; font-size: 11px; color: #374151; }
  .payment-row span { font-weight: 600; color: #1a1a1a; }

  /* Notes */
  .notes-section { margin-top: 16px; padding: 12px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; }
  .notes-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #92400e; letter-spacing: 1px; margin-bottom: 4px; }
  .notes-text { font-size: 11px; color: #444; line-height: 1.5; }

  /* Footer */
  .footer { margin-top: 36px; padding-top: 14px; border-top: 1px solid #e0e0e0; }
  .footer-table { width: 100%; border-collapse: collapse; }
  .footer-address { font-size: 10px; color: #666; line-height: 1.6; }
  .footer-thank { text-align: right; font-size: 12px; font-weight: 700; color: #1B6B2F; }
  .footer-sub { text-align: right; font-size: 10px; color: #888; margin-top: 2px; }
</style>
</head>
<body>
<div class="page">

  {{-- Header --}}
  <table class="header-table">
    <tr>
      <td>
        <div class="brand-name">Merza Bodi</div>
        <div class="brand-tagline">Premium Tropical Fruits &bull; Bodinayakanur, Tamil Nadu</div>
      </td>
      <td>
        <div class="invoice-title">INVOICE</div>
        <div class="invoice-meta">
          <strong>{{ $order->order_number }}</strong><br>
          Date: {{ $order->created_at->format('d M Y') }}<br>
          @if($order->payment_status === 'paid')
            <span class="badge badge-green">PAID</span>
          @elseif($order->payment_status === 'unpaid')
            <span class="badge badge-yellow">UNPAID</span>
          @else
            <span class="badge badge-blue">{{ strtoupper($order->payment_status) }}</span>
          @endif
        </div>
      </td>
    </tr>
  </table>

  <hr class="divider">

  {{-- Bill To + Order Info --}}
  <table class="info-table">
    <tr>
      <td class="info-box">
        <div class="info-label">Bill To</div>
        <div class="info-name">{{ $order->customer_name }}</div>
        <div class="info-text">
          @if($order->customer_phone) {{ $order->customer_phone }}<br>@endif
          @if($order->customer_email) {{ $order->customer_email }}<br>@endif
          {{ $order->delivery_address }}<br>
          @if($order->city){{ $order->city }}@endif@if($order->state), {{ $order->state }}@endif@if($order->postcode) &ndash; {{ $order->postcode }}@endif
        </div>
      </td>
      <td class="info-box-right">
        <div class="info-label">Order Details</div>
        <div class="info-text">
          <strong>Order #:</strong> {{ $order->order_number }}<br>
          <strong>Date:</strong> {{ $order->created_at->format('d M Y, h:i A') }}<br>
          <strong>Status:</strong>
          @php
            $statusColors = ['delivered'=>'badge-green','confirmed'=>'badge-blue','preparing'=>'badge-blue','delivering'=>'badge-green','cancelled'=>'badge-red','pending'=>'badge-yellow'];
            $statusColor = $statusColors[$order->status] ?? 'badge-gray';
          @endphp
          <span class="badge {{ $statusColor }}">{{ strtoupper($order->status) }}</span><br>
          @if($order->tracking_number)
            <strong>Tracking:</strong> {{ $order->tracking_number }}<br>
          @endif
          @if($order->delivered_at)
            <strong>Delivered:</strong> {{ $order->delivered_at->format('d M Y') }}<br>
          @endif
        </div>
      </td>
    </tr>
  </table>

  {{-- Items Table --}}
  <table class="items-table">
    <thead>
      <tr>
        <th style="width:5%">#</th>
        <th style="width:45%">Product</th>
        <th class="right" style="width:10%">Qty</th>
        <th class="right" style="width:20%">Unit Price</th>
        <th class="right" style="width:20%">Subtotal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($order->items as $i => $item)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>
          <div class="product-name">{{ $item->product_name }}</div>
          @if($item->variant_name)<div class="variant-name">{{ $item->variant_name }}</div>@endif
        </td>
        <td class="right">{{ $item->quantity }}</td>
        <td class="right">Rs. {{ number_format($item->unit_price, 2) }}</td>
        <td class="right">Rs. {{ number_format($item->subtotal, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Totals --}}
  <table class="totals-table">
    <tr>
      <td class="label">Subtotal</td>
      <td class="amount">Rs. {{ number_format($order->subtotal, 2) }}</td>
    </tr>
    <tr>
      <td class="label">Delivery Fee</td>
      <td class="amount">Rs. {{ number_format($order->delivery_fee, 2) }}</td>
    </tr>
    <tr class="totals-row-total">
      <td class="label">Total</td>
      <td class="amount">Rs. {{ number_format($order->total, 2) }}</td>
    </tr>
  </table>

  {{-- Payment --}}
  <div class="payment-section">
    <div class="payment-row">Payment Method: <span>{{ match($order->payment_method) { 'cod' => 'Cash on Delivery', 'bank_transfer' => 'Bank Transfer', 'whatsapp' => 'WhatsApp Payment', default => $order->payment_method } }}</span></div>
    <div class="payment-row">Payment Status: <span>{{ ucfirst($order->payment_status) }}</span></div>
  </div>

  {{-- Customer Notes --}}
  @if($order->notes)
  <div class="notes-section">
    <div class="notes-label">Customer Notes</div>
    <div class="notes-text">{{ $order->notes }}</div>
  </div>
  @endif

  {{-- Footer --}}
  <div class="footer">
    <table class="footer-table">
      <tr>
        <td class="footer-address">
          <strong>Merza Bodi</strong><br>
          HP Petrol Bunk, Pankajam School Opp., Thevaram Road<br>
          Bodinayakanur &ndash; 625513, Tamil Nadu, India<br>
          Phone: +91 93600 64278 &bull; merzabodinayakanur@gmail.com
        </td>
        <td>
          <div class="footer-thank">Thank you for your order!</div>
          <div class="footer-sub">merzabodi.com</div>
        </td>
      </tr>
    </table>
  </div>

</div>
</body>
</html>
