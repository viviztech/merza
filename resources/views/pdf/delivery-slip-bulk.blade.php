<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

  .page { padding: 24px 28px; }

  /* Letterhead */
  .letterhead-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  .brand-name { font-size: 20px; font-weight: 700; color: #1B6B2F; letter-spacing: 0.5px; }
  .brand-tagline { font-size: 9px; color: #777; margin-top: 1px; }
  .company-address { font-size: 9px; color: #555; line-height: 1.6; margin-top: 6px; }
  .slip-title { display: inline-block; font-size: 13px; font-weight: 700; color: #fff; background: #1B6B2F; padding: 8px 12px; text-align: center; border-radius: 4px; line-height: 1.4; }

  .divider { border: none; border-top: 2px solid #1B6B2F; margin: 12px 0; }

  /* Order Number Banner */
  .order-banner { background: #f0fdf4; border: 2px solid #1B6B2F; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px; text-align: center; }
  .order-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; }
  .order-number { font-size: 22px; font-weight: 700; color: #1B6B2F; margin-top: 2px; }
  .order-date { font-size: 10px; color: #666; margin-top: 2px; }

  /* Address section */
  .section-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; margin-bottom: 5px; }
  .deliver-box { border: 2px solid #1a1a1a; border-radius: 6px; padding: 16px 18px; margin-bottom: 14px; }
  .customer-name { font-size: 20px; font-weight: 700; color: #1a1a1a; margin-bottom: 6px; }
  .customer-phone { font-size: 15px; color: #1B6B2F; font-weight: 600; margin-bottom: 8px; }
  .customer-address { font-size: 16px; color: #333; line-height: 1.7; }

  /* Tracking */
  .tracking-box { padding: 8px 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; margin-bottom: 14px; font-size: 11px; }
  .tracking-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #92400e; letter-spacing: 1px; }
  .tracking-number { font-size: 14px; font-weight: 700; color: #1a1a1a; margin-top: 2px; letter-spacing: 1px; }

  /* Signature box */
  .sig-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
  .sig-box { border-top: 1px solid #ccc; padding-top: 4px; font-size: 9px; color: #888; text-align: center; }

  /* Footer */
  .footer { margin-top: 16px; padding-top: 10px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 9px; color: #999; }
</style>
</head>
<body>

@foreach($orders as $order)
<div class="page" @if(! $loop->last) style="page-break-after: always;" @endif>

  {{-- Letterhead --}}
  <table class="letterhead-table">
    <tr>
      <td style="width:72%">
        <div class="brand-name">Merza Bodi</div>
        <div class="brand-tagline">Premium Tropical Fruits &bull; Bodinayakanur, Tamil Nadu</div>
        <div class="company-address">
          HP Petrol Bunk, Pankajam School Opp., Thevaram Road,<br>
          Bodinayakanur &ndash; 625513, Tamil Nadu, India<br>
          Phone: +91 93600 64278<br>
          Email: merzabodinayakanur@gmail.com
        </div>
      </td>
      <td style="width:28%; text-align:right; vertical-align:top;">
        <span class="slip-title">DELIVERY<br>CHALLAN</span>
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
@endforeach

</body>
</html>
