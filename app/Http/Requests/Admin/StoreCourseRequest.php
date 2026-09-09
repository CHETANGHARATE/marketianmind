<?php

namespace App\Http\Requests\Admin;

use App\Enums\CourseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
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

        $this->merge([
            'is_free' => $isFree,
            'featured' => $this->boolean('featured'),
            'price' => $isFree ? 0.00 : $this->input('price'),
            'discount_price' => $isFree ? null : ($this->filled('discount_price') ? $this->input('discount_price') : null),
        ]);
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
            'slug' => ['nullable', 'string', 'max:255', 'unique:courses,slug'],
            'short_description' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'course_category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'is_free' => ['boolean'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'required_unless:is_free,true'],
            'discount_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'status' => ['required', Rule::enum(CourseStatus::class)],
            'featured' => ['boolean'],
            'estimated_duration' => ['nullable', 'string', 'max:100'],
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
            'thumbnail.max' => 'The thumbnail may not be greater than 3MB in size.',
        ];
    }
}
