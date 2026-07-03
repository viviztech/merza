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
<body class="bg-amber-50 font-sans antialiased text-stone-900 pb-20 md:pb-0">

    {{-- Sticky header --}}
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-amber-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between gap-4">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center flex-shrink-0">
                <img src="/images/logo.png" alt="Merza Natural Squash" class="h-10 w-auto">
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
                <a href="{{ route('home') }}"
                   class="px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('home') ? 'text-amber-700 bg-amber-50' : 'text-stone-600 hover:text-amber-700 hover:bg-amber-50' }}">
                    Home
                </a>
                <a href="{{ route('products.index') }}"
                   class="px-3 py-2 rounded-lg transition-colors {{ request()->routeIs('products.*') ? 'text-amber-700 bg-amber-50' : 'text-stone-600 hover:text-amber-700 hover:bg-amber-50' }}">
                    Products
                </a>
                <a href="#about"
                   class="px-3 py-2 rounded-lg text-stone-600 hover:text-amber-700 hover:bg-amber-50 transition-colors">
                    About
                </a>
                <a href="https://wa.me/918667696278?text=Hi%2C+I+want+to+enquire+about+Merza!" target="_blank"
                   class="px-3 py-2 rounded-lg text-stone-600 hover:text-amber-700 hover:bg-amber-50 transition-colors">
                    Contact
                </a>
            </nav>

            {{-- Right actions --}}
            <div class="flex items-center gap-2">

                {{-- Cart button --}}
                <a href="{{ route('cart.index') }}"
                   x-data="{ count: {{ session('cart_count', 0) }} }"
                   x-on:cart-updated.window="count = $event.detail?.count ?? (await fetch('/cart/count').then(r=>r.json()).then(d=>d.count).catch(()=>count))"
                   class="relative flex items-center gap-2 px-3 py-2 rounded-xl border-2 border-amber-200 bg-amber-50 hover:bg-amber-100 hover:border-amber-300 text-amber-700 font-semibold text-sm transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="hidden sm:inline">Cart</span>
                    <span x-show="count > 0"
                          x-text="count"
                          class="absolute -top-2 -right-2 bg-orange-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold shadow">
                    </span>
                </a>

                {{-- WhatsApp CTA --}}
                <a href="https://wa.me/918667696278?text=Hi%2C+I+want+to+order+from+Merza!"
                   target="_blank"
                   class="hidden md:flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition-all shadow-sm hover:shadow">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp
                </a>

                {{-- Account --}}
                @auth
                    <a href="{{ route('account.orders') }}" class="hidden md:flex items-center gap-1 text-sm text-stone-600 hover:text-amber-700 transition-colors px-2 py-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                @endauth
            </div>
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
                    <div class="flex items-center mb-3">
                        <img src="/images/logo.png" alt="Merza Natural Squash" class="h-10 w-auto brightness-0 invert">
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
                        @foreach([
                            ['🥭', 'Premium Mangoes'],
                            ['🍌', 'Banana Red'],
                            ['🍈', 'Vietnam Gold Jackfruit'],
                            ['🍋', 'Freeze Dried'],
                            ['🧃', 'Pulp & Puree'],
                        ] as [$icon, $name])
                            <li>
                                <a href="{{ route('products.index') }}" class="text-emerald-300 hover:text-amber-400 transition-colors flex items-center gap-2">
                                    <span>{{ $icon }}</span> {{ $name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Company --}}
                <div>
                    <h4 class="font-bold text-sm text-emerald-100 mb-3 uppercase tracking-wider">Company</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('about') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">About Us</a></li>
                        <li><a href="{{ route('blog') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">Blog & Recipes</a></li>
                        <li><a href="{{ route('wholesale') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">B2B Wholesale</a></li>
                        <li><a href="{{ route('careers') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">Careers</a></li>
                        <li><a href="{{ route('privacy') }}" class="text-emerald-300 hover:text-amber-400 transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="font-bold text-sm text-emerald-100 mb-3 uppercase tracking-wider">Order Now</h4>
                    <a href="https://wa.me/918667696278?text=Hi%2C+I+want+to+place+an+order!"
                       target="_blank"
                       class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-400 text-white font-bold px-5 py-3 rounded-xl text-sm transition-all shadow hover:shadow-lg">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        Chat on WhatsApp
                    </a>
                    <div class="mt-4 space-y-1 text-xs text-emerald-400">
                        <p>📍 Bodinayakanur, Theni - 625513, Tamil Nadu, India</p>
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
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-amber-100 safe-area-pb shadow-lg">
        <div class="grid grid-cols-5 h-16">
            {{-- Home --}}
            <a href="{{ route('home') }}" class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition-colors
               {{ request()->routeIs('home') ? 'text-amber-600' : 'text-stone-400 hover:text-amber-500' }}">
                <svg class="w-6 h-6" fill="{{ request()->routeIs('home') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Home</span>
            </a>

            {{-- Products --}}
            <a href="{{ route('products.index') }}" class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition-colors
               {{ request()->routeIs('products.*') ? 'text-amber-600' : 'text-stone-400 hover:text-amber-500' }}">
                <svg class="w-6 h-6" fill="{{ request()->routeIs('products.*') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span>Products</span>
            </a>

            {{-- Cart (center, prominent) --}}
            <a href="{{ route('cart.index') }}"
               x-data="{ count: {{ session('cart_count', 0) }} }"
               x-on:cart-updated.window="count = $event.detail?.count ?? count"
               class="relative flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium
                      {{ request()->routeIs('cart.*') ? 'text-amber-600' : 'text-stone-400 hover:text-amber-500' }}">
                <span class="relative -mt-6 flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-200 border-4 border-amber-50">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span x-show="count > 0" x-text="count"
                          class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] rounded-full w-4 h-4 flex items-center justify-center font-bold">
                    </span>
                </span>
                <span class="text-amber-600 font-semibold">Cart</span>
            </a>

            {{-- WhatsApp --}}
            <a href="https://wa.me/918667696278" target="_blank" class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium text-stone-400 hover:text-emerald-600 transition-colors">
                <svg class="w-6 h-6 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                <span>WhatsApp</span>
            </a>

            {{-- About --}}
            <a href="{{ route('about') }}"
               class="flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition-colors
                      {{ request()->routeIs('about') ? 'text-amber-600' : 'text-stone-400 hover:text-amber-500' }}">
                <svg class="w-6 h-6" fill="{{ request()->routeIs('about') ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>About</span>
            </a>
        </div>
    </nav>

    {{-- Floating WhatsApp (desktop) --}}
    <a href="https://wa.me/918667696278?text=Hi%2C%20I%20want%20to%20order%20from%20Merza!"
       target="_blank"
       class="hidden md:flex fixed bottom-6 right-6 z-50 bg-green-500 hover:bg-green-400 text-white rounded-2xl w-14 h-14 items-center justify-center shadow-xl transition-all hover:scale-110 hover:shadow-2xl">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>

    @livewireScripts
</body>
</html>
