<?php

namespace App\Attendance;

use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
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

    public function assertValidWindow(CarbonImmutable $checkInAt, ?CarbonImmutable $checkOutAt): void
    {
        if ($checkOutAt === null) {
            return;
        }

        if ($checkOutAt->lessThanOrEqualTo($checkInAt)) {
            throw ValidationException::withMessages([
                'check_out_time' => __('Check-out must be after check-in.'),
            ]);
        }

        $maxHours = $this->maxHours();
        if ($checkOutAt->greaterThan($checkInAt->addHours($maxHours))) {
            throw ValidationException::withMessages([
                'check_out_time' => __('Attendance cannot exceed :hours hours.', ['hours' => $maxHours]),
            ]);
        }
    }

    /**
     * @param  array{
     *     work_date: string,
     *     check_in_time: string,
     *     check_out_time?: string|null,
     *     checkout_next_day?: bool,
     *     reason: string
     * }  $data
     */
    public function createManual(Employee $employee, User $actor, array $data): AttendanceRecord
    {
        $this->assertEligibleActor($actor);
        $this->assertActiveEmployee($employee);
        $reason = $this->validatedReason($data['reason'] ?? null);

        [$checkInAt, $checkOutAt] = $this->resolveLocalTimes(
            $data['work_date'],
            $data['check_in_time'],
            $data['check_out_time'] ?? null,
            (bool) ($data['checkout_next_day'] ?? false),
        );

        $this->assertValidWindow($checkInAt, $checkOutAt);

        try {
            return DB::transaction(function () use ($employee, $actor, $data, $checkInAt, $checkOutAt, $reason) {
                $this->lockEmployee($employee);

                if (AttendanceRecord::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('work_date', $data['work_date'])
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'work_date' => __('An attendance record already exists for this employee and date.'),
                    ]);
                }

                $record = AttendanceRecord::query()->create([
                    'employee_id' => $employee->id,
                    'work_date' => $data['work_date'],
                    'check_in_at' => $checkInAt,
                    'check_out_at' => $checkOutAt,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $this->audit($actor, $record, 'attendance.manual_create', null, $this->snapshot($record), $reason);

                return $record->fresh();
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'work_date' => __('An attendance record already exists for this employee and date.'),
                ]);
            }

            throw $exception;
        }
    }

    /**
     * @param  array{
     *     check_in_time: string,
     *     check_out_time?: string|null,
     *     checkout_next_day?: bool,
     *     reason: string,
     *     expected_check_in_at?: string|null,
     *     expected_check_out_at?: string|null
     * }  $data
     */
    public function correctManual(AttendanceRecord $record, User $actor, array $data): AttendanceRecord
    {
        $this->assertEligibleActor($actor);
        $reason = $this->validatedReason($data['reason'] ?? null);

        $workDate = $record->work_date->toDateString();
        [$checkInAt, $checkOutAt] = $this->resolveLocalTimes(
            $workDate,
            $data['check_in_time'],
            $data['check_out_time'] ?? null,
            (bool) ($data['checkout_next_day'] ?? false),
        );
        $this->assertValidWindow($checkInAt, $checkOutAt);

        return DB::transaction(function () use ($record, $actor, $data, $checkInAt, $checkOutAt, $reason) {
            $locked = AttendanceRecord::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            if (array_key_exists('expected_check_in_at', $data)) {
                $expectedIn = $data['expected_check_in_at'];
                $expectedOut = $data['expected_check_out_at'] ?? null;
                $actualIn = optional($locked->check_in_at)->toIso8601String();
                $actualOut = optional($locked->check_out_at)->toIso8601String();

                if ($expectedIn !== $actualIn || $expectedOut !== $actualOut) {
                    throw ValidationException::withMessages([
                        'attendance' => __('This attendance record was changed by someone else. Reload and try again.'),
                    ]);
                }
            }

            $before = $this->snapshot($locked);

            $locked->forceFill([
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'updated_by' => $actor->id,
            ])->save();

            $after = $this->snapshot($locked->fresh());
            $this->audit($actor, $locked, 'attendance.manual_correct', $before, $after, $reason);

            return $locked->fresh();
        });
    }

    /**
     * @return array{0: CarbonImmutable, 1: ?CarbonImmutable}
     */
    public function resolveLocalTimes(string $workDate, string $checkInTime, ?string $checkOutTime, bool $checkoutNextDay): array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate)) {
            throw ValidationException::withMessages([
                'work_date' => __('Enter a valid attendance date.'),
            ]);
        }

        $checkInAt = $this->localWallToUtc($workDate, $checkInTime, 'check_in_time');
        $checkOutAt = null;

        if (filled($checkOutTime)) {
            $checkOutDate = $checkoutNextDay
                ? CarbonImmutable::parse($workDate, $this->timezone())->addDay()->toDateString()
                : $workDate;
            $checkOutAt = $this->localWallToUtc($checkOutDate, $checkOutTime, 'check_out_time');
        } elseif ($checkoutNextDay) {
            throw ValidationException::withMessages([
                'check_out_time' => __('Provide a check-out time when marking next-day check-out.'),
            ]);
        }

        return [$checkInAt, $checkOutAt];
    }

    private function localWallToUtc(string $date, string $time, string $field): CarbonImmutable
    {
        if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
            throw ValidationException::withMessages([
                $field => __('Enter a valid time as HH:MM.'),
            ]);
        }

        $local = CarbonImmutable::createFromFormat(
            '!Y-m-d H:i',
            $date.' '.$time,
            $this->timezone()
        );

        if ($local === false || $local->format('Y-m-d H:i') !== $date.' '.$time) {
            throw ValidationException::withMessages([
                $field => __('Enter a valid local date and time.'),
            ]);
        }

        return $local->utc();
    }

    private function validatedReason(?string $reason): string
    {
        $reason = trim((string) $reason);

        if ($reason === '' || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages([
                'reason' => __('A reason of at most 2000 characters is required.'),
            ]);
        }

        return $reason;
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(AttendanceRecord $record): array
    {
        return [
            'employee_id' => $record->employee_id,
            'work_date' => $record->work_date?->toDateString(),
            'check_in_at' => optional($record->check_in_at)->toIso8601String(),
            'check_out_at' => optional($record->check_out_at)->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function audit(User $actor, AttendanceRecord $record, string $action, ?array $before, ?array $after, string $reason): void
    {
        AuditEvent::query()->create([
            'actor_user_id' => $actor->id,
            'entity_type' => AttendanceRecord::class,
            'entity_id' => $record->id,
            'action' => $action,
            'before_json' => $before,
            'after_json' => $after,
            'reason' => $reason,
            'created_at' => $this->nowUtc(),
        ]);
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
