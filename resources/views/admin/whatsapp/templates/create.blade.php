@extends('layouts.admin')

@section('subcontent')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-slate-800 pb-6">
        <div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.whatsapp.templates.index') }}" class="text-xs text-slate-500 hover:text-slate-400">
                    &larr; Templates
                </a>
                <span class="text-xs text-slate-600">/</span>
                <span class="text-xs font-semibold text-emerald-400">New Template</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">
                Register WhatsApp Message Template
            </h1>
            <p class="mt-1 text-sm text-slate-400">
                Register a pre-approved Meta WhatsApp Business template configuration.
            </p>
        </div>
    </div>

    <!-- Errors -->
    @if($errors->any())
        <div class="rounded-xl bg-rose-500/10 border border-rose-500/30 p-4 text-xs font-medium text-rose-400">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <form action="{{ route('admin.whatsapp.templates.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-6 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                        Internal Display Name <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none"
                           placeholder="e.g. Welcome & Course Enrollment Receipt">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                        Meta Template Name <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="template_name" value="{{ old('template_name') }}" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-sm font-mono text-emerald-400 focus:border-emerald-500 focus:outline-none"
                           placeholder="e.g. course_enrollment_confirmation">
                    <p class="mt-1 text-[11px] text-slate-500">Must exactly match the template name registered in Meta WhatsApp Business Manager (lowercase, underscores).</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                        Category <span class="text-rose-400">*</span>
                    </label>
                    <select name="category" required class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->value }}" {{ old('category') === $cat->value ? 'selected' : '' }}>
                                {{ ucfirst($cat->value) }} — {{ $cat->description() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                        Language Code <span class="text-rose-400">*</span>
                    </label>
                    <input type="text" name="language" value="{{ old('language', 'en') }}" required
                           class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-sm font-mono text-white focus:border-emerald-500 focus:outline-none"
                           placeholder="e.g. en or en_US">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                    Header (Optional)
                </label>
                <input type="text" name="header" value="{{ old('header') }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none"
                       placeholder="e.g. Marketian Mind Confirmation">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                    Message Body <span class="text-rose-400">*</span>
                </label>
                <textarea name="body" rows="5" required
                          class="w-full rounded-xl border border-slate-700 bg-slate-800 p-3.5 text-sm font-mono text-white focus:border-emerald-500 focus:outline-none"
                          placeholder="Hello {{ user.name }}, thank you for enrolling in {{ course.title }}. Access your lectures here: {{ course.url }}">{{ old('body') }}</textarea>
                <p class="mt-1 text-[11px] text-slate-500">Supported variables: <code>@{{ user.name }}</code>, <code>@{{ lead.name }}</code>, <code>@{{ course.title }}</code>, <code>@{{ course.url }}</code>, <code>@{{ bundle.title }}</code>, <code>@{{ bundle.url }}</code>, <code>@{{ opt_out_url }}</code></p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                    Variables List (Comma-separated)
                </label>
                <input type="text" name="variables" value="{{ old('variables') }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-sm font-mono text-white focus:border-emerald-500 focus:outline-none"
                       placeholder="e.g. user.name, course.title, course.url">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">
                    Footer Text (Optional)
                </label>
                <input type="text" name="footer" value="{{ old('footer') }}"
                       class="w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-sm text-white focus:border-emerald-500 focus:outline-none"
                       placeholder="e.g. Reply STOP to unsubscribe">
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-300">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                           class="rounded border-slate-700 bg-slate-800 text-emerald-600 focus:ring-emerald-500">
                    Template is active and available for dispatches
                </label>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.whatsapp.templates.index') }}"
               class="rounded-xl border border-slate-700 px-5 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 transition">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-xl bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-500 transition">
                Save Template
            </button>
        </div>
    </form>
</div>
@endsection
