<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

  .page { padding: 24px 28px; }

  /* Header */
  .header-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  .brand-name { font-size: 20px; font-weight: 700; color: #1B6B2F; }
  .brand-tagline { font-size: 9px; color: #777; margin-top: 1px; }
  .slip-title { font-size: 18px; font-weight: 700; color: #fff; background: #1B6B2F; padding: 6px 14px; text-align: center; border-radius: 4px; }

  .divider { border: none; border-top: 2px solid #1B6B2F; margin: 12px 0; }

  /* Order Number Banner */
  .order-banner { background: #f0fdf4; border: 2px solid #1B6B2F; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; text-align: center; }
  .order-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; }
  .order-number { font-size: 22px; font-weight: 700; color: #1B6B2F; margin-top: 2px; }
  .order-date { font-size: 10px; color: #666; margin-top: 2px; }

  /* Address section */
  .section-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; margin-bottom: 5px; }
  .addr-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  .addr-col { width: 50%; vertical-align: top; }
  .addr-col.from { padding-right: 10px; }
  .addr-col.to { padding-left: 10px; }
  .from-box { border: 1px solid #ddd; border-radius: 6px; padding: 12px 14px; background: #fafafa; }
  .from-name { font-size: 13px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
  .from-address { font-size: 10px; color: #555; line-height: 1.6; }
  .deliver-box { border: 2px solid #1a1a1a; border-radius: 6px; padding: 12px 14px; }
  .customer-name { font-size: 16px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
  .customer-phone { font-size: 13px; color: #1B6B2F; font-weight: 600; margin-bottom: 6px; }
  .customer-address { font-size: 11px; color: #333; line-height: 1.6; }

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

  /* Signature box */
  .sig-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
  .sig-box { border-top: 1px solid #ccc; padding-top: 4px; font-size: 9px; color: #888; text-align: center; }

  /* Footer */
  .footer { margin-top: 16px; padding-top: 10px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 9px; color: #999; }
</style>
</head>
<body>
<div class="page">

  {{-- Header --}}
  <table class="header-table">
    <tr>
      <td>
        <div class="brand-name">Merza Bodi</div>
        <div class="brand-tagline">Premium Tropical Fruits &bull; Bodinayakanur</div>
      </td>
      <td style="text-align:right; vertical-align:middle;">
        <span class="slip-title">DELIVERY SLIP</span>
      </td>
    </tr>
  </table>

  <hr class="divider">

  {{-- Order Number --}}
  <div class="order-banner">
    <div class="order-label">Order Number</div>
    <div class="order-number">{{ $order->order_number }}</div>
    <div class="order-date">{{ $order->created_at->format('d M Y, h:i A') }}</div>
  </div>

  {{-- From / To --}}
  <table class="addr-table">
    <tr>
      <td class="addr-col from">
        <div class="section-label">From</div>
        <div class="from-box">
          <div class="from-name">Merza Bodi</div>
          <div class="from-address">
            HP Petrol Bunk, Pankajam School Opp.,<br>
            Thevaram Road, Bodinayakanur &ndash; 625513<br>
            Tamil Nadu, India<br>
            +91 93600 64278
          </div>
        </div>
      </td>
      <td class="addr-col to">
        <div class="section-label">To</div>
        <div class="deliver-box">
          <div class="customer-name">{{ $order->customer_name }}</div>
          @if($order->customer_phone)
          <div class="customer-phone">{{ $order->customer_phone }}</div>
          @endif
          <div class="customer-address">
            {{ $order->delivery_address }}<br>
            @if($order->city){{ $order->city }}@endif@if($order->state), {{ $order->state }}@endif@if($order->postcode) &ndash; {{ $order->postcode }}@endif
          </div>
        </div>
      </td>
    </tr>
  </table>

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
        <th>Product</th>
        <th class="right" style="width:15%">Qty</th>
      </tr>
    </thead>
    <tbody>
      @foreach($order->items as $i => $item)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>
          <div class="product-name">{{ $item->product_name }}</div>
          @if($item->variant_name)<span class="variant-tag">{{ $item->variant_name }}</span>@endif
        </td>
        <td class="right">{{ $item->quantity }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

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
