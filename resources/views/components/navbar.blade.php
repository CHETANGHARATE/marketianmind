<header class="sticky top-0 z-40 w-full border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <!-- Brand Logo -->
        <div class="flex items-center gap-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold text-xl text-slate-900 tracking-tight">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white font-black text-lg shadow-sm">
                    M
                </span>
                <span>Marketian<span class="text-indigo-600">Mind</span></span>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-7 text-sm font-medium text-slate-600">
                <a href="{{ route('home') }}" class="transition hover:text-indigo-600 {{ request()->routeIs('home') ? 'text-indigo-600 font-semibold' : '' }}">
                    Home
                </a>
                <a href="{{ route('about') }}" class="transition hover:text-indigo-600 {{ request()->routeIs('about') ? 'text-indigo-600 font-semibold' : '' }}">
                    About
                </a>
                <a href="{{ route('courses') }}" class="transition hover:text-indigo-600 {{ request()->routeIs('courses*') ? 'text-indigo-600 font-semibold' : '' }}">
                    Courses
                </a>
                <a href="{{ route('contact') }}" class="transition hover:text-indigo-600 {{ request()->routeIs('contact') ? 'text-indigo-600 font-semibold' : '' }}">
                    Contact
                </a>
            </nav>
        </div>

        <!-- Right Side Desktop Actions -->
        <div class="hidden md:flex items-center gap-4">
            <a href="#login" class="text-sm font-medium text-slate-700 hover:text-indigo-600 transition">
                Login
            </a>
            <a href="{{ route('courses') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                Get Started
            </a>
        </div>

        <!-- Mobile Menu Hamburger Button -->
        <div class="flex items-center gap-2 md:hidden">
            <button id="mobile-menu-btn" type="button" class="inline-flex items-center justify-center rounded-md p-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-controls="mobile-menu" aria-expanded="false">
                <span class="sr-only">Open main menu</span>
                <svg id="menu-open-icon" class="h-6 w-6 block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
                <svg id="menu-close-icon" class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div id="mobile-menu" class="hidden md:hidden border-b border-slate-200 bg-white px-4 pt-2 pb-4 shadow-lg space-y-1">
        <a href="{{ route('home') }}" class="block rounded-md px-3 py-2 text-base font-medium {{ request()->routeIs('home') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
            Home
        </a>
        <a href="{{ route('about') }}" class="block rounded-md px-3 py-2 text-base font-medium {{ request()->routeIs('about') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
            About
        </a>
        <a href="{{ route('courses') }}" class="block rounded-md px-3 py-2 text-base font-medium {{ request()->routeIs('courses*') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
            Courses
        </a>
        <a href="{{ route('contact') }}" class="block rounded-md px-3 py-2 text-base font-medium {{ request()->routeIs('contact') ? 'bg-indigo-50 text-indigo-600 font-semibold' : 'text-slate-700 hover:bg-slate-50' }}">
            Contact
        </a>

        <div class="pt-4 border-t border-slate-100 flex flex-col gap-2">
            <a href="#login" class="w-full text-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                Login
            </a>
            <a href="{{ route('courses') }}" class="w-full text-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                Get Started
            </a>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        const openIcon = document.getElementById('menu-open-icon');
        const closeIcon = document.getElementById('menu-close-icon');

        if (btn && menu) {
            btn.addEventListener('click', function () {
                const isHidden = menu.classList.contains('hidden');
                menu.classList.toggle('hidden', !isHidden);
                openIcon.classList.toggle('hidden', isHidden);
                closeIcon.classList.toggle('hidden', !isHidden);
                btn.setAttribute('aria-expanded', isHidden);
            });
        }
    });
</script>
