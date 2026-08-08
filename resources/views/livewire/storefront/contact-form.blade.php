<div class="bg-white rounded-3xl border border-amber-100 shadow-sm p-6 md:p-8">

    @if($submitted)
        <div class="text-center py-8">
            <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-lg font-extrabold text-stone-800 mb-1">Message sent!</h3>
            <p class="text-stone-500 text-sm mb-5">Thanks for reaching out — we'll get back to you shortly, usually on WhatsApp or phone.</p>
            <button wire:click="$set('submitted', false)" class="text-sm font-bold text-amber-600 hover:text-amber-700">
                Send another message
            </button>
        </div>
    @else
        <h2 class="font-extrabold text-stone-800 text-lg mb-1">Send us a message</h2>
        <p class="text-stone-500 text-sm mb-5">Prefer WhatsApp? Use the button below instead — replies are usually faster.</p>

        <form wire:submit="submit" class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">Full Name *</label>
                    <input wire:model="name" type="text" placeholder="Your name"
                           class="w-full border-2 {{ $errors->has('name') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-colors bg-white placeholder-stone-300">
                    @error('name') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">Phone Number *</label>
                    <input wire:model="phone" type="tel" placeholder="93600 64278"
                           class="w-full border-2 {{ $errors->has('phone') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-colors bg-white placeholder-stone-300">
                    @error('phone') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">
                    Email <span class="font-normal text-stone-400 normal-case">(optional)</span>
                </label>
                <input wire:model="email" type="email" placeholder="you@example.com"
                       class="w-full border-2 {{ $errors->has('email') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-colors bg-white placeholder-stone-300">
                @error('email') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-extrabold text-stone-600 mb-1.5 uppercase tracking-wide">Message *</label>
                <textarea wire:model="message" rows="4" placeholder="How can we help?"
                          class="w-full border-2 {{ $errors->has('message') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} rounded-xl px-4 py-2.5 text-sm focus:outline-none transition-colors bg-white placeholder-stone-300 resize-none"></textarea>
                @error('message') <p class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    wire:loading.attr="disabled"
                    class="w-full bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white font-extrabold py-3.5 rounded-xl transition-all shadow-sm hover:shadow flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="submit">Send Message</span>
                <span wire:loading wire:target="submit" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Sending…
                </span>
            </button>
        </form>
    @endif
</div>
