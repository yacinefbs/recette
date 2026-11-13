<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use App\Services\TastyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialAdminTest extends TestCase
{
    use RefreshDatabase;

    private function recipe(): Recipe
    {
        return Recipe::create(['tasty_id' => 1, 'name' => 'Test Pie', 'description' => 'Tasty']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/social-admin')->assertRedirect('/social-admin/login');
        $this->post('/social-admin/recipes/1/facebook')->assertRedirect('/social-admin/login');
    }

    public function test_login_page_is_noindex(): void
    {
        $this->get('/social-admin/login')->assertOk()->assertSee('noindex', false);
    }

    public function test_bad_and_good_login(): void
    {
        $user = User::factory()->create(['password' => 'secret-password-1']);
        $this->post('/social-admin/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/social-admin/login', ['email' => $user->email, 'password' => 'secret-password-1'])->assertRedirect('/social-admin');
    }

    public function test_flag_defaults_to_zero_and_publish_sets_it(): void
    {
        config(['services.facebook.page_id' => '123', 'services.facebook.page_token' => 'tok']);
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '123_456'])]);
        $recipe = $this->recipe();
        $this->assertFalse($recipe->fresh()->posted_facebook);

        $this->actingAs(User::factory()->create())->post("/social-admin/recipes/{$recipe->id}/facebook")->assertSessionHas('status');

        $recipe->refresh();
        $this->assertTrue($recipe->posted_facebook);
        $this->assertSame('123_456', $recipe->facebook_post_id);
    }

    public function test_pinterest_boards_are_loaded_with_the_server_side_access_token(): void
    {
        config(['services.pinterest.access_token' => 'pinterest-test-token']);
        Http::fake(['api.pinterest.com/v5/boards*' => Http::response([
            'items' => [['id' => 'board-1', 'name' => 'Recipes']],
            'bookmark' => null,
        ])]);

        $this->actingAs(User::factory()->create())
            ->getJson(route('social.pinterest.boards'))
            ->assertOk()
            ->assertJsonPath('items.0.id', 'board-1')
            ->assertJsonPath('items.0.name', 'Recipes');

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer pinterest-test-token'));
    }

    public function test_publish_to_facebook_and_pinterest_saves_each_publication(): void
    {
        config([
            'services.facebook.page_id' => '123',
            'services.facebook.page_token' => 'facebook-test-token',
            'services.pinterest.access_token' => 'pinterest-test-token',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => '123_456']),
            'api.pinterest.com/v5/boards*' => Http::response([
                'items' => [['id' => 'board-1', 'name' => 'Recipes']],
                'bookmark' => null,
            ]),
            'api.pinterest.com/v5/pins' => Http::response(['id' => 'pin-789']),
        ]);
        $recipe = $this->recipe();

        $this->actingAs(User::factory()->create())
            ->postJson(route('social.recipes.publish', $recipe), [
                'networks' => ['facebook', 'pinterest'],
                'board_id' => 'board-1',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $recipe->refresh();
        $this->assertTrue($recipe->posted_facebook);
        $this->assertTrue($recipe->posted_pinterest);
        $this->assertSame('pin-789', $recipe->pinterest_pin_id);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.pinterest.com/v5/pins'
            && $request['board_id'] === 'board-1'
            && $request['media_source']['source_type'] === 'image_url'
            && $request['link'] === route('recipes.show', $recipe));
    }

    public function test_selected_networks_can_be_published_independently(): void
    {
        config(['services.pinterest.access_token' => 'pinterest-test-token']);
        Http::fake([
            'api.pinterest.com/v5/boards*' => Http::response([
                'items' => [['id' => 'board-1', 'name' => 'Recipes']],
                'bookmark' => null,
            ]),
            'api.pinterest.com/v5/pins' => Http::response(['id' => 'pin-789']),
        ]);
        $recipe = $this->recipe();

        $this->actingAs(User::factory()->create())
            ->postJson(route('social.recipes.publish', $recipe), [
                'networks' => ['pinterest'],
                'board_id' => 'board-1',
            ])
            ->assertOk();

        $this->assertFalse($recipe->fresh()->posted_facebook);
        $this->assertTrue($recipe->fresh()->posted_pinterest);
    }

    public function test_partial_publish_keeps_success_and_reports_failure_for_retry(): void
    {
        config([
            'services.facebook.page_id' => '123',
            'services.facebook.page_token' => 'facebook-test-token',
            'services.pinterest.access_token' => 'pinterest-test-token',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => '123_456']),
            'api.pinterest.com/v5/boards*' => Http::response([
                'items' => [['id' => 'board-1', 'name' => 'Recipes']],
                'bookmark' => null,
            ]),
            'api.pinterest.com/v5/pins' => Http::response(['message' => 'Pin rejected'], 400),
        ]);
        $recipe = $this->recipe();

        $this->actingAs(User::factory()->create())
            ->postJson(route('social.recipes.publish', $recipe), [
                'networks' => ['facebook', 'pinterest'],
                'board_id' => 'board-1',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('partial', true);

        $this->assertTrue($recipe->fresh()->posted_facebook);
        $this->assertFalse($recipe->fresh()->posted_pinterest);
    }

    public function test_facebook_error_keeps_flag_off(): void
    {
        config(['services.facebook.page_id' => '123', 'services.facebook.page_token' => 'tok']);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Bad token']], 400)]);
        $recipe = $this->recipe();

        $this->actingAs(User::factory()->create())->post("/social-admin/recipes/{$recipe->id}/facebook")->assertSessionHas('error');
        $this->assertFalse($recipe->fresh()->posted_facebook);
    }

    public function test_ajax_publish_returns_json(): void
    {
        config(['services.facebook.page_id' => '123', 'services.facebook.page_token' => 'tok']);
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '1_2'])]);
        $recipe = $this->recipe();

        $this->actingAs(User::factory()->create())
            ->postJson("/social-admin/recipes/{$recipe->id}/facebook")
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertTrue($recipe->fresh()->posted_facebook);
    }

    public function test_dashboard_and_toggle(): void
    {
        $recipe = $this->recipe();
        $user = User::factory()->create();
        $this->actingAs($user)->get('/social-admin')->assertOk()->assertSee('Test Pie');
        $this->actingAs($user)->post("/social-admin/recipes/{$recipe->id}/facebook/toggle");
        $this->assertTrue($recipe->fresh()->posted_facebook);
    }

    public function test_publish_form_selects_both_configured_networks_by_default(): void
    {
        config([
            'services.facebook.page_id' => '123',
            'services.facebook.page_token' => 'facebook-test-token',
            'services.pinterest.access_token' => 'pinterest-test-token',
        ]);
        $recipe = $this->recipe();

        $this->actingAs(User::factory()->create())
            ->get('/social-admin')
            ->assertOk()
            ->assertSee('value="facebook" checked', false)
            ->assertSee('value="pinterest" checked', false)
            ->assertSee('Publish to selected networks');
    }

    public function test_only_authenticated_users_can_fetch_recipes(): void
    {
        $this->post(route('social.recipes.fetch'))->assertRedirect('/social-admin/login');
    }

    public function test_dashboard_shows_fetch_action_without_feed_token(): void
    {
        config(['services.tasty.feed_token' => 'private-feed-token']);

        $this->actingAs(User::factory()->create())->get('/social-admin')
            ->assertOk()
            ->assertSee('Get recipes')
            ->assertSee(route('social.recipes.fetch'))
            ->assertSee('name="_token"', false)
            ->assertDontSee('private-feed-token');
    }

    public function test_fetch_recipes_uses_existing_import_and_reports_the_result(): void
    {
        $this->mock(TastyService::class)
            ->shouldReceive('import')
            ->once()
            ->withNoArgs()
            ->andReturn(3);

        $this->actingAs(User::factory()->create())
            ->from('/social-admin?status=pending&q=chicken')
            ->post(route('social.recipes.fetch'))
            ->assertRedirect('/social-admin?status=pending&q=chicken')
            ->assertSessionHas('status', '3 recipe(s) processed. Total recipes: 0.');
    }

    public function test_fetch_recipes_reports_api_errors(): void
    {
        Http::fake(['*' => Http::response([], 429)]);

        $this->actingAs(User::factory()->create())
            ->post(route('social.recipes.fetch'))
            ->assertSessionHas('error', 'Recipe API quota reached. Please try again later.');
    }
}
