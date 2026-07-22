<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  @font-face { font-family: 'Noto Sans Tamil'; src: url('{{ public_path('fonts/pdf/NotoSansTamil-Regular.ttf') }}'); font-weight: normal; font-style: normal; }
  @font-face { font-family: 'Noto Sans Tamil'; src: url('{{ public_path('fonts/pdf/NotoSansTamil-Bold.ttf') }}'); font-weight: bold; font-style: normal; }

  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', 'Noto Sans Tamil', sans-serif; font-size: 12px; color: #000; background: #fff; }

  .page { padding: 10px 14px; }

  .order-ref { text-align: right; font-size: 7px; color: #666; margin-bottom: 5px; }

  .block { margin-bottom: 8px; }
  .block-heading { font-size: 13px; font-weight: 700; margin-bottom: 4px; text-transform: uppercase; }
  .block-body { padding-left: 18px; }
  .party-name { font-size: 12px; font-weight: 700; margin-bottom: 2px; }
  .party-address { font-size: 10px; line-height: 1.3; margin-bottom: 2px; }
  .party-mobile { font-size: 10px; font-weight: 700; }

  .to-address { font-size: 14px; font-weight: 700; line-height: 1.35; }

  .from-box { border: 1px solid #000; padding: 6px 8px; }
</style>
</head>
<body>
<div class="page">

  <div class="order-ref">Order: {{ $order->order_number }} &bull; {{ $order->created_at->format('d M Y') }}</div>

  {{-- To --}}
  <div class="block">
    <div class="block-heading">To:</div>
    <div class="block-body">
      <div class="party-name">{{ $order->customer_name }}</div>
      <div class="party-address to-address">
        {{ $order->delivery_address }}<br>
        {{ collect([$order->city, $order->state])->filter()->implode(', ') }}@if($order->postcode)-{{ $order->postcode }}@endif
      </div>
      @if($order->customer_phone)
        <div class="party-mobile">Mobile : {{ $order->customer_phone }}</div>
      @endif
    </div>
  </div>

  {{-- From --}}
  <div class="block">
    <div class="block-heading">From:</div>
    <div class="block-body from-box">
      <div class="party-name">Merza</div>
      <div class="party-address">
        Bodinayakanur, Theni District - 625513
      </div>
      <div class="party-mobile">Mobile : 8667696278</div>
    </div>
  </div>

</div>
</body>
</html>
