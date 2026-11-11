<?php

namespace App\Http\Requests;

use App\Models\MonthlySalary;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMonthlySalaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MonthlySalary $salary */
        $salary = $this->route('salary');

        return $this->user()?->can('update', $salary) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'salary_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'has_bonus' => ['sometimes', 'boolean'],
            'bonus_amount' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'max:2000'],
            'expected_version' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_bonus' => $this->boolean('has_bonus'),
        ]);
    }
}
