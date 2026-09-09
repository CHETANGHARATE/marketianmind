<?php

namespace App\Http\Requests\Admin;

use App\Enums\CategoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:course_categories,name'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:course_categories,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::enum(CategoryStatus::class)],
        ];
    }
}
