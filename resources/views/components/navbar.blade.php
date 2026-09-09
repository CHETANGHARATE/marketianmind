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
            @guest
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-700 hover:text-indigo-600 transition">
                    Login
                </a>
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    Create Account
                </a>
            @else
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700 hover:text-amber-800 bg-amber-50 px-3.5 py-2 rounded-lg border border-amber-200 transition">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Admin Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-600 hover:text-rose-600 transition cursor-pointer">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('student.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-700 hover:text-indigo-800 bg-indigo-50 px-3.5 py-2 rounded-lg border border-indigo-200 transition">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        My Learning / Dashboard
                    </a>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-700 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ auth()->user()->name }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-600 hover:text-rose-600 transition cursor-pointer">
                            Logout
                        </button>
                    </form>
                @endif
            @endguest
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
            @guest
                <a href="{{ route('login') }}" class="w-full text-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Login
                </a>
                <a href="{{ route('register') }}" class="w-full text-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    Create Account
                </a>
            @else
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="w-full text-center rounded-md bg-amber-50 border border-amber-200 px-4 py-2 text-sm font-semibold text-amber-800 transition">
                        Admin Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-center rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('student.dashboard') }}" class="w-full text-center rounded-md bg-indigo-50 border border-indigo-200 px-4 py-2 text-sm font-semibold text-indigo-700 transition">
                        My Learning / Dashboard
                    </a>
                    <div class="text-center text-xs text-slate-500 py-1">
                        Signed in as <span class="font-semibold text-slate-700">{{ auth()->user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-center rounded-md border border-slate-200 px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                            Logout
                        </button>
                    </form>
                @endif
            @endguest
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
