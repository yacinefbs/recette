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

    public function test_adsterra_units_are_rendered_for_public_pages_and_wait_for_consent(): void
    {
        $this->get('/', ['HTTP_HOST' => 'topictrail.me'])
            ->assertOk()
            ->assertSee('assets/js/adsterra.js')
            ->assertSee('tt_consent_v2')
            ->assertSee('data-adsterra-placement')
            ->assertSee('https://araplhn.org/4/b0f8fcde3e47c50b13231d916fc6d51b')
            ->assertSee('Adsterra');
    }

    public function test_ga4_page_view_waits_for_consent_and_is_sent_when_granted(): void
    {
        $this->get('/', ['HTTP_HOST' => 'topictrail.me'])
            ->assertOk()
            ->assertSee("send_page_view: tcConsent === 'granted'", false)
            ->assertSee("previous !== 'granted' && value === 'granted'", false)
            ->assertSee("gtag('event', 'page_view'", false);
    }
}
