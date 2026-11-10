<?php

namespace App\Employees;

use App\Enums\BloodGroup;
use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(private readonly EmployeePhotoService $photos) {}

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     phone?: string|null,
     *     address?: string|null,
     *     blood_group?: string|null,
     *     designation: string,
     *     base_salary_amount: numeric-string|float|int,
     *     image?: UploadedFile|null
     * }  $data
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => Str::lower(trim($data['email'])),
                'password' => $data['password'],
                'role' => Role::Employee,
                'must_change_password' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $employee = Employee::query()->create([
                'user_id' => $user->id,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'designation' => $data['designation'],
                'base_salary_amount' => $data['base_salary_amount'],
                'is_active' => true,
            ]);

            if (($data['image'] ?? null) instanceof UploadedFile) {
                $this->photos->store($employee, $data['image']);
            }

            return $employee->fresh(['user']);
        });
    }

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     phone?: string|null,
     *     address?: string|null,
     *     blood_group?: string|null,
     *     designation: string,
     *     base_salary_amount: numeric-string|float|int,
     *     image?: UploadedFile|null,
     *     remove_image?: bool
     * }  $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data) {
            $employee->user->forceFill([
                'name' => $data['name'],
                'email' => Str::lower(trim($data['email'])),
            ])->save();

            $employee->forceFill([
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'designation' => $data['designation'],
                'base_salary_amount' => $data['base_salary_amount'],
            ])->save();

            if (! empty($data['remove_image'])) {
                $this->photos->delete($employee);
            }

            if (($data['image'] ?? null) instanceof UploadedFile) {
                $this->photos->store($employee, $data['image']);
            }

            return $employee->fresh(['user']);
        });
    }

    public function deactivate(Employee $employee): Employee
    {
        return DB::transaction(function () use ($employee) {
            $employee->user->forceFill(['is_active' => false])->save();
            $employee->forceFill(['is_active' => false])->save();

            return $employee->fresh(['user']);
        });
    }

    public function reactivate(Employee $employee): Employee
    {
        return DB::transaction(function () use ($employee) {
            $employee->user->forceFill(['is_active' => true])->save();
            $employee->forceFill(['is_active' => true])->save();

            return $employee->fresh(['user']);
        });
    }

    public function resetPassword(Employee $employee, string $temporaryPassword): void
    {
        if ($employee->user->isSuperUser()) {
            throw ValidationException::withMessages([
                'password' => __('Cannot reset a super user password through employee tooling.'),
            ]);
        }

        $employee->user->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ])->save();
    }

    /**
     * @return list<string>
     */
    public static function bloodGroupValues(): array
    {
        return array_column(BloodGroup::cases(), 'value');
    }
}
