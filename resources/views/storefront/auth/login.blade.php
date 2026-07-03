<x-layouts.storefront title="Sign In">
    <section class="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            <div class="text-center mb-8">
                <img src="/images/logo.png" alt="Merza" class="h-14 w-auto mx-auto mb-4">
                <h1 class="text-2xl font-extrabold text-stone-900">Welcome back</h1>
                <p class="text-stone-500 text-sm mt-1">Sign in to track your orders</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-8">

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-5">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Email address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-stone-700 mb-1.5">Password</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-stone-600 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-stone-300 text-amber-500">
                            Remember me
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-xl text-sm transition-all shadow-sm hover:shadow">
                        Sign In
                    </button>
                </form>

                <p class="text-center text-sm text-stone-500 mt-6">
                    Don't have an account?
                    <a href="{{ route('customer.register') }}" class="text-amber-600 font-semibold hover:text-amber-700">Create one</a>

                </p>
            </div>

            <p class="text-center text-xs text-stone-400 mt-5">
                Are you staff?
                <a href="/admin/login" class="text-stone-500 hover:text-stone-700 underline">Admin panel →</a>
            </p>
        </div>
    </section>
</x-layouts.storefront>
