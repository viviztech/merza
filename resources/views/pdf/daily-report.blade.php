<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }

  .page { padding: 32px 36px; }

  /* Header */
  .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  .brand-name { font-size: 22px; font-weight: 700; color: #1B6B2F; }
  .report-title { font-size: 16px; font-weight: 700; color: #fff; background: #1B6B2F; padding: 6px 16px; text-align: center; border-radius: 4px; }
  .report-date { font-size: 12px; color: #555; font-weight: 600; text-align: right; margin-top: 4px; }

  .divider { border: none; border-top: 2px solid #1B6B2F; margin: 12px 0 20px; }

  /* Summary stats */
  .stats-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  .stat-box { text-align: center; padding: 12px 8px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; }
  .stat-value { font-size: 20px; font-weight: 700; color: #1B6B2F; }
  .stat-label { font-size: 9px; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: 1px; margin-top: 2px; }

  /* Orders table */
  .orders-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  .orders-table th { background: #1B6B2F; color: #fff; padding: 8px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; }
  .orders-table th.right { text-align: right; }
  .orders-table td { padding: 8px 8px; border-bottom: 1px solid #f0f0f0; font-size: 10px; vertical-align: top; }
  .orders-table td.right { text-align: right; font-weight: 600; }
  .orders-table tr:last-child td { border-bottom: none; }
  .orders-table tr:nth-child(even) td { background: #f9fafb; }

  .order-num { font-weight: 700; color: #1B6B2F; }
  .customer-name { font-weight: 600; }
  .customer-phone { font-size: 9px; color: #888; }
  .items-list { font-size: 9px; color: #555; line-height: 1.5; }

  /* Badge */
  .badge { display: inline-block; padding: 1px 8px; border-radius: 10px; font-size: 9px; font-weight: 700; text-transform: uppercase; }
  .badge-green  { background: #d1fae5; color: #065f46; }
  .badge-yellow { background: #fef9c3; color: #854d0e; }
  .badge-blue   { background: #dbeafe; color: #1e3a8a; }
  .badge-red    { background: #fee2e2; color: #991b1b; }
  .badge-gray   { background: #f3f4f6; color: #374151; }

  .paid-badge   { background: #d1fae5; color: #065f46; }
  .unpaid-badge { background: #fef9c3; color: #854d0e; }

  /* Total row */
  .total-row td { font-weight: 700; background: #f0fdf4; border-top: 2px solid #1B6B2F; }

  /* Footer */
  .footer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #e0e0e0; font-size: 9px; color: #999; text-align: center; }

  /* Empty state */
  .empty { text-align: center; padding: 40px; color: #888; font-size: 13px; }
</style>
</head>
<body>
<div class="page">

  {{-- Header --}}
  <table class="header-table">
    <tr>
      <td>
        <div class="brand-name">Merza Bodi</div>
        <div style="font-size:9px;color:#777;margin-top:2px;">Premium Tropical Fruits &bull; Bodinayakanur, Tamil Nadu</div>
      </td>
      <td style="text-align:right; vertical-align:top;">
        <span class="report-title">DAILY ORDER REPORT</span>
        <div class="report-date">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</div>
        <div style="font-size:9px;color:#999;margin-top:2px;">Generated: {{ now()->format('d M Y, h:i A') }}</div>
      </td>
    </tr>
  </table>

  <hr class="divider">

  @php
    $totalRevenue   = $orders->whereNotIn('status', ['cancelled'])->sum('total');
    $totalOrders    = $orders->count();
    $paidOrders     = $orders->where('payment_status', 'paid')->count();
    $cancelledCount = $orders->where('status', 'cancelled')->count();
  @endphp

  {{-- Summary Stats --}}
  <table class="stats-table">
    <tr>
      <td style="width:25%;padding-right:8px;"><div class="stat-box"><div class="stat-value">{{ $totalOrders }}</div><div class="stat-label">Total Orders</div></div></td>
      <td style="width:25%;padding:0 4px;"><div class="stat-box"><div class="stat-value">Rs. {{ number_format($totalRevenue, 0) }}</div><div class="stat-label">Revenue</div></div></td>
      <td style="width:25%;padding:0 4px;"><div class="stat-box"><div class="stat-value">{{ $paidOrders }}</div><div class="stat-label">Paid</div></div></td>
      <td style="width:25%;padding-left:8px;"><div class="stat-box"><div class="stat-value">{{ $cancelledCount }}</div><div class="stat-label">Cancelled</div></div></td>
    </tr>
  </table>

  @if($orders->isEmpty())
  <div class="empty">No orders for this date.</div>
  @else
  {{-- Orders Table --}}
  <table class="orders-table">
    <thead>
      <tr>
        <th style="width:12%">Order #</th>
        <th style="width:20%">Customer</th>
        <th style="width:30%">Items</th>
        <th style="width:10%">Status</th>
        <th style="width:10%">Payment</th>
        <th class="right" style="width:12%">Total</th>
        <th style="width:6%">Time</th>
      </tr>
    </thead>
    <tbody>
      @php $grandTotal = 0; @endphp
      @foreach($orders as $order)
      @php
        $statusColors = ['delivered'=>'badge-green','confirmed'=>'badge-blue','preparing'=>'badge-blue','delivering'=>'badge-green','cancelled'=>'badge-red','pending'=>'badge-yellow'];
        $statusColor = $statusColors[$order->status] ?? 'badge-gray';
        if ($order->status !== 'cancelled') $grandTotal += $order->total;
      @endphp
      <tr>
        <td><span class="order-num">{{ $order->order_number }}</span></td>
        <td>
          <div class="customer-name">{{ $order->customer_name }}</div>
          <div class="customer-phone">{{ $order->customer_phone }}</div>
        </td>
        <td>
          <div class="items-list">
            @foreach($order->items as $item)
              &bull; {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif &times; {{ $item->quantity }}<br>
            @endforeach
          </div>
        </td>
        <td><span class="badge {{ $statusColor }}">{{ $order->status }}</span></td>
        <td>
          @if($order->payment_status === 'paid')
            <span class="badge paid-badge">Paid</span>
          @else
            <span class="badge unpaid-badge">Unpaid</span>
          @endif
        </td>
        <td class="right">Rs. {{ number_format($order->total, 2) }}</td>
        <td style="font-size:9px;color:#888;">{{ $order->created_at->format('h:i A') }}</td>
      </tr>
      @endforeach
      <tr class="total-row">
        <td colspan="5" style="text-align:right;padding-right:12px;">Total Revenue (excl. cancelled)</td>
        <td class="right">Rs. {{ number_format($grandTotal, 2) }}</td>
        <td></td>
      </tr>
    </tbody>
  </table>
  @endif

  {{-- Footer --}}
  <div class="footer">
    Merza Bodi &bull; HP Petrol Bunk, Pankajam School Opp., Thevaram Road, Bodinayakanur &ndash; 625513 &bull; +91 86676 96278 &bull; merzabodi.com
  </div>

</div>
</body>
</html>
