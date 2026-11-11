<?php

namespace Tests\Feature;

use App\Enums\SalaryStatus;
use App\Models\Employee;
use App\Models\MonthlySalary;
use App\Models\User;
use App\Salary\SalaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_user_dashboard_shows_counts_without_treating_missing_as_unpaid(): void
    {
        $admin = User::factory()->superUser()->create(['must_change_password' => false]);
        $withSalary = Employee::factory()->activeAccount()->create(['base_salary_amount' => '40000.00']);
        Employee::factory()->activeAccount()->create(['base_salary_amount' => '30000.00']);

        app(SalaryService::class)->create($withSalary, $admin, [
            'salary_month' => now(config('hrms.display_timezone'))->format('Y-m'),
            'reason' => 'Dashboard seed',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Active employees')
            ->assertSee('Missing salary rows')
            ->assertSee('Unpaid this month')
            ->assertSee('Add employee')
            ->assertSee('admin confirmation only');
    }

    public function test_employee_dashboard_shows_self_service_shortcuts(): void
    {
        $employee = Employee::factory()->activeAccount()->create([
            'designation' => 'Cashier',
            'base_salary_amount' => '25000.00',
        ]);

        MonthlySalary::factory()->create([
            'employee_id' => $employee->id,
            'salary_month' => now()->startOfMonth()->toDateString(),
            'salary_amount' => '25000.00',
            'given_status' => SalaryStatus::Unpaid,
        ]);

        $this->actingAs($employee->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cashier')
            ->assertSee('My Profile')
            ->assertSee('My Attendance')
            ->assertSee('My Salaries')
            ->assertSee('Unpaid');
    }
}
