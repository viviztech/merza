<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Top Products by Revenue</x-slot>

        @php $products = $this->getTopProducts(); @endphp

        @if ($products->isEmpty())
            <p class="text-sm text-gray-400 py-4">No order data yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10 text-left text-xs text-gray-500 uppercase tracking-wide">
                            <th class="pb-2 pr-6">#</th>
                            <th class="pb-2 pr-6">Product</th>
                            <th class="pb-2 pr-6 text-right">Orders</th>
                            <th class="pb-2 pr-6 text-right">Units Sold</th>
                            <th class="pb-2 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($products as $i => $row)
                            <tr>
                                <td class="py-2 pr-6 text-gray-400 font-mono text-xs">{{ $i + 1 }}</td>
                                <td class="py-2 pr-6 font-medium text-gray-900 dark:text-white">
                                    {{ $row->product_name }}
                                    @if ($row->sku)
                                        <span class="text-xs text-gray-400 ml-1">{{ $row->sku }}</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-6 text-right text-gray-600 dark:text-gray-300">{{ $row->order_count }}</td>
                                <td class="py-2 pr-6 text-right text-gray-600 dark:text-gray-300">{{ $row->total_qty }}</td>
                                <td class="py-2 text-right font-semibold text-emerald-600 dark:text-emerald-400">
                                    &#x20B9;{{ number_format($row->total_revenue, 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
