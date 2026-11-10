<?php

namespace Tests\Feature\Employees;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDeactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_employee_cannot_login_and_existing_session_is_rejected(): void
    {
        $admin = User::factory()->superUser()->create(['must_change_password' => false]);
        $employee = Employee::factory()->activeAccount()->create();

        $this->actingAs($employee->user)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('employees.deactivate', $employee))
            ->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertFalse($employee->is_active);
        $this->assertFalse($employee->user->fresh()->is_active);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);

        $this->actingAs($employee->user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => $employee->user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_super_user_can_reactivate_employee(): void
    {
        $admin = User::factory()->superUser()->create(['must_change_password' => false]);
        $employee = Employee::factory()->inactive()->create();

        $this->actingAs($admin)
            ->post(route('employees.reactivate', $employee))
            ->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertTrue($employee->is_active);
        $this->assertTrue($employee->user->fresh()->is_active);
    }
}
