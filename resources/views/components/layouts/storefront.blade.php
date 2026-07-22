<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description ?? 'Merza — Premium Tropical Fruits delivered fresh to your door. Mangoes, Jackfruit, Banana & more.' }}">

    <title>{{ isset($title) ? $title . ' | Merza' : 'Merza — Premium Tropical Fruits' }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/icon-16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icon-32.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/images/icon-48.png">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="57x57"  href="/images/icon-57.png">
    <link rel="apple-touch-icon" sizes="60x60"  href="/images/icon-60.png">
    <link rel="apple-touch-icon" sizes="72x72"  href="/images/icon-72.png">
    <link rel="apple-touch-icon" sizes="76x76"  href="/images/icon-76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/icon-114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/icon-120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/icon-144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/icon-152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icon-180.png">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#D97706">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Merza">
    <meta name="application-name" content="Merza">

    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileColor" content="#D97706">
    <meta name="msapplication-TileImage" content="/images/icon-144.png">
    <meta name="msapplication-square70x70logo" content="/images/icon-70.png">
    <meta name="msapplication-square150x150logo" content="/images/icon-150.png">
    <meta name="msapplication-square310x310logo" content="/images/icon-310.png">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ isset($title) ? $title . ' | Merza' : 'Merza — Premium Tropical Fruits' }}">
    <meta property="og:description" content="{{ $description ?? 'Merza — Premium Tropical Fruits delivered fresh to your door. Mangoes, Jackfruit, Banana & more.' }}">
    <meta property="og:image" content="/images/icon-512.png">
    <meta property="og:type" content="website">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<style>[x-cloak]{display:none!important}</style>
