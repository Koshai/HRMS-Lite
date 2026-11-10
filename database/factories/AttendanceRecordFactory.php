<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = CarbonImmutable::now('UTC')->subHours(2);

        return [
            'employee_id' => Employee::factory()->activeAccount(),
            'work_date' => $checkIn->setTimezone(config('hrms.display_timezone'))->toDateString(),
            'check_in_at' => $checkIn,
            'check_out_at' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'check_out_at' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $checkIn = CarbonImmutable::parse($attributes['check_in_at'] ?? now('UTC'));

            return [
                'check_out_at' => $checkIn->addHours(8),
            ];
        });
    }
}
