<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_a_guest_sees_the_home_hub(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Увійти')
            ->assertSee('Зареєструватися');
    }

    /**
     * Авторизований користувач не має повертатися на гостьову стартову сторінку.
     */
    public function test_an_authenticated_user_is_redirected_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard', absolute: false));
    }
}
