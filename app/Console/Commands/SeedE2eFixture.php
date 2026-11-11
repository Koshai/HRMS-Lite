<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\AttendanceRecord;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\MonthlySalary;
use App\Models\User;
use Illuminate\Console\Command;

class SeedE2eFixture extends Command
{
    protected $signature = 'hrms-lite:e2e-fixture';

    protected $description = 'Seed disposable synthetic accounts for Playwright (never production)';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('E2E fixtures are only allowed in local/testing.');

            return self::FAILURE;
        }

        $password = (string) env('E2E_PASSWORD', '');
        if ($password === '' || strlen($password) < 12) {
            $this->error('E2E_PASSWORD must be provided (12+ characters).');

            return self::FAILURE;
        }

        MonthlySalary::query()->delete();
        AttendanceRecord::query()->delete();
        AuditEvent::query()->delete();
        Employee::query()->delete();
        User::query()->delete();

        User::query()->create([
            'name' => 'E2E Super User',
            'email' => 'super@hrms-lite-e2e.example.invalid',
            'password' => $password,
            'role' => Role::SuperUser,
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $otherUser = User::query()->create([
            'name' => 'E2E Other Employee',
            'email' => 'other@hrms-lite-e2e.example.invalid',
            'password' => $password,
            'role' => Role::Employee,
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Employee::query()->create([
            'user_id' => $otherUser->id,
            'phone' => '01700000099',
            'address' => 'Synthetic address',
            'designation' => 'Analyst',
            'base_salary_amount' => '32000.00',
            'is_active' => true,
        ]);

        $this->info('E2E fixtures ready (synthetic addresses only).');

        return self::SUCCESS;
    }
}
