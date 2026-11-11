<?php

namespace App\Http\Requests;

use App\Models\MonthlySalary;
use Illuminate\Foundation\Http\FormRequest;

class MarkSalaryPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var MonthlySalary $salary */
        $salary = $this->route('salary');

        return $this->user()?->can('markPaid', $salary) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'expected_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
