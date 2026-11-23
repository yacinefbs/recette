<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacebookAppMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_facebook_app_id_is_rendered_on_all_site_pages(): void
    {
        $pages = [
            '/',
            route('tools.converter'),
            route('tools.electricity.calculator'),
            route('pages.show', 'terms'),
        ];

        foreach ($pages as $page) {
            $this->get($page)
                ->assertOk()
                ->assertSee('<meta property="fb:app_id" content="1166005632599541">', false);
        }
    }

    public function test_facebook_app_id_can_be_overridden_by_environment_configuration(): void
    {
        config(['services.social.facebook_app_id' => '123456789012345']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<meta property="fb:app_id" content="123456789012345">', false);
    }
}
