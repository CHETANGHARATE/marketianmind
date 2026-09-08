<header class="sticky top-0 z-40 w-full border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold text-xl text-slate-900 tracking-tight">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white font-black text-lg shadow-sm">
                    M
                </span>
                <span>Marketian<span class="text-indigo-600">Mind</span></span>
            </a>

            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-slate-600">
                <a href="{{ route('home') }}" class="transition hover:text-indigo-600 {{ request()->routeIs('home') ? 'text-indigo-600 font-semibold' : '' }}">
                    Home
                </a>
                <a href="#courses" class="transition hover:text-indigo-600">
                    Courses
                </a>
                <a href="#features" class="transition hover:text-indigo-600">
                    Features
                </a>
                <a href="#about" class="transition hover:text-indigo-600">
                    About
                </a>
            </nav>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('student.dashboard') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium text-slate-700 hover:text-indigo-600 hover:bg-slate-100 transition border border-slate-200">
                Student Portal
            </a>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-xs font-medium text-slate-700 hover:text-indigo-600 hover:bg-slate-100 transition border border-slate-200">
                Admin Portal
            </a>
            <a href="#login" class="hidden sm:inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                Get Started
            </a>
        </div>
    </div>
</header>
