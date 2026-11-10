<?php

namespace App\Http\Requests;

use App\Employees\EmployeePhotoService;
use App\Employees\EmployeeService;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return $this->user()?->can('update', $employee) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employee->user_id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
            'blood_group' => ['nullable', 'string', Rule::in(EmployeeService::bloodGroupValues())],
            'designation' => ['required', 'string', 'max:255'],
            'base_salary_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'image' => ['nullable', 'file', 'max:2048', 'mimetypes:'.implode(',', EmployeePhotoService::ALLOWED_MIME_TYPES)],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email'))),
            ]);
        }

        if ($this->has('remove_image')) {
            $this->merge([
                'remove_image' => $this->boolean('remove_image'),
            ]);
        }
    }
}
