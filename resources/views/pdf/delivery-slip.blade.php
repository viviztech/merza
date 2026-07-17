<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

  .page { padding: 30px 34px; }

  /* Letterhead */
  .letterhead-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  .brand-name { font-size: 24px; font-weight: 700; color: #1B6B2F; letter-spacing: 0.5px; }
  .brand-tagline { font-size: 9px; color: #777; margin-top: 1px; }
  .company-address { font-size: 9.5px; color: #555; line-height: 1.6; margin-top: 6px; }
  .slip-title { font-size: 18px; font-weight: 700; color: #fff; background: #1B6B2F; padding: 7px 16px; text-align: center; border-radius: 4px; letter-spacing: 0.5px; }
  .slip-meta { text-align: right; font-size: 10px; color: #444; margin-top: 6px; line-height: 1.6; }

  .divider { border: none; border-top: 2px solid #1B6B2F; margin: 12px 0 16px; }

  /* Order Number Banner */
  .order-banner { background: #f0fdf4; border: 2px solid #1B6B2F; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; text-align: center; }
  .order-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; }
  .order-number { font-size: 22px; font-weight: 700; color: #1B6B2F; margin-top: 2px; }

  /* Address section */
  .section-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; margin-bottom: 5px; }
  .deliver-box { border: 2px solid #1a1a1a; border-radius: 6px; padding: 12px 14px; margin-bottom: 14px; }
  .customer-name { font-size: 16px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
  .customer-phone { font-size: 13px; color: #1B6B2F; font-weight: 600; margin-bottom: 6px; }
  .customer-address { font-size: 11px; color: #333; line-height: 1.6; }

  /* Badges */
  .badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
  .badge-green  { background: #d1fae5; color: #065f46; }
  .badge-yellow { background: #fef9c3; color: #854d0e; }
  .badge-red    { background: #fee2e2; color: #991b1b; }
  .badge-gray   { background: #f3f4f6; color: #374151; }

  /* Items table */
  .items-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  .items-table th { background: #1B6B2F; color: #fff; padding: 7px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
  .items-table th.right { text-align: right; }
  .items-table td { padding: 8px 8px; border-bottom: 1px solid #eeeeee; font-size: 10px; vertical-align: middle; }
  .items-table td.right { text-align: right; font-weight: 600; }
  .items-table tr:last-child td { border-bottom: none; }
  .items-table tr:nth-child(even) td { background: #f9fafb; }
  .product-name { font-weight: 600; }
  .variant-tag { display: inline-block; background: #e5e7eb; color: #374151; padding: 1px 6px; border-radius: 8px; font-size: 9px; margin-top: 2px; }

  /* Tracking */
  .tracking-box { padding: 8px 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; margin-bottom: 14px; font-size: 11px; }
  .tracking-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #92400e; letter-spacing: 1px; }
  .tracking-number { font-size: 14px; font-weight: 700; color: #1a1a1a; margin-top: 2px; letter-spacing: 1px; }

  /* Courier & Charges */
  .charges-box { border: 1px solid #ddd; border-radius: 6px; padding: 14px 16px; margin-bottom: 18px; background: #fafafa; }
  .charges-table { width: 100%; border-collapse: collapse; }
  .charges-table td { padding: 4px 0; font-size: 11.5px; }
  .charges-table .label { color: #555; }
  .charges-table .amount { text-align: right; font-weight: 600; color: #1a1a1a; }
  .charges-table .divider-row td { border-top: 1px solid #ddd; padding-top: 8px; }
  .charges-table .total-row .label { font-size: 14px; font-weight: 700; color: #1a1a1a; }
  .charges-table .total-row .amount { font-size: 16px; font-weight: 700; color: #1B6B2F; }

  /* Signature box */
  .sig-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  .sig-box { border-top: 1px solid #ccc; padding-top: 4px; font-size: 9px; color: #888; text-align: center; }

  /* Footer */
  .footer { margin-top: 16px; padding-top: 10px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 9px; color: #999; }
</style>
</head>
<body>
<div class="page">

  {{-- Letterhead --}}
  <table class="letterhead-table">
    <tr>
      <td style="width:55%">
        <div class="brand-name">Merza Bodi</div>
        <div class="brand-tagline">Premium Tropical Fruits &bull; Bodinayakanur, Tamil Nadu</div>
        <div class="company-address">
          HP Petrol Bunk, Pankajam School Opp., Thevaram Road, Bodinayakanur &ndash; 625513, Tamil Nadu, India<br>
          Phone: +91 93600 64278 &bull; Email: merzabodinayakanur@gmail.com
        </div>
      </td>
      <td style="width:45%; vertical-align:top;">
        <div style="text-align:right;">
          <span class="slip-title">DELIVERY CHALLAN</span>
          <div class="slip-meta">
            <strong>{{ $order->order_number }}</strong><br>
            Date: {{ $order->created_at->format('d M Y, h:i A') }}
          </div>
        </div>
      </td>
    </tr>
  </table>

  <hr class="divider">

  {{-- Deliver To --}}
  <div class="section-label">Deliver To</div>
  <div class="deliver-box">
    <div class="customer-name">{{ $order->customer_name }}</div>
    @if($order->customer_phone)
    <div class="customer-phone">{{ $order->customer_phone }}</div>
    @endif
    <div class="customer-address">
      {{ $order->delivery_address }}<br>
      {{ collect([$order->city, $order->state])->filter()->implode(', ') }}@if($order->postcode) &ndash; {{ $order->postcode }}@endif
    </div>
  </div>

  {{-- Tracking --}}
  @if($order->tracking_number)
  <div class="tracking-box">
    <div class="tracking-label">Tracking Number</div>
    <div class="tracking-number">{{ $order->tracking_number }}</div>
  </div>
  @endif

  {{-- Items --}}
  <div class="section-label">Items in this Order</div>
  <table class="items-table">
    <thead>
      <tr>
        <th style="width:5%">#</th>
        <th style="width:55%">Product</th>
        <th class="right" style="width:15%">Qty</th>
        <th class="right" style="width:25%">Weight</th>
      </tr>
    </thead>
    <tbody>
      @php $totalWeightKg = 0; @endphp
      @foreach($order->items as $i => $item)
        @php
          $variant = $item->variant;
          $lineWeightKg = 0;
          if ($variant && $variant->weight_value) {
              $unitWeightKg = $variant->weight_unit === 'g' ? ($variant->weight_value / 1000) : (float) $variant->weight_value;
              $lineWeightKg = $unitWeightKg * $item->quantity;
          }
          $totalWeightKg += $lineWeightKg;
        @endphp
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>
            <div class="product-name">{{ $item->product_name }}</div>
            @if($item->variant_name)<span class="variant-tag">{{ $item->variant_name }}</span>@endif
          </td>
          <td class="right">{{ $item->quantity }}</td>
          <td class="right">{{ $lineWeightKg > 0 ? number_format($lineWeightKg, 2) . ' kg' : '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Courier & Charges --}}
  <div class="section-label">Courier &amp; Charges</div>
  <div class="charges-box">
    <table class="charges-table">
      <tr>
        <td class="label">Total Weight</td>
        <td class="amount">{{ number_format($totalWeightKg, 2) }} kg</td>
      </tr>
      <tr>
        <td class="label">Subtotal</td>
        <td class="amount">Rs. {{ number_format($order->subtotal, 2) }}</td>
      </tr>
      <tr>
        <td class="label">Courier Charges</td>
        <td class="amount">Rs. {{ number_format($order->delivery_fee, 2) }}</td>
      </tr>
      <tr class="divider-row total-row">
        <td class="label">Total Amount</td>
        <td class="amount">Rs. {{ number_format($order->total, 2) }}</td>
      </tr>
    </table>
    <div style="margin-top:10px;">
      Payment Status:
      @php
        $paymentBadge = match($order->payment_status) {
          'paid' => 'badge-green', 'unpaid' => 'badge-yellow', 'refunded' => 'badge-red', default => 'badge-gray',
        };
      @endphp
      <span class="badge {{ $paymentBadge }}">{{ strtoupper($order->payment_status) }}</span>
      @if($order->payment_status === 'unpaid')
        <span style="font-size:10px; color:#92400e;"> &mdash; collect Rs. {{ number_format($order->total, 2) }} on delivery</span>
      @endif
    </div>
  </div>

  {{-- Signature Line --}}
  <table class="sig-table">
    <tr>
      <td style="width:45%">
        <div class="sig-box">Delivery Agent Signature</div>
      </td>
      <td style="width:10%"></td>
      <td style="width:45%">
        <div class="sig-box">Customer Signature / Received By</div>
      </td>
    </tr>
  </table>

  {{-- Footer --}}
  <div class="footer">
    Merza Bodi &bull; +91 93600 64278 &bull; merzabodinayakanur@gmail.com &bull; merzabodi.com
  </div>

</div>
</body>
</html>
