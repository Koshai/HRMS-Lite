<?php

namespace App\Http\Requests;

use App\Models\MonthlySalary;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonthlySalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MonthlySalary::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'salary_month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'salary_amount' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'has_bonus' => ['sometimes', 'boolean'],
            'bonus_amount' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_bonus' => $this->boolean('has_bonus'),
        ]);
    }
}
