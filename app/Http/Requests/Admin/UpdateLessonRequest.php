<?php

namespace App\Http\Requests\Admin;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_preview' => $this->boolean('is_preview'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $lesson = $this->route('lesson');
        $lessonId = is_object($lesson) ? $lesson->id : $lesson;
        $hasExistingPdf = is_object($lesson) ? (bool) $lesson->pdf_url : false;

        $pdfRules = ['nullable', 'file', 'mimes:pdf', 'max:10240'];
        if (! $hasExistingPdf) {
            $pdfRules[] = 'required_if:lesson_type,pdf';
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('lessons', 'slug')->ignore($lessonId)],
            'lesson_type' => ['required', Rule::enum(LessonType::class)],
            'video_url' => ['nullable', 'url', 'max:255', 'required_if:lesson_type,video'],
            'content' => ['nullable', 'string', 'required_if:lesson_type,text'],
            'pdf_file' => $pdfRules,
            'description' => ['nullable', 'string', 'max:1000'],
            'duration' => ['nullable', 'string', 'max:100'],
            'is_preview' => ['boolean'],
            'status' => ['required', Rule::enum(LessonStatus::class)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'video_url.required_if' => 'A video URL is required for video lessons.',
            'content.required_if' => 'Lesson content is required for text lessons.',
            'pdf_file.required_if' => 'A PDF document must be uploaded for PDF lessons.',
            'pdf_file.mimes' => 'The uploaded file must be a valid PDF document.',
            'pdf_file.max' => 'The PDF document may not exceed 10MB.',
        ];
    }
}
