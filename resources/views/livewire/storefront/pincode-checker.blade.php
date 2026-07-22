<div class="bg-white border border-stone-100 rounded-2xl p-4">
    <p class="text-xs font-bold text-stone-500 uppercase tracking-wide mb-2">🚚 Check Delivery to Your Area</p>
    <form wire:submit="check" class="flex gap-2">
        <input type="text" wire:model="pincode" maxlength="6" inputmode="numeric" placeholder="Enter pincode"
               class="flex-1 rounded-xl border-stone-200 focus:border-amber-400 focus:ring-amber-400 text-sm">
        <button type="submit" wire:loading.attr="disabled" wire:target="check"
                class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 rounded-xl text-sm transition-all">
            <span wire:loading.remove wire:target="check">Check</span>
            <span wire:loading wire:target="check">…</span>
        </button>
    </form>
    @error('pincode') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror

    @if($checked)
        @if($serviceable)
            <p class="text-sm text-emerald-700 font-semibold mt-3">
                ✅ We deliver here — arrives in ~{{ $etaDays }} day{{ $etaDays == 1 ? '' : 's' }} ({{ $zoneName }})
            </p>
        @else
            <p class="text-sm text-amber-700 font-semibold mt-3">
                We don't cover this area yet —
                <a href="https://wa.me/919360064278?text=Hi%2C+do+you+deliver+to+pincode+{{ $pincode }}%3F" target="_blank" class="underline">ask us on WhatsApp</a>.
            </p>
        @endif
    @endif
</div>
