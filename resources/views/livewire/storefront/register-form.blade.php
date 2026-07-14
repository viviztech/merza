<section class="min-h-[70vh] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <img src="/images/logo.png" alt="Merza" class="h-14 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-extrabold text-stone-900">Create your account</h1>
            <p class="text-stone-500 text-sm mt-1">Shop Mukkani fruits and track your orders</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-8">

            {{-- ── Error bag ── --}}
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-5">
                    <ul class="space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ══════════════════════════════════════════ --}}
            {{-- STEP 1: Registration form                 --}}
            {{-- ══════════════════════════════════════════ --}}
            @if($step === 'form')
                <form wire:submit="sendOtp" class="space-y-5">

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Full name *</label>
                        <input wire:model="name" type="text" required autofocus
                               placeholder="Your full name"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} focus:ring-2 focus:ring-amber-100 outline-none text-base transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Email address *</label>
                        <input wire:model="email" type="email" required
                               placeholder="you@example.com"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} focus:ring-2 focus:ring-amber-100 outline-none text-base transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">
                            WhatsApp number *
                            <span class="text-xs font-normal text-stone-400 ml-1">— for OTP verification</span>
                        </label>
                        <input wire:model="phone" type="tel" required
                               placeholder="+91 93600 64278"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('phone') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} focus:ring-2 focus:ring-amber-100 outline-none text-base transition-all">
                        <p class="text-[11px] text-stone-400 mt-1.5 leading-relaxed">
                            📱 We'll send a 6-digit code to this WhatsApp number to verify your account.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Password *</label>
                        <input wire:model="password" type="password" required
                               placeholder="At least 8 characters"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('password') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} focus:ring-2 focus:ring-amber-100 outline-none text-base transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Confirm password *</label>
                        <input wire:model="password_confirmation" type="password" required
                               placeholder="Repeat password"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('password_confirmation') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} focus:ring-2 focus:ring-amber-100 outline-none text-base transition-all">
                    </div>

                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-bold py-3 rounded-xl text-sm transition-all shadow-sm hover:shadow flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="sendOtp" class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            Send OTP to WhatsApp
                        </span>
                        <span wire:loading wire:target="sendOtp" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Sending OTP…
                        </span>
                    </button>
                </form>

            {{-- ══════════════════════════════════════════ --}}
            {{-- STEP 2: OTP verification                  --}}
            {{-- ══════════════════════════════════════════ --}}
            @elseif($step === 'otp')

                {{-- Sent confirmation banner --}}
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 mb-6 flex gap-3">
                    <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    <div class="text-sm text-emerald-800">
                        <p class="font-semibold">OTP sent via WhatsApp!</p>
                        <p class="text-emerald-700 mt-0.5">Check your WhatsApp at <strong>{{ $phone }}</strong>. Valid for 10 minutes.</p>
                    </div>
                </div>

                <form wire:submit="verifyOtp" class="space-y-5">

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Enter 6-digit OTP</label>
                        <input wire:model="otp" type="text" inputmode="numeric" pattern="[0-9]*"
                               maxlength="6" autofocus placeholder="_ _ _ _ _ _"
                               class="w-full px-4 py-3 rounded-xl border {{ $errors->has('otp') ? 'border-red-300 bg-red-50' : 'border-stone-200 focus:border-amber-400' }} focus:ring-2 focus:ring-amber-100 outline-none text-2xl tracking-[0.5em] font-bold text-center transition-all">
                        @error('otp')
                            <p class="text-red-500 text-xs mt-1.5 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="w-full bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white font-bold py-3 rounded-xl text-sm transition-all shadow-sm hover:shadow flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="verifyOtp">✓ Verify &amp; Create Account</span>
                        <span wire:loading wire:target="verifyOtp" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Creating account…
                        </span>
                    </button>

                </form>

                {{-- Resend / Back links --}}
                <div class="mt-5 flex items-center justify-between text-sm">
                    <button wire:click="goBack" type="button"
                            class="text-stone-400 hover:text-stone-600 transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Change number
                    </button>
                    <button wire:click="resendOtp" type="button"
                            wire:loading.attr="disabled"
                            class="text-amber-600 hover:text-amber-700 font-semibold transition-colors">
                        <span wire:loading.remove wire:target="resendOtp">Resend OTP</span>
                        <span wire:loading wire:target="resendOtp">Sending…</span>
                    </button>
                </div>

            @endif

            <p class="text-center text-sm text-stone-500 mt-6">
                Already have an account?
                <a href="{{ route('login') }}" class="text-amber-600 font-semibold hover:text-amber-700">Sign in</a>
            </p>
        </div>
    </div>
</section>
