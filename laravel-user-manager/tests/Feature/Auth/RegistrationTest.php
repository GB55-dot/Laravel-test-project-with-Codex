<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'Test@Example.com',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $this->assertTrue(Hash::check('Strong-password-123', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('status', 'Акаунт успішно створено. Вітаємо!');
    }

    public function test_user_can_log_in_after_registration(): void
    {
        $this->post('/register', [
            'name' => 'Login User',
            'email' => 'login@example.com',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
        ]);

        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'Strong-password-123',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_registration_rejects_an_existing_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->from('/register')
            ->post('/register', [
                'name' => 'Another User',
                'email' => 'taken@example.com',
                'password' => 'Strong-password-123',
                'password_confirmation' => 'Strong-password-123',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors('email');
    }

    public function test_registration_is_rate_limited_by_ip_address(): void
    {
        Cache::flush();

        for ($number = 1; $number <= 5; $number++) {
            $this->post('/register', [
                'name' => "Rate Limited User {$number}",
                'email' => "rate-{$number}@example.com",
                'password' => 'Strong-password-123',
                'password_confirmation' => 'Strong-password-123',
            ])->assertRedirect(route('dashboard', absolute: false));

            $this->post('/logout');
        }

        $this->post('/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'Strong-password-123',
            'password_confirmation' => 'Strong-password-123',
        ])->assertTooManyRequests();
    }
}
