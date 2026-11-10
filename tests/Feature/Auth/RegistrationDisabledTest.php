<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_routes_are_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'password' => 'Password1!abcd',
            'password_confirmation' => 'Password1!abcd',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }
}
