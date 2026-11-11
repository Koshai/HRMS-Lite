<?php

namespace App\Salary;

use App\Enums\SalaryStatus;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\MonthlySalary;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryService
{
    public function nowUtc(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }

    public function normalizeMonth(string $monthInput): string
    {
        $monthInput = trim($monthInput);

        if (preg_match('/^\d{4}-\d{2}$/', $monthInput) === 1) {
            $monthInput .= '-01';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $monthInput) !== 1) {
            throw ValidationException::withMessages([
                'salary_month' => __('Enter a valid salary month (YYYY-MM).'),
            ]);
        }

        $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $monthInput);

        if ($parsed === false || $parsed->format('Y-m-d') !== $monthInput) {
            throw ValidationException::withMessages([
                'salary_month' => __('Enter a valid salary month (YYYY-MM).'),
            ]);
        }

        return $parsed->startOfMonth()->toDateString();
    }

    public function normalizeMoney(mixed $amount, string $field): string
    {
        if ($amount === null || $amount === '') {
            throw ValidationException::withMessages([
                $field => __('Enter a valid amount.'),
            ]);
        }

        if (is_int($amount)) {
            $raw = (string) $amount;
        } elseif (is_string($amount)) {
            $raw = trim($amount);
        } else {
            throw ValidationException::withMessages([
                $field => __('Enter a valid amount as a decimal string.'),
            ]);
        }

        if (preg_match('/^\d+(\.\d{1,2})?$/', $raw) !== 1) {
            throw ValidationException::withMessages([
                $field => __('Enter a non-negative amount with at most two decimal places.'),
            ]);
        }

        $normalized = bcadd($raw, '0', 2);

        if (bccomp($normalized, '0', 2) === -1) {
            throw ValidationException::withMessages([
                $field => __('Amount cannot be negative.'),
            ]);
        }

        if (strlen(explode('.', $normalized)[0]) > 10) {
            throw ValidationException::withMessages([
                $field => __('Amount is too large.'),
            ]);
        }

        return $normalized;
    }

    public function total(string $salaryAmount, string $bonusAmount): string
    {
        return bcadd($salaryAmount, $bonusAmount, 2);
    }

    /**
     * @param  array{
     *     salary_month: string,
     *     salary_amount?: mixed,
     *     has_bonus?: bool,
     *     bonus_amount?: mixed,
     *     reason: string
     * }  $data
     */
    public function create(Employee $employee, User $actor, array $data): MonthlySalary
    {
        $this->assertEligibleActor($actor);
        $reason = $this->validatedReason($data['reason'] ?? null);
        $salaryMonth = $this->normalizeMonth($data['salary_month']);

        $salaryAmount = array_key_exists('salary_amount', $data) && $data['salary_amount'] !== null && $data['salary_amount'] !== ''
            ? $this->normalizeMoney($data['salary_amount'], 'salary_amount')
            : $this->normalizeMoney((string) $employee->base_salary_amount, 'salary_amount');

        [$hasBonus, $bonusAmount] = $this->resolveBonus(
            (bool) ($data['has_bonus'] ?? false),
            $data['bonus_amount'] ?? '0'
        );

        try {
            return DB::transaction(function () use ($employee, $actor, $salaryMonth, $salaryAmount, $hasBonus, $bonusAmount, $reason) {
                $this->lockEmployee($employee);

                if (MonthlySalary::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('salary_month', $salaryMonth)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'salary_month' => __('A salary record already exists for this employee and month.'),
                    ]);
                }

                $record = MonthlySalary::query()->create([
                    'employee_id' => $employee->id,
                    'salary_month' => $salaryMonth,
                    'salary_amount' => $salaryAmount,
                    'given_status' => SalaryStatus::Unpaid,
                    'has_bonus' => $hasBonus,
                    'bonus_amount' => $bonusAmount,
                    'paid_at' => null,
                    'paid_by' => null,
                    'version' => 0,
                ]);

                $this->audit($actor, $record, 'salary.create', null, $this->snapshot($record), $reason);

                return $record->fresh(['employee.user']);
            });
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                throw ValidationException::withMessages([
                    'salary_month' => __('A salary record already exists for this employee and month.'),
                ]);
            }

            throw $exception;
        }
    }

    /**
     * @param  array{
     *     salary_amount: mixed,
     *     has_bonus?: bool,
     *     bonus_amount?: mixed,
     *     reason: string,
     *     expected_version: int|string
     * }  $data
     */
    public function updateAmounts(MonthlySalary $salary, User $actor, array $data): MonthlySalary
    {
        $this->assertEligibleActor($actor);
        $reason = $this->validatedReason($data['reason'] ?? null);
        $salaryAmount = $this->normalizeMoney($data['salary_amount'], 'salary_amount');
        [$hasBonus, $bonusAmount] = $this->resolveBonus(
            (bool) ($data['has_bonus'] ?? false),
            $data['bonus_amount'] ?? '0'
        );
        $expectedVersion = (int) ($data['expected_version'] ?? -1);

        return DB::transaction(function () use ($salary, $actor, $salaryAmount, $hasBonus, $bonusAmount, $reason, $expectedVersion) {
            $locked = MonthlySalary::query()->whereKey($salary->id)->lockForUpdate()->firstOrFail();

            $this->assertExpectedVersion($locked, $expectedVersion);

            if ($locked->isPaid()) {
                throw ValidationException::withMessages([
                    'salary' => __('Paid salary amounts cannot be edited silently. Reverse to unpaid first, or use an audited correction workflow.'),
                ]);
            }

            $before = $this->snapshot($locked);

            $locked->forceFill([
                'salary_amount' => $salaryAmount,
                'has_bonus' => $hasBonus,
                'bonus_amount' => $bonusAmount,
                'version' => $locked->version + 1,
            ])->save();

            $after = $this->snapshot($locked->fresh());
            $this->audit($actor, $locked, 'salary.update', $before, $after, $reason);

            return $locked->fresh(['employee.user']);
        });
    }

    public function markPaid(MonthlySalary $salary, User $actor, int $expectedVersion): MonthlySalary
    {
        $this->assertEligibleActor($actor);

        return DB::transaction(function () use ($salary, $actor, $expectedVersion) {
            $locked = MonthlySalary::query()->whereKey($salary->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                // Idempotent: do not rewrite paid_at/paid_by or append duplicate audit.
                return $locked->fresh(['employee.user', 'payer']);
            }

            $this->assertExpectedVersion($locked, $expectedVersion);

            $before = $this->snapshot($locked);
            $now = $this->nowUtc();

            $locked->forceFill([
                'given_status' => SalaryStatus::Paid,
                'paid_at' => $now,
                'paid_by' => $actor->id,
                'version' => $locked->version + 1,
            ])->save();

            $after = $this->snapshot($locked->fresh());
            $this->audit($actor, $locked, 'salary.mark_paid', $before, $after, 'Marked paid');

            return $locked->fresh(['employee.user', 'payer']);
        });
    }

    /**
     * @param  array{reason: string, expected_version: int|string}  $data
     */
    public function reverseToUnpaid(MonthlySalary $salary, User $actor, array $data): MonthlySalary
    {
        $this->assertEligibleActor($actor);
        $reason = $this->validatedReason($data['reason'] ?? null);
        $expectedVersion = (int) ($data['expected_version'] ?? -1);

        return DB::transaction(function () use ($salary, $actor, $reason, $expectedVersion) {
            $locked = MonthlySalary::query()->whereKey($salary->id)->lockForUpdate()->firstOrFail();
            $this->assertExpectedVersion($locked, $expectedVersion);

            if ($locked->isUnpaid()) {
                throw ValidationException::withMessages([
                    'salary' => __('This salary record is already unpaid.'),
                ]);
            }

            $before = $this->snapshot($locked);

            $locked->forceFill([
                'given_status' => SalaryStatus::Unpaid,
                'paid_at' => null,
                'paid_by' => null,
                'version' => $locked->version + 1,
            ])->save();

            $after = $this->snapshot($locked->fresh());
            $this->audit($actor, $locked, 'salary.reverse_unpaid', $before, $after, $reason);

            return $locked->fresh(['employee.user', 'payer']);
        });
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function resolveBonus(bool $hasBonus, mixed $bonusAmount): array
    {
        if (! $hasBonus) {
            if ($bonusAmount !== null && $bonusAmount !== '' && $bonusAmount !== '0' && $bonusAmount !== '0.00') {
                if (is_string($bonusAmount) || is_int($bonusAmount)) {
                    $candidate = $this->normalizeMoney($bonusAmount, 'bonus_amount');
                    if (bccomp($candidate, '0', 2) === 1) {
                        throw ValidationException::withMessages([
                            'bonus_amount' => __('Bonus amount must be zero when has bonus is false.'),
                        ]);
                    }
                }
            }

            return [false, '0.00'];
        }

        return [true, $this->normalizeMoney($bonusAmount ?? '0', 'bonus_amount')];
    }

    private function assertExpectedVersion(MonthlySalary $salary, int $expectedVersion): void
    {
        if ($expectedVersion < 0 || (int) $salary->version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'salary' => __('This salary record was changed by someone else. Reload and try again.'),
            ]);
        }
    }

    private function assertEligibleActor(User $actor): void
    {
        if (! $actor->is_active) {
            throw ValidationException::withMessages([
                'salary' => __('Inactive accounts cannot manage salaries.'),
            ]);
        }

        if ($actor->mustChangePassword()) {
            throw ValidationException::withMessages([
                'salary' => __('Change your temporary password before managing salaries.'),
            ]);
        }

        if (! $actor->isSuperUser()) {
            throw ValidationException::withMessages([
                'salary' => __('Only super users can manage salaries.'),
            ]);
        }
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
    public function snapshot(MonthlySalary $salary): array
    {
        return [
            'employee_id' => $salary->employee_id,
            'salary_month' => $salary->salary_month?->toDateString(),
            'salary_amount' => (string) $salary->salary_amount,
            'bonus_amount' => (string) $salary->bonus_amount,
            'has_bonus' => (bool) $salary->has_bonus,
            'given_status' => $salary->given_status?->value,
            'paid_at' => optional($salary->paid_at)->toIso8601String(),
            'paid_by' => $salary->paid_by,
            'version' => (int) $salary->version,
            'total_amount' => $salary->totalAmount(),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function audit(User $actor, MonthlySalary $salary, string $action, ?array $before, ?array $after, string $reason): void
    {
        AuditEvent::query()->create([
            'actor_user_id' => $actor->id,
            'entity_type' => MonthlySalary::class,
            'entity_id' => $salary->id,
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
