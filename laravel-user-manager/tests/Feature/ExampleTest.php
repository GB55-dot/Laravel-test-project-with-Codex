<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_home_page_redirects_to_user_management(): void
    {
        $this->get('/')
            ->assertRedirect(route('users.index', absolute: false));
    }
}
