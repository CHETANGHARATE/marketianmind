@extends('layouts.admin')

@section('subcontent')
<div class="space-y-6">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-400 flex-wrap">
        <a href="{{ route('admin.courses.index') }}" class="hover:text-white transition">Courses</a>
        <span>/</span>
        <a href="{{ route('admin.courses.modules.index', $course) }}" class="hover:text-white transition truncate max-w-xs">{{ $course->title }}</a>
        <span>/</span>
        <a href="{{ route('admin.courses.modules.lessons.index', [$course, $module]) }}" class="hover:text-white transition truncate max-w-xs">{{ $module->title }}</a>
        <span>/</span>
        <a href="{{ route('admin.courses.modules.lessons.edit', [$course, $module, $lesson]) }}" class="hover:text-white transition truncate max-w-xs">{{ $lesson->title }}</a>
        <span>/</span>
        <span class="text-amber-400 font-semibold">Resources &amp; Downloads</span>
    </div>

    <!-- Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-400 border border-amber-500/20">
                    {{ ucfirst($lesson->lesson_type->value) }} Lesson
                </span>
                <span class="text-xs text-slate-400">in {{ $module->title }}</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl mt-1">
                {{ $lesson->title }}: Resources
            </h1>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl">
                Attach downloadable worksheets, cheat sheets, templates, and external links for enrolled students.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.courses.modules.lessons.index', [$course, $module]) }}"
               class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                &larr; Back to Lessons
            </a>
            <a href="{{ route('admin.courses.modules.lessons.edit', [$course, $module, $lesson]) }}"
               class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-xs font-semibold text-white hover:bg-slate-700 transition">
                Edit Lesson
            </a>
        </div>
    </div>

    <!-- Main Grid: Resources List (Left) & Add Form (Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Existing Resources Table (7 cols) -->
        <div class="lg:col-span-7 space-y-4">
            <div class="rounded-xl border border-slate-800 bg-slate-900/60 overflow-hidden shadow-sm">
                <div class="p-4 border-b border-slate-800 bg-slate-950/60 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                            Attached Resources ({{ $resources->count() }})
                        </h2>
                    </div>
                </div>

                @if($resources->count() > 0)
                    <div class="divide-y divide-slate-800">
                        @foreach($resources as $resource)
                            <div class="p-4 hover:bg-slate-800/40 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $resource->isFile() ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' }}">
                                        @if($resource->isFile())
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="text-sm font-bold text-white truncate">
                                                {{ $resource->title }}
                                            </h3>
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase {{ $resource->isFile() ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' }}">
                                                {{ $resource->type }}
                                            </span>
                                            @if($resource->sort_order > 0)
                                                <span class="text-[11px] font-mono text-slate-500">
                                                    #{{ $resource->sort_order }}
                                                </span>
                                            @endif
                                        </div>

                                        @if($resource->description)
                                            <p class="text-xs text-slate-400 mt-1 line-clamp-2">
                                                {{ $resource->description }}
                                            </p>
                                        @endif

                                        <div class="flex items-center gap-3 mt-1.5 text-xs text-slate-500">
                                            @if($resource->isFile())
                                                <span class="truncate max-w-xs font-mono text-[11px] text-slate-400">{{ $resource->file_name ?? basename($resource->file_path) }}</span>
                                                @if($resource->formattedSize())
                                                    <span>&bull;</span>
                                                    <span>{{ $resource->formattedSize() }}</span>
                                                @endif
                                            @else
                                                <span class="truncate max-w-xs font-mono text-[11px] text-emerald-400">{{ $resource->external_url }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                    @if($resource->isFile() && $resource->file_path)
                                        <a href="{{ asset('storage/' . $resource->file_path) }}" target="_blank" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition" title="Preview / Download File">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                    @elseif($resource->isLink() && $resource->external_url)
                                        <a href="{{ $resource->external_url }}" target="_blank" rel="noopener noreferrer" class="p-2 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition" title="Open Link">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('admin.courses.modules.lessons.resources.destroy', [$course, $module, $lesson, $resource]) }}" onsubmit="return confirm('Are you sure you want to delete this resource?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-rose-500/10 transition" title="Delete Resource">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-slate-400">
                        <svg class="h-10 w-10 mx-auto text-slate-600 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <p class="text-sm font-semibold text-slate-300">No resources attached yet</p>
                        <p class="text-xs text-slate-500 mt-1">Use the form on the right to attach files or external links.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Add Resource Form (5 cols) -->
        <div class="lg:col-span-5">
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
                <div class="border-b border-slate-800 pb-3">
                    <h2 class="text-base font-bold text-white">
                        Add New Resource
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Upload a file or provide an external link for students.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.courses.modules.lessons.resources.store', [$course, $module, $lesson]) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <!-- Resource Title -->
                    <div>
                        <label for="res_title" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Resource Title <span class="text-amber-400">*</span>
                        </label>
                        <input type="text"
                               name="title"
                               id="res_title"
                               value="{{ old('title') }}"
                               placeholder="e.g. Action Checklist or Ad Copy Template"
                               required
                               class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                        @error('title')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Resource Type Selector -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                            Resource Type <span class="text-amber-400">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2.5 p-3 rounded-lg border border-slate-700 bg-slate-950/60 cursor-pointer hover:border-slate-600 transition">
                                <input type="radio"
                                       name="type"
                                       value="file"
                                       {{ old('type', 'file') === 'file' ? 'checked' : '' }}
                                       onchange="toggleResourceType('file')"
                                       class="h-4 w-4 text-amber-500 focus:ring-amber-400 border-slate-700 bg-slate-900">
                                <span class="text-xs font-bold text-white">Downloadable File</span>
                            </label>
                            <label class="flex items-center gap-2.5 p-3 rounded-lg border border-slate-700 bg-slate-950/60 cursor-pointer hover:border-slate-600 transition">
                                <input type="radio"
                                       name="type"
                                       value="link"
                                       {{ old('type') === 'link' ? 'checked' : '' }}
                                       onchange="toggleResourceType('link')"
                                       class="h-4 w-4 text-amber-500 focus:ring-amber-400 border-slate-700 bg-slate-900">
                                <span class="text-xs font-bold text-white">External Link</span>
                            </label>
                        </div>
                    </div>

                    <!-- File Upload Input (Conditional) -->
                    <div id="file_field_container" class="{{ old('type', 'file') === 'link' ? 'hidden' : '' }}">
                        <label for="res_file" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Select File <span class="text-amber-400">*</span>
                        </label>
                        <input type="file"
                               name="file"
                               id="res_file"
                               class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-400 cursor-pointer">
                        <p class="mt-1.5 text-[11px] text-slate-500">
                            Formats: PDF, DOCX, XLSX, CSV, PPTX, ZIP, TXT, PNG, JPG (Max: 25 MB).
                        </p>
                        @error('file')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- External URL Input (Conditional) -->
                    <div id="link_field_container" class="{{ old('type', 'file') === 'file' ? 'hidden' : '' }}">
                        <label for="res_link" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            External URL <span class="text-amber-400">*</span>
                        </label>
                        <input type="url"
                               name="external_url"
                               id="res_link"
                               value="{{ old('external_url') }}"
                               placeholder="https://example.com/template or Notion/Google Docs"
                               class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 font-mono">
                        <p class="mt-1.5 text-[11px] text-slate-500">
                            Must begin with https:// or http://.
                        </p>
                        @error('external_url')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description / Instructions -->
                    <div>
                        <label for="res_description" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Description / Instructions <span class="text-slate-500 lowercase font-normal">(optional)</span>
                        </label>
                        <textarea name="description"
                                  id="res_description"
                                  rows="2"
                                  placeholder="Provide instructions on how to use this resource..."
                                  class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label for="res_sort_order" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Sort Order
                        </label>
                        <input type="number"
                               name="sort_order"
                               id="res_sort_order"
                               value="{{ old('sort_order', $nextSortOrder) }}"
                               min="0"
                               class="mt-1.5 w-32 rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Resource
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleResourceType(type) {
    const fileContainer = document.getElementById('file_field_container');
    const linkContainer = document.getElementById('link_field_container');
    if (type === 'file') {
        fileContainer.classList.remove('hidden');
        linkContainer.classList.add('hidden');
    } else {
        fileContainer.classList.add('hidden');
        linkContainer.classList.remove('hidden');
    }
}
</script>
@endsection