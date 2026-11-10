<?php

namespace Tests\Feature\Employees;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_with_temporary_password_is_redirected_to_forced_change(): void
    {
        $employee = Employee::factory()->create();
        $employee->user->forceFill([
            'password' => 'TempPass1!abcd',
            'must_change_password' => true,
            'is_active' => true,
        ])->save();

        $this->post('/login', [
            'email' => $employee->user->email,
            'password' => 'TempPass1!abcd',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('password.forced.edit'));

        $this->get(route('profile.mine'))
            ->assertRedirect(route('password.forced.edit'));
    }

    public function test_forced_password_change_requires_current_password_and_clears_flag(): void
    {
        $employee = Employee::factory()->create();
        $employee->user->forceFill([
            'password' => 'TempPass1!abcd',
            'must_change_password' => true,
            'is_active' => true,
        ])->save();

        $this->actingAs($employee->user)
            ->post(route('password.forced.update'), [
                'current_password' => 'TempPass1!abcd',
                'password' => 'NewSecure1!xyz',
                'password_confirmation' => 'NewSecure1!xyz',
            ])
            ->assertRedirect(route('dashboard'));

        $employee->user->refresh();
        $this->assertFalse($employee->user->must_change_password);
        $this->assertTrue(Hash::check('NewSecure1!xyz', $employee->user->password));

        $this->actingAs($employee->user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_logout_remains_available_during_forced_password_change(): void
    {
        $employee = Employee::factory()->mustChangePassword()->create();

        $this->actingAs($employee->user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_admin_password_reset_forces_change_without_revealing_old_password(): void
    {
        $admin = User::factory()->superUser()->create(['must_change_password' => false]);
        $employee = Employee::factory()->activeAccount()->create();
        $oldHash = $employee->user->password;

        $response = $this->actingAs($admin)
            ->post(route('employees.reset-password', $employee), [
                'password' => 'ResetPass1!abcd',
            ]);

        $response->assertRedirect(route('employees.show', $employee));
        $response->assertSessionMissing('password');
        $this->assertStringNotContainsString('ResetPass1!abcd', $response->getContent());

        $employee->user->refresh();
        $this->assertNotSame($oldHash, $employee->user->password);
        $this->assertTrue($employee->user->must_change_password);
        $this->assertTrue(Hash::check('ResetPass1!abcd', $employee->user->password));
    }
}
