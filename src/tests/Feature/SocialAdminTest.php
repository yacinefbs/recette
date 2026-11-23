<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use App\Services\TastyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->get('/social-admin/login')
            ->assertOk()
            ->assertSee('noindex', false)
            ->assertSee('<link rel="icon" type="image/svg+xml" href="'.asset('assets/images/social-admin-favicon.svg').'">', false);
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

    public function test_dashboard_shows_reusable_facebook_posts_for_meal_planner_and_tools(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/social-admin')->assertOk()
            ->assertSee('<link rel="icon" type="image/svg+xml" href="'.asset('assets/images/social-admin-favicon.svg').'">', false)
            ->assertSee('Promote TopicTrail tools')
            ->assertSee('Ready-to-edit Facebook posts')
            ->assertSee(route('social.tools.publish', 'meal-planner'))
            ->assertSee(route('social.tools.publish', 'cooking-timer'))
            ->assertSee(route('social.tools.publish', 'air-fryer-calculator'))
            ->assertSee(route('social.tools.publish', 'baking-calculator'))
            ->assertSee(route('tools.baking-calculator'))
            ->assertSee(asset('assets/images/social/baking-calculator-og.jpg'))
            ->assertSee(route('social.tools.publish', 'electricity-cost-calculator'))
            ->assertSee(route('tools.electricity.calculator'))
            ->assertSee(asset('assets/images/social/electricity-cost-calculator-og.jpg'))
            ->assertSee(route('social.tools.publish', 'recipe-cost-calculator'))
            ->assertSee(route('tools.recipe-cost-calculator'))
            ->assertSee(asset('assets/images/social/recipe-cost-calculator-og.jpg'))
            ->assertSee(route('tools.air-fryer-calculator'))
            ->assertSee(asset('assets/images/social/air-fryer-calculator-og.jpg'))
            ->assertSee(route('social.tools.publish', 'pantry-inventory'))
            ->assertSee(route('social.tools.publish', 'cooking-converter'))
            ->assertSee(route('social.tools.publish', 'nutrition-calculator'))
            ->assertSee(route('social.tools.publish', 'what-can-i-cook'))
            ->assertSee(route('social.tools.publish', 'ingredient-substitution'))
            ->assertSee('Make the week’s meals easier to plan.');

        $this->assertSame(11, substr_count($response->getContent(), 'class="js-tool-fb-form"'));
    }

    public function test_tool_post_publishes_the_selected_page_without_changing_recipe_status(): void
    {
        config(['services.facebook.page_id' => 'page-123', 'services.facebook.page_token' => 'secret-page-token']);
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'page-123_post-456'])]);
        $recipe = $this->recipe();

        $this->actingAs(User::factory()->create())
            ->postJson(route('social.tools.publish', 'cooking-timer'), [
                'message' => 'Keep multiple kitchen timers in one place! ⏲️',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => '"Cooking Timer" posted to Facebook.']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/page-123/feed')
                && $request['link'] === route('tools.cooking-timer')
                && $request['message'] === 'Keep multiple kitchen timers in one place! ⏲️'
                && $request['access_token'] === 'secret-page-token';
        });
        $this->assertFalse($recipe->fresh()->posted_facebook);
        $this->assertNull($recipe->fresh()->facebook_post_id);
    }

    public function test_air_fryer_tool_post_publishes_its_page_link(): void
    {
        config(['services.facebook.page_id' => 'page-123', 'services.facebook.page_token' => 'secret-page-token']);
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'page-123_post-air-fryer'])]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('social.tools.publish', 'air-fryer-calculator'), [
                'message' => 'Find sourced air fryer cooking times with TopicTrail.',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => '"Air Fryer Calculator" posted to Facebook.']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/page-123/feed')
                && $request['link'] === route('tools.air-fryer-calculator')
                && $request['message'] === 'Find sourced air fryer cooking times with TopicTrail.'
                && $request['access_token'] === 'secret-page-token';
        });
    }

    public function test_baking_calculator_tool_post_publishes_its_page_link(): void
    {
        config(['services.facebook.page_id' => 'page-123', 'services.facebook.page_token' => 'secret-page-token']);
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'page-123_post-baking'])]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('social.tools.publish', 'baking-calculator'), [
                'message' => 'Scale bread and pizza dough with TopicTrail.',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => '"Baking Calculator" posted to Facebook.']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/page-123/feed')
                && $request['link'] === route('tools.baking-calculator')
                && $request['message'] === 'Scale bread and pizza dough with TopicTrail.'
                && $request['access_token'] === 'secret-page-token';
        });
    }

    public function test_electricity_calculator_tool_post_publishes_its_page_link(): void
    {
        config(['services.facebook.page_id' => 'page-123', 'services.facebook.page_token' => 'secret-page-token']);
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'page-123_post-electricity'])]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('social.tools.publish', 'electricity-cost-calculator'), [
                'message' => 'Estimate your appliance electricity costs with TopicTrail.',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => '"Electricity Cost Calculator" posted to Facebook.']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/page-123/feed')
                && $request['link'] === route('tools.electricity.calculator')
                && $request['message'] === 'Estimate your appliance electricity costs with TopicTrail.'
                && $request['access_token'] === 'secret-page-token';
        });
    }

    public function test_recipe_cost_tool_post_publishes_its_page_link(): void
    {
        config(['services.facebook.page_id' => 'page-123', 'services.facebook.page_token' => 'secret-page-token']);
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'page-123_post-recipe-cost'])]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('social.tools.publish', 'recipe-cost-calculator'), [
                'message' => 'Estimate the ingredient cost for your recipes.',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => '"Recipe Cost Calculator" posted to Facebook.']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/page-123/feed')
                && $request['link'] === route('tools.recipe-cost-calculator')
                && $request['message'] === 'Estimate the ingredient cost for your recipes.'
                && $request['access_token'] === 'secret-page-token';
        });
    }

    public function test_tool_post_reports_facebook_errors_and_rejects_unknown_pages(): void
    {
        config(['services.facebook.page_id' => 'page-123', 'services.facebook.page_token' => 'secret-page-token']);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Page permission denied']], 400)]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('social.tools.publish', 'meal-planner'), ['message' => 'Try this free meal planner.'])
            ->assertUnprocessable()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'Facebook: Page permission denied');

        $this->postJson(route('social.tools.publish', 'unknown-tool'), ['message' => 'A valid length post.'])
            ->assertNotFound();
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
