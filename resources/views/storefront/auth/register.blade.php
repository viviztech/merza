<x-layouts.storefront title="Create Account">
    <section class="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            <div class="text-center mb-8">
                <img src="/images/logo.png" alt="Merza" class="h-14 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-extrabold text-stone-900">Create your account</h1>
                <p class="text-stone-500 text-sm mt-1">Shop Mukkani fruits and track your orders</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-8">

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-5">
                        <ul class="space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.register') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Full name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required autofocus
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Phone number <span class="font-normal text-stone-400">(optional)</span></label>
                        <input type="tel" name="phone" value="{{ old('phone') }}"
                               placeholder="+91 00000 00000"
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Email address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Password</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                        <p class="text-xs text-stone-400 mt-1">At least 8 characters</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Confirm password</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                    </div>

                    <button type="submit"
                            class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-xl text-sm transition-all shadow-sm hover:shadow">
                        Create Account
                    </button>
                </form>

                <p class="text-center text-sm text-stone-500 mt-6">
                    Already have an account?
                    <a href="{{ route('customer.login') }}" class="text-amber-600 font-semibold hover:text-amber-700">Sign in</a>
                </p>
            </div>
        </div>
    </section>
</x-layouts.storefront>
