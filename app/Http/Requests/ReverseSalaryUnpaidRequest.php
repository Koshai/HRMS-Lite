<?php

namespace App\Http\Requests;

use App\Models\MonthlySalary;
use Illuminate\Foundation\Http\FormRequest;

class ReverseSalaryUnpaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MonthlySalary $salary */
        $salary = $this->route('salary');

        return $this->user()?->can('reverseUnpaid', $salary) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            'expected_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
