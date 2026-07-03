<x-layouts.storefront title="My Profile">
    <div class="max-w-2xl mx-auto px-4 py-10">

        <div class="flex items-center gap-3 mb-8">
            <a href="{{ route('account.dashboard') }}" class="text-stone-400 hover:text-stone-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-2xl font-extrabold text-stone-900">My Profile</h1>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl px-4 py-3 mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-6">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Profile Info --}}
        <div class="bg-white border border-stone-100 rounded-2xl p-6 mb-5">
            <h2 class="text-base font-bold text-stone-800 mb-5">Personal Information</h2>
            <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1.5">Full name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1.5">Phone number</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1.5">Email address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                </div>

                <div class="pt-2">
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-all">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

        {{-- Change Password --}}
        <div class="bg-white border border-stone-100 rounded-2xl p-6">
            <h2 class="text-base font-bold text-stone-800 mb-5">Change Password</h2>
            <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1.5">Current password</label>
                    <input type="password" name="current_password" required
                           class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1.5">New password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-stone-700 mb-1.5">Confirm new password</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-4 py-2.5 rounded-xl border border-stone-200 focus:border-amber-400 focus:ring-2 focus:ring-amber-100 outline-none text-sm transition-all">
                </div>

                <div class="pt-2">
                    <button type="submit" class="bg-stone-700 hover:bg-stone-800 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-all">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.storefront>
