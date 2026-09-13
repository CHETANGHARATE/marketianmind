<?php

namespace App\Http\Requests\Admin;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:100'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'bundle_id' => ['nullable', 'integer', 'exists:bundles,id'],
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'priority' => ['required', Rule::enum(LeadPriority::class)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'next_follow_up_at' => ['nullable', 'date'],
            'follow_up_status' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'initial_note' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
