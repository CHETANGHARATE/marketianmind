<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
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
        $isFree = $this->boolean('is_free');

        $rawValidity = $this->input('access_validity_days');
        $validity = ($rawValidity === null || $rawValidity === '') ? 365 : $rawValidity;

        $this->merge([
            'is_free' => $isFree,
            'featured' => $this->boolean('featured'),
            'price' => $isFree ? 0.00 : $this->input('price'),
            'discount_price' => $isFree ? null : ($this->filled('discount_price') ? $this->input('discount_price') : null),
            'access_validity_days' => $validity,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $courseId = $this->route('course')?->id ?? $this->route('course');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('courses', 'slug')->ignore($courseId)],
            'short_description' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'course_category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'is_free' => ['boolean'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'required_unless:is_free,true'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'status' => ['required', Rule::enum(CourseStatus::class)],
            'featured' => ['boolean'],
            'estimated_duration' => ['nullable', 'string', 'max:100'],
            'access_validity_days' => ['required', 'integer', 'min:1'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:3072'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price.required_unless' => 'A price is required for paid courses.',
            'discount_price.lte' => 'Discount price cannot be higher than the regular course price.',
            'access_validity_days.required' => 'Course access validity in days is required.',
            'access_validity_days.integer' => 'Course access validity must be a whole number of days.',
            'access_validity_days.min' => 'Course access validity must be at least 1 day.',
            'thumbnail.max' => 'The thumbnail may not be greater than 3MB in size.',
        ];
    }
}
