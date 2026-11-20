<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_monetag_scripts_and_verification_tag_are_not_rendered(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('monetag')
            ->assertDontSee('quge5.com/88/tag.min.js')
            ->assertDontSee('n6wxm.com/vignette.min.js');
    }
}
