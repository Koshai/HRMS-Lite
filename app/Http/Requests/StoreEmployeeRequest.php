<?php

namespace App\Http\Requests;

use App\Employees\EmployeePhotoService;
use App\Employees\EmployeeService;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Employee::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
            'blood_group' => ['nullable', 'string', Rule::in(EmployeeService::bloodGroupValues())],
            'designation' => ['required', 'string', 'max:255'],
            'base_salary_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'image' => ['nullable', 'file', 'max:2048', 'mimetypes:'.implode(',', EmployeePhotoService::ALLOWED_MIME_TYPES)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }
}
