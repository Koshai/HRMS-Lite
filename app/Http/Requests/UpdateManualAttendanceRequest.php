<?php

namespace App\Http\Requests;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;

class UpdateManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AttendanceRecord $attendance */
        $attendance = $this->route('attendance');

        return $this->user()?->can('update', $attendance) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'checkout_next_day' => ['sometimes', 'boolean'],
            'reason' => ['required', 'string', 'max:2000'],
            'expected_check_in_at' => ['required', 'string'],
            'expected_check_out_at' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'checkout_next_day' => $this->boolean('checkout_next_day'),
        ]);
    }
}
