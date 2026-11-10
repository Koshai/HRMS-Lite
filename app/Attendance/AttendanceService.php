<?php

namespace App\Attendance;

use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function timezone(): string
    {
        return (string) config('hrms.display_timezone', 'Asia/Dhaka');
    }

    public function maxHours(): int
    {
        return max(1, (int) config('hrms.attendance_max_hours', 16));
    }

    public function nowUtc(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }

    public function localDateFor(CarbonImmutable $utcInstant): string
    {
        return $utcInstant->setTimezone($this->timezone())->toDateString();
    }

    public function formatLocal(?CarbonImmutable $utcInstant): ?string
    {
        return $utcInstant?->setTimezone($this->timezone())->format('Y-m-d H:i');
    }

    public function assertEligibleActor(User $actor): void
    {
        if (! $actor->is_active) {
            throw ValidationException::withMessages([
                'attendance' => __('Inactive accounts cannot submit attendance.'),
            ]);
        }

        if ($actor->mustChangePassword()) {
            throw ValidationException::withMessages([
                'attendance' => __('Change your temporary password before submitting attendance.'),
            ]);
        }
    }

    public function assertActiveEmployee(Employee $employee): void
    {
        if (! $employee->is_active || ! $employee->user?->is_active) {
            throw ValidationException::withMessages([
                'attendance' => __('Inactive employees cannot submit attendance.'),
            ]);
        }
    }

    public function getOpenAttendance(Employee $employee): ?AttendanceRecord
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereNull('check_out_at')
            ->orderByDesc('check_in_at')
            ->first();
    }

    public function getCurrentAttendance(Employee $employee): ?AttendanceRecord
    {
        $today = $this->localDateFor($this->nowUtc());

        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->first()
            ?? $this->getOpenAttendance($employee);
    }

    /**
     * @return LengthAwarePaginator<int, AttendanceRecord>|Collection<int, AttendanceRecord>
     */
    public function getAttendanceHistory(Employee $employee, int $perPage = 30): LengthAwarePaginator
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('work_date')
            ->orderByDesc('check_in_at')
            ->paginate($perPage);
    }

    public function checkIn(Employee $employee, User $actor): AttendanceRecord
    {
        $this->assertEligibleActor($actor);
        $this->assertActiveEmployee($employee);

        if ($actor->id !== $employee->user_id) {
            throw ValidationException::withMessages([
                'attendance' => __('Self check-in is only available for your own employee profile.'),
            ]);
        }

        try {
            return DB::transaction(function () use ($employee, $actor) {
                $this->lockEmployee($employee);

                if ($open = $this->getOpenAttendance($employee)) {
                    throw ValidationException::withMessages([
                        'attendance' => __('You already have an open attendance from :date. Check out before checking in again.', [
                            'date' => $open->work_date->toDateString(),
                        ]),
                    ]);
                }

                $now = $this->nowUtc();
                $workDate = $this->localDateFor($now);

                if (AttendanceRecord::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('work_date', $workDate)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'attendance' => __('Attendance for today already exists. You cannot check in again.'),
                    ]);
                }

                return AttendanceRecord::query()->create([
                    'employee_id' => $employee->id,
                    'work_date' => $workDate,
                    'check_in_at' => $now,
                    'check_out_at' => null,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'attendance' => __('Attendance for today already exists. You cannot check in again.'),
                ]);
            }

            throw $exception;
        }
    }

    public function checkOut(Employee $employee, User $actor): AttendanceRecord
    {
        $this->assertEligibleActor($actor);
        $this->assertActiveEmployee($employee);

        if ($actor->id !== $employee->user_id) {
            throw ValidationException::withMessages([
                'attendance' => __('Self check-out is only available for your own employee profile.'),
            ]);
        }

        return DB::transaction(function () use ($employee, $actor) {
            $this->lockEmployee($employee);

            $open = AttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereNull('check_out_at')
                ->orderByDesc('check_in_at')
                ->lockForUpdate()
                ->first();

            if ($open === null) {
                throw ValidationException::withMessages([
                    'attendance' => __('You have no open attendance to check out from.'),
                ]);
            }

            $now = $this->nowUtc();
            $this->assertValidWindow($open->check_in_at, $now);

            $open->forceFill([
                'check_out_at' => $now,
                'updated_by' => $actor->id,
            ])->save();

            return $open->fresh();
        });
    }

    public function assertValidWindow(CarbonImmutable $checkInAt, CarbonImmutable $checkOutAt): void
    {
        if ($checkOutAt->lessThanOrEqualTo($checkInAt)) {
            throw ValidationException::withMessages([
                'attendance' => __('Check-out must be after check-in.'),
            ]);
        }

        $maxHours = $this->maxHours();
        if ($checkOutAt->greaterThan($checkInAt->addHours($maxHours))) {
            throw ValidationException::withMessages([
                'attendance' => __('Attendance cannot exceed :hours hours.', ['hours' => $maxHours]),
            ]);
        }
    }

    private function lockEmployee(Employee $employee): Employee
    {
        return Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = strtolower($exception->getMessage());

        return $sqlState === '23000'
            || $driverCode === 1062
            || str_contains($message, 'unique')
            || str_contains($message, 'duplicate');
    }
}
