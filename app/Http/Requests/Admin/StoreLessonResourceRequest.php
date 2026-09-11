<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonResourceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:file,link'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'file' => [
                'required_if:type,file',
                'nullable',
                'file',
                'max:25600', // 25 MB
                'mimes:pdf,doc,docx,xls,xlsx,csv,ppt,pptx,zip,txt,png,jpg,jpeg,webp',
            ],
            'external_url' => [
                'required_if:type,link',
                'nullable',
                'url:http,https',
                'max:2048',
            ],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required_if' => 'Please select a file to upload when resource type is File.',
            'file.max' => 'The uploaded file may not be greater than 25 MB.',
            'file.mimes' => 'Allowed file formats: PDF, DOC, DOCX, XLS, XLSX, CSV, PPT, PPTX, ZIP, TXT, PNG, JPG, JPEG, WEBP.',
            'external_url.required_if' => 'Please provide a valid URL when resource type is External Link.',
            'external_url.url' => 'The external URL must begin with http:// or https://.',
        ];
    }
}