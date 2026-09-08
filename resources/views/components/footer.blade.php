<footer class="mt-auto border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <!-- Brand & Mission Column -->
            <div class="md:col-span-2">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold text-xl text-slate-900 tracking-tight mb-4">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-sm shadow-sm">
                        M
                    </span>
                    <span>Marketian<span class="text-indigo-600">Mind</span></span>
                </a>
                <p class="text-sm text-slate-600 max-w-md leading-relaxed mb-4">
                    Marketing Knowledge for Business Owners. Learn how to grow your business online without depending on expensive marketing agencies.
                </p>
                <p class="text-xs text-slate-400">
                    Built for small business owners, startup founders, entrepreneurs, and local businesses.
                </p>
            </div>

            <!-- Platform Navigation -->
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900 mb-4">Navigation</h3>
                <ul class="space-y-2.5 text-sm text-slate-600">
                    <li>
                        <a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Home</a>
                    </li>
                    <li>
                        <a href="{{ route('about') }}" class="hover:text-indigo-600 transition">About Marketian Mind</a>
                    </li>
                    <li>
                        <a href="{{ route('courses') }}" class="hover:text-indigo-600 transition">Courses</a>
                    </li>
                    <li>
                        <a href="{{ route('course.details') }}" class="hover:text-indigo-600 transition">Featured Course</a>
                    </li>
                    <li>
                        <a href="{{ route('contact') }}" class="hover:text-indigo-600 transition">Contact Us</a>
                    </li>
                </ul>
            </div>

            <!-- Learning & Portals -->
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-900 mb-4">Platform Previews</h3>
                <ul class="space-y-2.5 text-sm text-slate-600">
                    <li>
                        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center gap-1.5 hover:text-indigo-600 transition">
                            <span>Student Portal</span>
                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">Preview</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 hover:text-indigo-600 transition">
                            <span>Admin Portal</span>
                            <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">Preview</span>
                        </a>
                    </li>
                    <li class="pt-2 text-xs text-slate-400">
                        Need support? <a href="{{ route('contact') }}" class="text-indigo-600 hover:underline">Get in touch</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-slate-500">
                &copy; {{ date('Y') }} Marketian Mind. All rights reserved. Practical Marketing Education.
            </p>
            <div class="flex items-center gap-6 text-xs text-slate-400">
                <span>No complex theory. Just real-world business growth.</span>
            </div>
        </div>
    </div>
</footer>
