<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookingTimerTest extends TestCase
{
    use RefreshDatabase;

    public function test_cooking_timer_page_renders_seo_tooling_and_safety_guidance(): void
    {
        $this->get(route('tools.cooking-timer'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('tools.cooking-timer').'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('assets/images/social/cooking-timer-og.jpg').'">', false)
            ->assertSee('<meta name="twitter:image" content="'.asset('assets/images/social/cooking-timer-og.jpg').'">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false)
            ->assertSee('"@type":"WebApplication"', false)
            ->assertSee('Free Online Cooking Timer')
            ->assertSee('timer-active-list', false)
            ->assertSee('timer-completed-list', false)
            ->assertSee('cooking-timer-core.js', false)
            ->assertSee('cooking-timer.js', false)
            ->assertSee('FoodSafety.gov safe minimum internal temperatures')
            ->assertSee(route('tools.cooking-timer'));
    }

    public function test_cooking_timer_is_listed_as_a_clean_canonical_url(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.route('tools.cooking-timer').'</loc>', false)
            ->assertDontSee('/src/public/')
            ->assertDontSee('www.topictrail.me');

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee(route('tools.cooking-timer'));
    }
}
