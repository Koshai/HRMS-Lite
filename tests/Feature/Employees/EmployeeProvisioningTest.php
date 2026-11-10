<?php

namespace Tests\Feature\Employees;

use App\Employees\EmployeePhotoService;
use App\Employees\EmployeeService;
use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TestImage;
use Tests\TestCase;

class EmployeeProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private function superUser(): User
    {
        return User::factory()->superUser()->create([
            'must_change_password' => false,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Amina Rahman',
            'email' => 'amina@example.com',
            'password' => 'TempPass1!abcd',
            'phone' => '01700000000',
            'address' => 'Dhaka',
            'blood_group' => 'B+',
            'designation' => 'Accountant',
            'base_salary_amount' => '45000.50',
        ], $overrides);
    }

    public function test_super_user_can_provision_employee_atomically(): void
    {
        $admin = $this->superUser();

        $response = $this->actingAs($admin)->post(route('employees.store'), $this->validPayload());

        $response->assertRedirect();
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('employees', 1);

        $user = User::query()->where('email', 'amina@example.com')->firstOrFail();
        $employee = Employee::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame(Role::Employee, $user->role);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('TempPass1!abcd', $user->password));
        $this->assertFalse(str_contains(json_encode($user->toArray()), 'TempPass1!abcd'));
        $this->assertSame('45000.50', $employee->base_salary_amount);
        $this->assertSame('Accountant', $employee->designation);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $admin = $this->superUser();
        User::factory()->create(['email' => 'amina@example.com']);

        $this->actingAs($admin)
            ->post(route('employees.store'), $this->validPayload())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_creation_rolls_back_when_photo_storage_fails_inside_transaction(): void
    {
        Storage::fake('local');
        $beforeUsers = User::query()->count();

        $this->mock(EmployeePhotoService::class, function ($mock) {
            $mock->shouldReceive('store')
                ->once()
                ->andThrow(new \RuntimeException('disk full'));
        });

        $service = app(EmployeeService::class);

        try {
            $service->create($this->validPayload([
                'email' => 'rollback@example.com',
                'image' => TestImage::png(),
            ]));
            $this->fail('Expected provisioning failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('disk full', $exception->getMessage());
        }

        $this->assertSame($beforeUsers, User::query()->count());
        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.com']);
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_employee_cannot_access_admin_employee_routes(): void
    {
        $employee = Employee::factory()->activeAccount()->create();
        $other = Employee::factory()->activeAccount()->create();

        $this->actingAs($employee->user)
            ->get(route('employees.index'))
            ->assertForbidden();

        $this->actingAs($employee->user)
            ->post(route('employees.store'), $this->validPayload([
                'email' => 'newhire@example.com',
            ]))
            ->assertForbidden();

        $this->actingAs($employee->user)
            ->put(route('employees.update', $other), $this->validPayload([
                'email' => 'other@example.com',
                'password' => null,
            ]))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'newhire@example.com']);
    }

    public function test_employee_cannot_view_another_employee_profile_or_sensitive_fields(): void
    {
        $viewer = Employee::factory()->activeAccount()->create();
        $target = Employee::factory()->activeAccount()->create([
            'blood_group' => 'O+',
            'base_salary_amount' => '99999.00',
        ]);

        $this->actingAs($viewer->user)
            ->get(route('employees.show', $target))
            ->assertForbidden();

        $this->actingAs($viewer->user)
            ->get(route('employees.photo', $target))
            ->assertForbidden();
    }

    public function test_employee_can_view_own_profile_with_sensitive_fields(): void
    {
        $employee = Employee::factory()->activeAccount()->create([
            'blood_group' => 'A+',
            'base_salary_amount' => '32000.00',
        ]);

        $this->actingAs($employee->user)
            ->get(route('profile.mine'))
            ->assertOk()
            ->assertSee('A+', false)
            ->assertSee('32,000.00', false);
    }

    public function test_malicious_and_oversized_photos_are_rejected(): void
    {
        Storage::fake('local');
        $admin = $this->superUser();

        $this->actingAs($admin)
            ->post(route('employees.store'), $this->validPayload([
                'email' => 'photo1@example.com',
                'image' => TestImage::spoofedJpeg(),
            ]))
            ->assertSessionHasErrors('image');

        $this->actingAs($admin)
            ->post(route('employees.store'), $this->validPayload([
                'email' => 'photo2@example.com',
                'image' => TestImage::oversizedJpegStub(),
            ]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('employees', 0);
    }

    public function test_authorized_photo_is_served_privately(): void
    {
        Storage::fake('local');
        $admin = $this->superUser();
        $image = TestImage::png();

        $this->actingAs($admin)
            ->post(route('employees.store'), $this->validPayload([
                'email' => 'photo3@example.com',
                'image' => $image,
            ]))
            ->assertRedirect();

        $employee = Employee::query()->firstOrFail();
        $this->assertNotNull($employee->image_path);
        Storage::disk('local')->assertExists($employee->image_path);

        $this->actingAs($admin)
            ->get(route('employees.photo', $employee))
            ->assertOk();

        $stranger = Employee::factory()->activeAccount()->create();
        $this->actingAs($stranger->user)
            ->get(route('employees.photo', $employee))
            ->assertForbidden();
    }
}
