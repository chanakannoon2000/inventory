<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_username(): void
    {
        User::factory()->create([
            'username' => 'admin',
            'password' => 'admin',
            'role' => 'owner',
        ]);

        $response = $this->post('/login', [
            'login' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_user_can_still_login_with_email(): void
    {
        $user = User::factory()->create([
            'username' => 'admin',
            'password' => 'admin',
            'role' => 'owner',
        ]);

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'admin',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
