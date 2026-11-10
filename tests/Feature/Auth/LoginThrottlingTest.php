<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->superUser()->create([
            'email' => 'throttle@example.com',
            'must_change_password' => false,
        ]);

        RateLimiter::clear(strtolower($user->email).'|127.0.0.1');

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password-'.$attempt,
            ])->assertStatus(302);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password-final',
        ])->assertStatus(429);
    }
}
