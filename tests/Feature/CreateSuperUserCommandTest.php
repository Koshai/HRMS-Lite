<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateSuperUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_super_user_without_logging_the_password(): void
    {
        $this->artisan('hrms-lite:create-super-user', [
            'name' => 'Bootstrap Admin',
            'email' => 'ADMIN@Example.COM',
        ])
            ->expectsQuestion('Password', 'SecurePass1!xyz')
            ->expectsQuestion('Confirm password', 'SecurePass1!xyz')
            ->assertSuccessful();

        $user = User::query()->sole();

        $this->assertSame('admin@example.com', $user->email);
        $this->assertSame(Role::SuperUser, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('SecurePass1!xyz', $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
    }

    public function test_it_refuses_when_a_super_user_already_exists(): void
    {
        User::factory()->superUser()->create();

        $this->artisan('hrms-lite:create-super-user', [
            'name' => 'Another Admin',
            'email' => 'another@example.com',
        ])->assertFailed();

        $this->assertDatabaseCount('users', 1);
    }
}
