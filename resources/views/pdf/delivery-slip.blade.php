<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

  .page { padding: 18px 26px; }

  /* Letterhead */
  .letterhead-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  .brand-name { font-size: 19px; font-weight: 700; color: #1B6B2F; letter-spacing: 0.5px; }
  .brand-tagline { font-size: 9px; color: #777; margin-top: 1px; }
  .company-address { font-size: 9px; color: #555; line-height: 1.5; margin-top: 5px; }
  .slip-title { display: inline-block; font-size: 13px; font-weight: 700; color: #fff; background: #1B6B2F; padding: 7px 12px; text-align: center; border-radius: 4px; line-height: 1.3; }

  .divider { border: none; border-top: 2px solid #1B6B2F; margin: 8px 0; }

  /* Order Number Banner */
  .order-banner { background: #f0fdf4; border: 2px solid #1B6B2F; border-radius: 6px; padding: 7px 14px; margin-bottom: 10px; text-align: center; }
  .order-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; }
  .order-number { font-size: 20px; font-weight: 700; color: #1B6B2F; margin-top: 2px; }
  .order-date { font-size: 10px; color: #666; margin-top: 2px; }

  /* Address section */
  .section-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; margin-bottom: 4px; }
  .deliver-box { border: 2px solid #1a1a1a; border-radius: 6px; padding: 16px 18px; margin-bottom: 10px; }
  .customer-name { font-size: 26px; font-weight: 700; color: #1a1a1a; margin-bottom: 6px; }
  .customer-phone { font-size: 18px; color: #1B6B2F; font-weight: 700; margin-bottom: 8px; }
  .customer-address { font-size: 22px; font-weight: 700; color: #1a1a1a; line-height: 1.4; }

  /* Tracking */
  .tracking-box { padding: 7px 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; margin-bottom: 10px; font-size: 11px; }
  .tracking-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #92400e; letter-spacing: 1px; }
  .tracking-number { font-size: 14px; font-weight: 700; color: #1a1a1a; margin-top: 2px; letter-spacing: 1px; }

  /* Signature box */
  .sig-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
  .sig-box { border-top: 1px solid #ccc; padding-top: 4px; font-size: 9px; color: #888; text-align: center; }

  /* Footer */
  .footer { margin-top: 10px; padding-top: 8px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 9px; color: #999; }
</style>
</head>
<body>
<div class="page">

  {{-- Letterhead --}}
  <table class="letterhead-table">
    <tr>
      <td style="width:72%">
        <div class="brand-name">Merza Bodi</div>
        <div class="brand-tagline">Premium Tropical Fruits &bull; Bodinayakanur, Tamil Nadu</div>
        <div class="company-address">
          HP Petrol Bunk, Pankajam School Opp., Thevaram Road, Bodinayakanur &ndash; 625513, Tamil Nadu<br>
          Phone: +91 93600 64278 &bull; Email: merzabodinayakanur@gmail.com
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
</body>
</html>
