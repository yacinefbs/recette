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

    public function test_monetag_site_verification_meta_tag_is_rendered_when_configured(): void
    {
        config(['app.monetag_site_verification' => 'test-verification-token']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="monetag" content="test-verification-token">', false);
    }
}
