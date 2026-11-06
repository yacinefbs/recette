<?php

namespace Tests\Feature;

use App\Models\CookingGuide;
use App\Models\CookingGuideCategory;
use Database\Seeders\CookingGuidesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookingGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CookingGuidesSeeder::class);
    }

    public function test_index_hub_loads_with_seo_and_schema(): void
    {
        $response = $this->get(route('cooking-tips.index'));

        $response->assertOk();
        $response->assertSee('Cooking Tips &amp; Guides', false);
        $response->assertSee('What cooking problem can we help you solve?', false);
        $response->assertSee('Cooking Techniques', false);
        $response->assertSee('Food Storage &amp; Preservation', false);
        $response->assertSee('<link rel="canonical" href="'.route('cooking-tips.index').'">', false);
        $response->assertSee('"@type":"CollectionPage"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_category_page_renders_guides(): void
    {
        $category = CookingGuideCategory::where('slug', 'techniques')->firstOrFail();

        $response = $this->get(route('cooking-tips.category', $category->slug));

        $response->assertOk();
        $response->assertSee('Cooking Techniques', false);
        $response->assertSee('How to Cook Rice Perfectly Every Time', false);
        $response->assertSee('How to Keep Chicken Breast Moist and Juicy', false);
        $response->assertSee('<link rel="canonical" href="'.route('cooking-tips.category', $category->slug).'">', false);
    }

    public function test_individual_guide_article_page_renders_complete_structure(): void
    {
        $guide = CookingGuide::where('slug', 'how-to-cook-rice-perfectly-every-time')->firstOrFail();
        $category = $guide->category;

        $response = $this->get(route('cooking-tips.show', ['category' => $category->slug, 'guide' => $guide->slug]));

        $response->assertOk();
        $response->assertSee($guide->title, false);
        $response->assertSee('Quick Answer', false);
        $response->assertSee('What You\'ll Need', false);
        $response->assertSee('Always Rinse the Rice', false);
        $response->assertSee('Common Mistakes &amp; How to Avoid Them', false);
        $response->assertSee('Need to Convert Rice Cups to Grams?', false);
        $response->assertSee('Frequently Asked Questions', false);
        $response->assertSee('Scientific &amp; Culinary References', false);

        // SEO Schemas
        $response->assertSee('"@type":"Article"', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_problem_solver_search_api_endpoint(): void
    {
        // Query for dry chicken problem
        $chickenRes = $this->getJson(route('cooking-tips.search', ['q' => 'my chicken is too dry']));
        $chickenRes->assertOk();
        $chickenRes->assertJsonFragment(['title' => 'How to Keep Chicken Breast Moist and Juicy (The 4 Rules)']);

        // Query for sticky rice problem
        $riceRes = $this->getJson(route('cooking-tips.search', ['q' => 'sticky rice']));
        $riceRes->assertOk();
        $riceRes->assertJsonFragment(['title' => 'How to Cook Rice Perfectly Every Time (Never Mushy, Never Burnt)']);

        // Query for salty soup problem
        $soupRes = $this->getJson(route('cooking-tips.search', ['q' => 'my soup is too salty']));
        $soupRes->assertOk();
        $soupRes->assertJsonFragment(['title' => 'How to Fix an Oversalted Soup or Sauce (What Works vs. What Fails)']);

        // Query for sunken cake
        $cakeRes = $this->getJson(route('cooking-tips.search', ['q' => 'cake collapsed']));
        $cakeRes->assertOk();
        $cakeRes->assertJsonFragment(['title' => 'Why Do Cakes Sink in the Middle? (5 Causes & How to Fix Them)']);
    }

    public function test_sitemap_and_llms_include_cooking_tips_and_guides(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk()->content();
        $this->assertStringContainsString(route('cooking-tips.index'), $sitemap);
        $this->assertStringContainsString(route('cooking-tips.category', 'techniques'), $sitemap);
        $this->assertStringContainsString('how-to-cook-rice-perfectly-every-time', $sitemap);

        $llms = $this->get('/llms.txt')->assertOk()->content();
        $this->assertStringContainsString(route('cooking-tips.index'), $llms);
    }
}
