@extends('layouts.admin')

@section('subcontent')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="{{ route('admin.courses.index') }}" class="hover:text-white transition">Courses</a>
        <span>/</span>
        <a href="{{ route('admin.courses.modules.index', $course) }}" class="hover:text-white transition truncate max-w-xs">{{ $course->title }}</a>
        <span>/</span>
        <a href="{{ route('admin.courses.modules.lessons.index', [$course, $module]) }}" class="hover:text-white transition truncate max-w-xs">{{ $module->title }}</a>
        <span>/</span>
        <span class="text-amber-400 font-semibold">Edit Lesson</span>
    </div>

    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
            Edit Lesson: {{ $lesson->title }}
        </h1>
        <p class="mt-1 text-sm text-slate-400">
            Updating lesson inside <span class="text-slate-200 font-semibold">"{{ $module->title }}"</span>.
        </p>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.courses.modules.lessons.update', [$course, $module, $lesson]) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- 1. Lesson Core Info -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                1. Lesson Overview
            </h2>

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Lesson Title <span class="text-amber-400">*</span>
                </label>
                <input type="text"
                       name="title"
                       id="title"
                       value="{{ old('title', $lesson->title) }}"
                       required
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('title')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Custom Slug -->
            <div>
                <label for="slug" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Slug <span class="text-slate-500 lowercase font-normal">(must be unique)</span>
                </label>
                <div class="mt-1.5 flex rounded-lg border border-slate-700 bg-slate-950 overflow-hidden focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500">
                    <span class="inline-flex items-center px-3 text-xs text-slate-500 bg-slate-900 border-r border-slate-800 select-none">
                        /lessons/
                    </span>
                    <input type="text"
                           name="slug"
                           id="slug"
                           value="{{ old('slug', $lesson->slug) }}"
                           class="w-full bg-transparent px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-hidden font-mono">
                </div>
                @error('slug')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Brief Summary <span class="text-slate-500 lowercase font-normal">(optional)</span>
                </label>
                <textarea name="description"
                          id="description"
                          rows="2"
                          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('description', $lesson->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- 2. Lesson Type & Dynamic Content -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                2. Lesson Type & Content
            </h2>

            <!-- Type Radio Selection -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-2">
                    Select Lesson Format <span class="text-amber-400">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Video -->
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-700 bg-slate-950 cursor-pointer hover:border-amber-500 transition">
                        <input type="radio"
                               name="lesson_type"
                               value="video"
                               {{ old('lesson_type', $lesson->lesson_type->value) === 'video' ? 'checked' : '' }}
                               class="lesson-type-radio h-4 w-4 text-amber-500 bg-slate-900 border-slate-700 focus:ring-amber-500">
                        <div>
                            <span class="block text-sm font-bold text-white">Video Lesson</span>
                            <span class="block text-[11px] text-slate-400">Embed YouTube/Vimeo</span>
                        </div>
                    </label>

                    <!-- Text -->
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-700 bg-slate-950 cursor-pointer hover:border-amber-500 transition">
                        <input type="radio"
                               name="lesson_type"
                               value="text"
                               {{ old('lesson_type', $lesson->lesson_type->value) === 'text' ? 'checked' : '' }}
                               class="lesson-type-radio h-4 w-4 text-amber-500 bg-slate-900 border-slate-700 focus:ring-amber-500">
                        <div>
                            <span class="block text-sm font-bold text-white">Text / Article</span>
                            <span class="block text-[11px] text-slate-400">Written notes & guides</span>
                        </div>
                    </label>

                    <!-- PDF -->
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-700 bg-slate-950 cursor-pointer hover:border-amber-500 transition">
                        <input type="radio"
                               name="lesson_type"
                               value="pdf"
                               {{ old('lesson_type', $lesson->lesson_type->value) === 'pdf' ? 'checked' : '' }}
                               class="lesson-type-radio h-4 w-4 text-amber-500 bg-slate-900 border-slate-700 focus:ring-amber-500">
                        <div>
                            <span class="block text-sm font-bold text-white">PDF Resource</span>
                            <span class="block text-[11px] text-slate-400">Downloadable checklist/PDF</span>
                        </div>
                    </label>
                </div>
                @error('lesson_type')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Conditional Section: Video URL -->
            <div id="section_video" class="space-y-2 pt-2">
                <label for="video_url" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Video URL <span class="text-amber-400">*</span>
                </label>
                <input type="url"
                       name="video_url"
                       id="video_url"
                       value="{{ old('video_url', $lesson->video_url) }}"
                       placeholder="https://www.youtube.com/watch?v=... or https://vimeo.com/..."
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                @error('video_url')
                    <p class="text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Conditional Section: Text Content -->
            <div id="section_text" class="space-y-2 pt-2 hidden">
                <label for="content" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Lesson Content / Article Text <span class="text-amber-400">*</span>
                </label>
                <textarea name="content"
                          id="content"
                          rows="8"
                          class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500 font-sans leading-relaxed">{{ old('content', $lesson->content) }}</textarea>
                @error('content')
                    <p class="text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Conditional Section: PDF Upload -->
            <div id="section_pdf" class="space-y-2 pt-2 hidden">
                <label for="pdf_file" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    PDF Document
                </label>

                @if($lesson->pdf_url)
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-950 border border-slate-800 mb-2">
                        <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-white truncate">Current File</p>
                            <a href="{{ $lesson->pdfUrl() }}" target="_blank" class="text-[11px] text-amber-400 hover:underline truncate block">
                                {{ $lesson->pdf_url }}
                            </a>
                        </div>
                    </div>
                @endif

                <input type="file"
                       name="pdf_file"
                       id="pdf_file"
                       accept="application/pdf"
                       class="w-full text-xs text-slate-400 file:mr-3 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700 file:cursor-pointer cursor-pointer border border-slate-700 bg-slate-950 rounded-lg p-2">
                <p class="text-[11px] text-slate-500">
                    {{ $lesson->pdf_url ? 'Upload a new PDF to replace the current document.' : 'Select a PDF document (up to 10MB).' }}
                </p>
                @error('pdf_file')
                    <p class="text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- 3. Settings & Meta -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-base font-bold text-white border-b border-slate-800 pb-3">
                3. Lesson Settings & Sequence
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Duration -->
                <div>
                    <label for="duration" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Estimated Duration
                    </label>
                    <input type="text"
                           name="duration"
                           id="duration"
                           value="{{ old('duration', $lesson->duration) }}"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    @error('duration')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Sort Order
                    </label>
                    <input type="number"
                       name="sort_order"
                       id="sort_order"
                       value="{{ old('sort_order', $lesson->sort_order) }}"
                       min="0"
                       class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                    @error('sort_order')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Status <span class="text-amber-400">*</span>
                    </label>
                    <select name="status"
                            id="status"
                            required
                            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                        <option value="draft" {{ old('status', $lesson->status->value) === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $lesson->status->value) === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Preview Lesson Toggle -->
            <div class="pt-2">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox"
                           name="is_preview"
                           value="1"
                           {{ old('is_preview', $lesson->is_preview) ? 'checked' : '' }}
                           class="h-4 w-4 rounded-sm border-slate-700 bg-slate-950 text-amber-500 focus:ring-amber-500">
                    <div>
                        <span class="text-sm font-semibold text-white">Free Preview Lesson</span>
                        <p class="text-xs text-slate-400">Allow unregistered visitors or non-enrolled students to preview this lesson for free.</p>
                    </div>
                </label>
                @error('is_preview')
                    <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Submit & Actions -->
        <div class="flex items-center justify-between pt-2">
            <button type="button"
                    onclick="if(confirm('Are you sure you want to delete this lesson?')) { document.getElementById('delete-lesson-form').submit(); }"
                    class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-2.5 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 transition">
                Delete Lesson
            </button>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.courses.modules.lessons.index', [$course, $module]) }}"
                   class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                    Update Lesson
                </button>
            </div>
        </div>
    </form>

    <form id="delete-lesson-form" method="POST" action="{{ route('admin.courses.modules.lessons.destroy', [$course, $module, $lesson]) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('.lesson-type-radio');
        const sectionVideo = document.getElementById('section_video');
        const sectionText = document.getElementById('section_text');
        const sectionPdf = document.getElementById('section_pdf');

        function updateTypeSections() {
            let selectedType = 'video';
            radios.forEach(function (r) {
                if (r.checked) selectedType = r.value;
            });

            if (sectionVideo) sectionVideo.classList.toggle('hidden', selectedType !== 'video');
            if (sectionText) sectionText.classList.toggle('hidden', selectedType !== 'text');
            if (sectionPdf) sectionPdf.classList.toggle('hidden', selectedType !== 'pdf');
        }

        radios.forEach(function (r) {
            r.addEventListener('change', updateTypeSections);
        });

        updateTypeSections();
    });
</script>
@endsection
