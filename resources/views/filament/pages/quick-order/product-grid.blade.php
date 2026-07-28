<div class="space-y-4">
    @forelse ($variants->groupBy(fn ($variant) => $variant->product->name) as $productName => $productVariants)
        <div>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ $productName }}
            </p>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($productVariants as $variant)
                    <button
                        type="button"
                        wire:click="addItemToCart({{ $variant->id }})"
                        wire:loading.attr="disabled"
                        wire:target="addItemToCart({{ $variant->id }})"
                        @disabled($variant->stock_qty <= 0)
                        class="group relative flex flex-col items-start gap-0.5 rounded-xl border border-gray-200 bg-white p-3 text-left shadow-sm transition hover:border-primary-400 hover:shadow-md active:scale-[0.97] disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-400"
                    >
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $variant->name }}</span>
                        <span class="text-base font-bold text-primary-600 dark:text-primary-400">
                            &#8377;{{ number_format($variant->price, 0) }}
                        </span>
                        @if ($variant->stock_qty <= 0)
                            <span class="text-xs font-medium text-danger-600 dark:text-danger-400">Out of stock</span>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">Tap to add</span>
                        @endif

                        <span
                            wire:loading.flex
                            wire:target="addItemToCart({{ $variant->id }})"
                            class="absolute inset-0 hidden items-center justify-center rounded-xl bg-white/70 dark:bg-gray-900/70"
                        >
                            <svg class="h-5 w-5 animate-spin text-primary-600" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">No active products to sell right now.</p>
    @endforelse
</div>
