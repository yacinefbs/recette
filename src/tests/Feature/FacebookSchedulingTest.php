<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-04-15 12:00:00 UTC');
        config(['services.facebook.page_id' => '123', 'services.facebook.page_token' => 'test-token']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function recipe(): Recipe
    {
        return Recipe::create(['tasty_id' => 999, 'name' => 'Chicken Pie']);
    }

    public function test_delay_sends_schedule_to_facebook_without_marking_published(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '123_456'])]);
        $recipe = $this->recipe();

        $this->actingAs(User::factory()->create())->postJson(route('social.facebook.schedule', $recipe), [
            'schedule_mode' => 'delay', 'delay' => 15, 'delay_unit' => 'minutes', 'message' => 'Hello',
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn (Request $request) => $request['published'] === 'false'
            && (int) $request['scheduled_publish_time'] === CarbonImmutable::parse('2026-04-15 12:15:00 UTC')->timestamp
            && $request['message'] === 'Hello');
        $recipe->refresh();
        $this->assertFalse($recipe->posted_facebook);
        $this->assertSame('123_456', $recipe->facebook_post_id);
        $this->assertSame('2026-04-15 12:15:00', $recipe->facebook_scheduled_at->utc()->format('Y-m-d H:i:s'));
        $this->get('/social-admin?status=pending')->assertDontSee('Chicken Pie');
        $this->get('/social-admin?status=scheduled')->assertSee('Chicken Pie')->assertSee('2026-04-15 13:15');
        $this->post(route('social.facebook.toggle', $recipe))->assertSessionHas('error');
        $this->assertFalse($recipe->fresh()->posted_facebook);
    }

    public function test_morocco_calendar_and_hours_use_correct_instant(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '123_456'])]);
        $user = User::factory()->create();
        $first = $this->recipe();
        $this->actingAs($user)->postJson(route('social.facebook.schedule', $first), [
            'schedule_mode' => 'calendar', 'scheduled_at' => '2026-04-15T14:30',
        ])->assertOk();
        Http::assertSent(fn (Request $request) => (int) $request['scheduled_publish_time'] === CarbonImmutable::parse('2026-04-15 13:30:00 UTC')->timestamp);

        $second = Recipe::create(['tasty_id' => 1000, 'name' => 'Soup']);
        $this->postJson(route('social.facebook.schedule', $second), [
            'schedule_mode' => 'delay', 'delay' => 2, 'delay_unit' => 'hours',
        ])->assertOk();
        $this->assertSame('2026-04-15 14:00:00', $second->fresh()->facebook_scheduled_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_short_invalid_and_unauthenticated_requests_are_rejected(): void
    {
        Http::fake();
        $recipe = $this->recipe();
        $url = route('social.facebook.schedule', $recipe);
        $this->postJson($url, [])->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->postJson($url, ['schedule_mode' => 'delay', 'delay' => 5, 'delay_unit' => 'minutes'])->assertUnprocessable()->assertJsonValidationErrors('schedule');
        $this->postJson($url, ['schedule_mode' => 'delay', 'delay' => 30, 'delay_unit' => 'seconds'])->assertUnprocessable()->assertJsonValidationErrors('delay_unit');
        $this->postJson($url, ['schedule_mode' => 'calendar', 'scheduled_at' => 'not-a-date'])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        Http::assertNothingSent();
    }

    public function test_facebook_rejection_and_duplicate_scheduling(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid time']], 400)]);
        $recipe = $this->recipe();
        $this->actingAs(User::factory()->create())->postJson(route('social.facebook.schedule', $recipe), [
            'schedule_mode' => 'delay', 'delay' => 20, 'delay_unit' => 'minutes',
        ])->assertUnprocessable()->assertJsonPath('ok', false);
        $this->assertNull($recipe->fresh()->facebook_scheduled_at);

        $recipe->forceFill(['facebook_scheduled_at' => now()->addHour(), 'facebook_post_id' => '123_456'])->save();
        $this->postJson(route('social.facebook.schedule', $recipe), [
            'schedule_mode' => 'delay', 'delay' => 20, 'delay_unit' => 'minutes',
        ])->assertStatus(409);
        $this->postJson(route('social.facebook.publish', $recipe))->assertStatus(409);
        Http::assertSentCount(1);
    }

    public function test_clock_change_times_are_not_silently_misinterpreted(): void
    {
        CarbonImmutable::setTestNow('2026-02-01 12:00:00 UTC');
        Http::fake();
        $recipe = $this->recipe();
        $this->actingAs(User::factory()->create());
        foreach (['2026-02-15T02:30', '2026-03-22T02:30'] as $date) {
            $this->postJson(route('social.facebook.schedule', $recipe), [
                'schedule_mode' => 'calendar', 'scheduled_at' => $date,
            ])->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        }
        Http::assertNothingSent();
    }

    public function test_sync_only_marks_posted_after_confirmation(): void
    {
        $recipe = $this->recipe();
        $recipe->forceFill(['facebook_scheduled_at' => now()->addHour(), 'facebook_post_id' => '123_456'])->save();
        $this->actingAs(User::factory()->create());
        Http::fake(['graph.facebook.com/*' => Http::sequence()->push(['is_published' => false])->push(['is_published' => true])]);
        $this->post(route('social.facebook.sync', $recipe))->assertSessionHas('status');
        $this->assertFalse($recipe->fresh()->posted_facebook);
        $this->post(route('social.facebook.sync', $recipe))->assertSessionHas('status');
        $this->assertTrue($recipe->fresh()->posted_facebook);
        $this->assertNull($recipe->fresh()->facebook_scheduled_at);
        $this->assertNull($recipe->fresh()->facebook_posted_at);
    }
}