<body class="bg-white font-sans antialiased text-stone-900 pb-20 md:pb-0">

    {{-- Announcement bar --}}
    <div x-data="{ show: !localStorage.getItem('merza_ann_v3') }" x-show="show" x-cloak
         class="relative bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 text-white text-center text-xs sm:text-sm py-2.5 px-10 font-semibold">
        🥭 Kasa Lattu Mango Season is Here! &nbsp;·&nbsp;
        <a href="{{ route('products.index') }}" class="underline font-extrabold hover:text-amber-100 transition-colors">Shop Now →</a>
        <button @click="show=false; localStorage.setItem('merza_ann_v3','1')"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition-colors p-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Sticky header --}}
    <header x-data="{ mobileOpen: false }" class="sticky top-0 z-40">

        {{-- Main bar --}}
        <div class="bg-white/95 backdrop-blur-sm border-b border-amber-100 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-3">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center flex-shrink-0">
                    <img src="/images/logo.png" alt="Merza" class="h-10 w-auto">
                </a>

                {{-- Desktop nav links (md+) --}}
                <nav class="hidden md:flex items-center gap-0.5 text-sm font-semibold">
                    @php
                        $navLinks = [
                            ['Home',     route('home'),           'home'],
                            ['Products', route('products.index'), 'products.*'],
                            ['About',    route('about'),          'about'],
                        ];
                    @endphp
                    @foreach($navLinks as [$label, $href, $match])
                        <a href="{{ $href }}"
                           class="px-3.5 py-2 rounded-lg transition-colors
                                  {{ request()->routeIs($match)
                                        ? 'text-brand-green-dark bg-amber-50'
                                        : 'text-stone-600 hover:text-brand-green-dark hover:bg-amber-50' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                    <a href="https://wa.me/919360064278?text=Hi%2C+I+want+to+enquire+about+Merza!"
                       target="_blank"
                       class="px-3.5 py-2 rounded-lg text-stone-600 hover:text-brand-green-dark hover:bg-amber-50 transition-colors">
                        Contact
                    </a>
                </nav>

                {{-- Right side actions --}}
                <div class="flex items-center gap-2">

                    {{-- Cart (hidden on mobile — the bottom tab bar already has a cart button there) --}}
                    <div x-data="{ count: {{ session('cart_count', 0) }} }"
                         x-on:cart-updated.window="count = $event.detail?.count ?? count"
                         class="hidden md:block">
                        <a href="{{ route('cart.index') }}"
                           class="relative flex items-center gap-1.5 px-3 py-2 rounded-xl border-2 border-amber-200 bg-amber-50 hover:bg-amber-100 hover:border-amber-300 text-brand-green-dark font-semibold text-sm transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span class="hidden sm:inline">Cart</span>
                            <span x-show="count > 0" x-text="count"
                                  class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center shadow">
                            </span>
                        </a>
                    </div>

                    {{-- WhatsApp (desktop only) --}}
                    <a href="https://wa.me/919360064278?text=Hi%2C+I+want+to+order+from+Merza!"
                       target="_blank"
                       class="hidden md:flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition-all shadow-sm">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        WhatsApp
                    </a>

                    {{-- Account dropdown (desktop, auth) --}}
                    @auth
                        <div class="hidden md:block relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="flex items-center gap-2 pl-3 pr-2.5 py-2 rounded-xl text-sm font-semibold text-stone-700
                                           border border-stone-200 hover:border-amber-300 hover:bg-amber-50 transition-all">
                                <span class="w-6 h-6 rounded-full bg-amber-100 text-brand-green-dark flex items-center justify-center text-xs font-extrabold">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="max-w-[90px] truncate">{{ auth()->user()->name }}</span>
                                <svg class="w-3.5 h-3.5 text-stone-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Dropdown --}}
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 @click.outside="open = false"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-stone-100 py-2 z-50 origin-top-right">

                                <div class="px-4 py-2 border-b border-stone-100 mb-1">
                                    <p class="text-xs font-bold text-stone-800 truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-stone-400 truncate">{{ auth()->user()->email }}</p>
                                </div>

                                <a href="{{ route('account.dashboard') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-stone-700 hover:bg-amber-50 hover:text-brand-green-dark transition-colors">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    Dashboard
                                </a>
                                <a href="{{ route('account.orders') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-stone-700 hover:bg-amber-50 hover:text-brand-green-dark transition-colors">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    My Orders
                                </a>
                                <a href="{{ route('account.profile') }}"
                                   class="flex items-center gap-2.5 px-4 py-2 text-sm text-stone-700 hover:bg-amber-50 hover:text-brand-green-dark transition-colors">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Profile
                                </a>
                                <div class="border-t border-stone-100 my-1.5 mx-2"></div>
                                <form method="POST" action="{{ route('customer.logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        {{-- Guest: Sign In + Register (desktop) --}}
                        <div class="hidden md:flex items-center gap-2">
                            <a href="{{ route('login') }}"
                               class="text-sm font-semibold text-stone-600 hover:text-brand-green-dark px-3.5 py-2 rounded-xl hover:bg-amber-50 transition-colors">
                                Sign In
                            </a>
                            <a href="{{ route('customer.register') }}"
                               class="text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 px-4 py-2 rounded-xl transition-all shadow-sm hover:shadow">
                                Register
                            </a>
                        </div>
                    @endauth

                    {{-- Hamburger (mobile only) --}}
                    <button @click="mobileOpen = !mobileOpen"
                            class="md:hidden flex items-center justify-center w-10 h-10 rounded-xl text-stone-600 hover:bg-amber-50 hover:text-brand-green-dark transition-colors">
                        <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile slide-down menu --}}
        <div x-show="mobileOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             @click.outside="mobileOpen = false"
             class="md:hidden bg-white border-b border-amber-100 shadow-lg">

            {{-- Auth info strip --}}
            @auth
                <div class="flex items-center gap-3 px-4 py-3 border-b border-stone-100 bg-amber-50">
                    <span class="w-9 h-9 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-sm font-extrabold shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-stone-800 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-stone-500 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            @endauth

            <nav class="px-3 py-3 space-y-0.5">
                <a href="{{ route('home') }}" @click="mobileOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors
                          {{ request()->routeIs('home') ? 'bg-amber-50 text-brand-green-dark' : 'text-stone-700 hover:bg-stone-50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Home
                </a>
                <a href="{{ route('products.index') }}" @click="mobileOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors
                          {{ request()->routeIs('products.*') ? 'bg-amber-50 text-brand-green-dark' : 'text-stone-700 hover:bg-stone-50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    Products
                </a>
                <a href="{{ route('about') }}" @click="mobileOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors
                          {{ request()->routeIs('about') ? 'bg-amber-50 text-brand-green-dark' : 'text-stone-700 hover:bg-stone-50' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    About
                </a>
                <a href="https://wa.me/919360064278?text=Hi%2C+I+want+to+enquire+about+Merza!" target="_blank" @click="mobileOpen = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors">
                    <svg class="w-5 h-5 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Contact on WhatsApp
                </a>

                @auth
                    <div class="border-t border-stone-100 my-2"></div>
                    <a href="{{ route('account.dashboard') }}" @click="mobileOpen = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-stone-700 hover:bg-stone-50 transition-colors">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        My Orders
                    </a>
                    <div class="px-3 pt-1 pb-2">
                        <form method="POST" action="{{ route('customer.logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm font-bold text-red-600 border border-red-200 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Sign out
                            </button>
                        </form>
                    </div>
                @else
                    <div class="border-t border-stone-100 my-2"></div>
                    <div class="px-3 pb-2 grid grid-cols-2 gap-2">
                        <a href="{{ route('login') }}" @click="mobileOpen = false"
                           class="flex items-center justify-center py-2.5 rounded-xl text-sm font-bold text-brand-green-dark border-2 border-amber-300 hover:bg-amber-50 transition-colors">
                            Sign In
                        </a>
                        <a href="{{ route('customer.register') }}" @click="mobileOpen = false"
                           class="flex items-center justify-center py-2.5 rounded-xl text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 transition-all shadow-sm">
                            Register
                        </a>
                    </div>
                @endauth
            </nav>
        </div>

    </header>

    {{-- Page content --}}
    <main>
        {{ $slot }}
    </main>

    {{-- Footer (desktop + mobile) --}}
    <footer id="about" class="bg-emerald-900 text-white mt-12">
        <div class="max-w-7xl mx-auto px-4 pt-12 pb-8">
            <div class="grid md:grid-cols-4 gap-8 mb-8">

                {{-- Brand --}}
                <div class="md:col-span-1">
                    <div class="flex items-center mb-3 bg-white rounded-xl p-2 w-fit">
                        <img src="/images/logo.png" alt="Merza Natural Squash" loading="lazy" class="h-10 w-auto">
                    </div>
                    <p class="text-emerald-300 text-sm leading-relaxed">
                        Premium tropical fruits delivered fresh from the farm to your door. Quality you can taste.
                    </p>
                    <div class="flex items-center gap-1 mt-4">
                        <span class="w-2 h-2 rounded-full bg-green-400 pulse-dot"></span>
                        <span class="text-xs text-emerald-400">Open Mon–Sat, 9am–6pm</span>
                    </div>
                </div>

                {{-- Products --}}
                <div>
                    <h4 class="font-bold text-sm text-emerald-100 mb-3 uppercase tracking-wider">Our Fruits</h4>
                    <ul class="space-y-2 text-sm">
                        @php $footerProducts = \App\Models\Product::where('is_active', true)->orderBy('sort_order')->limit(5)->get(); @endphp
                        @forelse($footerProducts as $fp)
                            <li>
                                <a href="{{ route('products.show', $fp->slug) }}" class="text-emerald-300 hover:text-amber-400 transition-colors flex items-center gap-2">
                                    <span>🥭</span> {{ $fp->name }}
                                </a>
                            </li>
                        @empty
                            <li>
                                <a href="{{ route('products.index') }}" class="text-emerald-300 hover:text-amber-400 transition-colors flex items-center gap-2">
                                    <span>🥭</span> Shop All Fruits
                                </a>
                            </li>
                        @endforelse
                    </ul>
                </div>

                {{-- Company --}}
                <div>
                    <h4 class="font-bold text-sm text-emerald-100 mb-3 uppercase tracking-wider">Company</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('about') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">About Us</a></li>
                        <li><a href="{{ route('track.index') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">Track Your Order</a></li>
                        <li><a href="{{ route('faq') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">FAQ</a></li>
                        <li><a href="{{ route('blog') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">Blog & Recipes</a></li>
                        <li><a href="{{ route('wholesale') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">B2B Wholesale</a></li>
                        <li><a href="{{ route('careers') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">Careers</a></li>
                        <li><a href="{{ route('privacy') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">Terms &amp; Conditions</a></li>
                    </ul>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="font-bold text-sm text-emerald-100 mb-3 uppercase tracking-wider">Order Now</h4>
                    <a href="https://wa.me/919360064278?text=Hi%2C+I+want+to+place+an+order!"
                       target="_blank"
                       class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-400 text-white font-bold px-5 py-3 rounded-xl text-sm transition-all shadow hover:shadow-lg">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Chat on WhatsApp
                    </a>
                    <div class="mt-4 space-y-1 text-xs text-emerald-400">
                        <p>📍 HP Petrol Bunk, Pankajam School Opp.,</p>
                        <p class="pl-5">Thevaram Road, Bodinayakanur — 625513</p>
                        <p>📞 +91 86676 96278</p>
                        <p>✉️ merzabodinayakanur@gmail.com</p>
                    </div>
                </div>
            </div>

            <div class="border-t border-emerald-800 pt-6 flex flex-col md:flex-row items-center justify-between gap-2 text-xs text-emerald-500">
                <span>© {{ date('Y') }} Merza Bodi. All rights reserved.</span>
                <span>Made with 🥭 in India</span>
            </div>
        </div>
    </footer>

    {{-- Mobile bottom tab bar --}}
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-sm border-t border-stone-100 shadow-[0_-1px_12px_rgba(0,0,0,0.06)]"
         style="padding-bottom: env(safe-area-inset-bottom)">
        @php
            $isAuth = auth()->check();
            $isAccount = request()->routeIs('account.*');
        @endphp
        <div class="grid h-16" style="grid-template-columns: repeat(4, 1fr)">

            {{-- Home --}}
            <a href="{{ route('home') }}"
               class="flex flex-col items-center justify-center gap-0.5 transition-colors
                      {{ request()->routeIs('home') ? 'text-brand-green-dark' : 'text-stone-400' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('home') ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-[10px] font-semibold">Home</span>
            </a>

            {{-- Products --}}
            <a href="{{ route('products.index') }}"
               class="flex flex-col items-center justify-center gap-0.5 transition-colors
                      {{ request()->routeIs('products.*') ? 'text-brand-green-dark' : 'text-stone-400' }}">
                <svg class="w-5 h-5" fill="{{ request()->routeIs('products.*') ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span class="text-[10px] font-semibold">Products</span>
            </a>

            {{-- Cart (elevated center button) --}}
            <div x-data="{ count: {{ session('cart_count', 0) }} }"
                 x-on:cart-updated.window="count = $event.detail?.count ?? count"
                 class="flex flex-col items-center justify-center">
                <a href="{{ route('cart.index') }}" class="relative -mt-5 flex flex-col items-center gap-0.5">
                    <span class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-300/50 border-2 border-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span x-show="count > 0" x-text="count"
                              class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center border border-white">
                        </span>
                    </span>
                    <span class="text-[10px] font-semibold {{ request()->routeIs('cart.*') ? 'text-brand-green-dark' : 'text-stone-400' }}">Cart</span>
                </a>
            </div>

            {{-- Account / Sign In --}}
            @auth
                <a href="{{ route('account.dashboard') }}"
                   class="flex flex-col items-center justify-center gap-0.5 transition-colors {{ $isAccount ? 'text-brand-green-dark' : 'text-stone-400' }}">
                    <span class="w-5 h-5 rounded-full {{ $isAccount ? 'bg-amber-500 text-white' : 'bg-stone-200 text-stone-600' }} flex items-center justify-center text-[10px] font-extrabold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <span class="text-[10px] font-semibold">Account</span>
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="flex flex-col items-center justify-center gap-0.5 text-stone-400 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-[10px] font-semibold">Sign In</span>
                </a>
            @endauth

        </div>
    </nav>

    {{-- Floating WhatsApp (desktop only — mobile already has "Contact on WhatsApp" in the menu) --}}
    <a href="https://wa.me/919360064278?text=Hi%2C%20I%20want%20to%20order%20from%20Merza!"
       target="_blank"
       class="hidden md:flex fixed bottom-6 left-6 z-50 bg-green-500 hover:bg-green-400 text-white rounded-2xl w-14 h-14 items-center justify-center shadow-xl transition-all hover:scale-110 hover:shadow-2xl"
       style="width:52px;height:52px;" title="Order on WhatsApp">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>

    @livewireScripts
</body>
</html>
