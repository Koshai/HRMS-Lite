<?php

namespace Tests\Feature\Salary;

use App\Enums\SalaryStatus;
use App\Models\AuditEvent;
use App\Models\Employee;
use App\Models\MonthlySalary;
use App\Models\User;
use App\Salary\SalaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MonthlySalaryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superUser()->create(['must_change_password' => false]);
    }

    private function employee(string $baseSalary = '45000.00'): Employee
    {
        return Employee::factory()->activeAccount()->create([
            'base_salary_amount' => $baseSalary,
        ]);
    }

    public function test_admin_creates_monthly_salary_with_snapshot_and_audit(): void
    {
        $admin = $this->admin();
        $employee = $this->employee('45000.50');

        $this->actingAs($admin)
            ->post(route('salaries.store'), [
                'employee_id' => $employee->id,
                'salary_month' => '2026-11',
                'reason' => 'November payroll tracking',
            ])
            ->assertRedirect();

        $record = MonthlySalary::query()->sole();
        $this->assertSame('2026-11-01', $record->salary_month->toDateString());
        $this->assertSame('45000.50', (string) $record->salary_amount);
        $this->assertSame('0.00', (string) $record->bonus_amount);
        $this->assertFalse($record->has_bonus);
        $this->assertSame(SalaryStatus::Unpaid, $record->given_status);
        $this->assertSame('45000.50', $record->totalAmount());

        $audit = AuditEvent::query()->sole();
        $this->assertSame('salary.create', $audit->action);
        $this->assertSame($admin->id, $audit->actor_user_id);
        $this->assertSame('November payroll tracking', $audit->reason);
    }

    public function test_admin_can_confirm_or_adjust_snapshot_amount_before_save(): void
    {
        $admin = $this->admin();
        $employee = $this->employee('45000.00');

        $this->actingAs($admin)
            ->post(route('salaries.store'), [
                'employee_id' => $employee->id,
                'salary_month' => '2026-10',
                'salary_amount' => '46000.25',
                'has_bonus' => true,
                'bonus_amount' => '1500.75',
                'reason' => 'Adjusted snapshot with festival bonus',
            ])
            ->assertRedirect();

        $record = MonthlySalary::query()->sole();
        $this->assertSame('46000.25', (string) $record->salary_amount);
        $this->assertSame('1500.75', (string) $record->bonus_amount);
        $this->assertSame('47501.00', $record->totalAmount());
    }

    public function test_historical_snapshot_preserved_after_profile_salary_change(): void
    {
        $admin = $this->admin();
        $employee = $this->employee('40000.00');
        $service = app(SalaryService::class);

        $record = $service->create($employee, $admin, [
            'salary_month' => '2026-09',
            'reason' => 'September',
        ]);

        $employee->forceFill(['base_salary_amount' => '55000.00'])->save();

        $record->refresh();
        $this->assertSame('40000.00', (string) $record->salary_amount);

        $newer = $service->create($employee->fresh(), $admin, [
            'salary_month' => '2026-10',
            'reason' => 'October after raise',
        ]);
        $this->assertSame('55000.00', (string) $newer->salary_amount);
    }

    public function test_bonus_must_be_zero_when_has_bonus_false(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('salaries.store'), [
                'employee_id' => $employee->id,
                'salary_month' => '2026-11',
                'has_bonus' => false,
                'bonus_amount' => '100.00',
                'reason' => 'Invalid bonus',
            ])
            ->assertSessionHasErrors('bonus_amount');

        $this->assertDatabaseCount('monthly_salaries', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_duplicate_employee_month_is_rejected(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();
        app(SalaryService::class)->create($employee, $admin, [
            'salary_month' => '2026-11',
            'reason' => 'First',
        ]);

        $this->actingAs($admin)
            ->post(route('salaries.store'), [
                'employee_id' => $employee->id,
                'salary_month' => '2026-11',
                'reason' => 'Duplicate',
            ])
            ->assertSessionHasErrors('salary_month');

        $this->assertDatabaseCount('monthly_salaries', 1);
    }

    public function test_employee_can_view_only_own_salaries_read_only(): void
    {
        $admin = $this->admin();
        $mine = $this->employee('30000.00');
        $other = $this->employee('35000.00');
        $service = app(SalaryService::class);

        $service->create($mine, $admin, ['salary_month' => '2026-11', 'reason' => 'Mine']);
        $service->create($other, $admin, ['salary_month' => '2026-11', 'reason' => 'Other']);

        $this->actingAs($mine->user)
            ->get(route('salaries.mine'))
            ->assertOk()
            ->assertSee('30000.00')
            ->assertDontSee('35000.00');

        $this->actingAs($mine->user)
            ->get(route('salaries.index'))
            ->assertForbidden();

        $otherRecord = MonthlySalary::query()->where('employee_id', $other->id)->sole();
        $this->actingAs($mine->user)
            ->get(route('salaries.show', $otherRecord))
            ->assertForbidden();

        $this->actingAs($mine->user)
            ->post(route('salaries.store'), [
                'employee_id' => $mine->id,
                'salary_month' => '2026-12',
                'reason' => 'No',
            ])
            ->assertForbidden();
    }

    public function test_inactive_employee_salary_history_is_retained_and_viewable_by_admin(): void
    {
        $admin = $this->admin();
        $employee = $this->employee('28000.00');
        $record = app(SalaryService::class)->create($employee, $admin, [
            'salary_month' => '2026-08',
            'reason' => 'Before leave',
        ]);

        $employee->forceFill(['is_active' => false])->save();
        $employee->user->forceFill(['is_active' => false])->save();

        $this->assertDatabaseHas('monthly_salaries', ['id' => $record->id, 'salary_amount' => '28000.00']);

        $this->actingAs($admin)
            ->get(route('salaries.show', $record))
            ->assertOk()
            ->assertSee('28000.00');
    }

    public function test_invalid_month_and_negative_amounts_are_rejected(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('salaries.store'), [
                'employee_id' => $employee->id,
                'salary_month' => '2026-13',
                'reason' => 'Bad month',
            ])
            ->assertSessionHasErrors('salary_month');

        $this->actingAs($admin)
            ->post(route('salaries.store'), [
                'employee_id' => $employee->id,
                'salary_month' => '2026-11',
                'salary_amount' => '-10.00',
                'reason' => 'Negative',
            ])
            ->assertSessionHasErrors('salary_amount');

        $this->assertDatabaseCount('monthly_salaries', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_failed_creation_does_not_leave_partial_data(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();
        $service = app(SalaryService::class);

        try {
            $service->create($employee, $admin, [
                'salary_month' => 'not-a-month',
                'reason' => 'Fail',
            ]);
            $this->fail('Expected validation exception');
        } catch (ValidationException) {
            // expected
        }

        $this->assertDatabaseCount('monthly_salaries', 0);
        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_admin_can_edit_unpaid_amounts_with_reason_and_version(): void
    {
        $admin = $this->admin();
        $employee = $this->employee('40000.00');
        $record = app(SalaryService::class)->create($employee, $admin, [
            'salary_month' => '2026-11',
            'reason' => 'Create',
        ]);

        $this->actingAs($admin)
            ->put(route('salaries.update', $record), [
                'salary_amount' => '41000.00',
                'has_bonus' => true,
                'bonus_amount' => '500.00',
                'reason' => 'Correction before payment',
                'expected_version' => 0,
            ])
            ->assertRedirect(route('salaries.show', $record));

        $record->refresh();
        $this->assertSame('41000.00', (string) $record->salary_amount);
        $this->assertSame('500.00', (string) $record->bonus_amount);
        $this->assertSame(1, $record->version);
        $this->assertSame(2, AuditEvent::query()->count());
    }

    public function test_stale_version_on_edit_is_rejected(): void
    {
        $admin = $this->admin();
        $employee = $this->employee();
        $service = app(SalaryService::class);
        $record = $service->create($employee, $admin, [
            'salary_month' => '2026-11',
            'reason' => 'Create',
        ]);

        $service->updateAmounts($record, $admin, [
            'salary_amount' => '41000.00',
            'reason' => 'First edit',
            'expected_version' => 0,
        ]);

        $this->expectException(ValidationException::class);
        $service->updateAmounts($record->fresh(), $admin, [
            'salary_amount' => '42000.00',
            'reason' => 'Stale',
            'expected_version' => 0,
        ]);
    }
}
