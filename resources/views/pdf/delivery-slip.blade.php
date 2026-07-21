<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  @font-face { font-family: 'Noto Sans Tamil'; src: url('{{ public_path('fonts/pdf/NotoSansTamil-Regular.ttf') }}'); font-weight: normal; font-style: normal; }
  @font-face { font-family: 'Noto Sans Tamil'; src: url('{{ public_path('fonts/pdf/NotoSansTamil-Bold.ttf') }}'); font-weight: bold; font-style: normal; }

  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Serif', 'Noto Sans Tamil', serif; font-size: 15px; color: #000; background: #fff; }

  .page { padding: 40px 45px; }

  .order-ref { text-align: right; font-family: 'DejaVu Sans', 'Noto Sans Tamil', sans-serif; font-size: 10px; color: #999; margin-bottom: 20px; }

  .block { margin-bottom: 55px; }
  .block-heading { font-size: 24px; font-weight: 700; margin-bottom: 14px; }
  .block-body { padding-left: 45px; }
  .party-name { font-size: 20px; font-weight: 700; margin-bottom: 6px; }
  .party-address { font-size: 17px; line-height: 1.6; margin-bottom: 6px; }
  .party-mobile { font-size: 17px; font-weight: 700; }
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
      <div class="party-address">
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
    <div class="block-heading">From :</div>
    <div class="block-body">
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
