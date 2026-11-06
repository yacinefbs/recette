<?php

namespace App\Http\Controllers;

use App\Models\CookingGuide;
use App\Models\CookingGuideCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CookingGuideController extends Controller
{
    public function index(Request $request)
    {
        $site = config('app.name', 'TopicTrail');
        $canonical = route('cooking-tips.index');

        $categories = CookingGuideCategory::query()
            ->withCount('publishedGuides')
            ->ordered()
            ->get();

        $featuredGuides = CookingGuide::query()
            ->with('category')
            ->published()
            ->featured()
            ->take(4)
            ->get();

        if ($featuredGuides->isEmpty()) {
            $featuredGuides = CookingGuide::query()
                ->with('category')
                ->published()
                ->latest()
                ->take(4)
                ->get();
        }

        $latestGuides = CookingGuide::query()
            ->with('category')
            ->published()
            ->latest()
            ->paginate(9);

        $seo = [
            'title' => 'Cooking Tips & Guides – Kitchen Techniques, Ingredients & Hacks | '.$site,
            'description' => 'Practical cooking knowledge, ingredient guides, kitchen hacks, food storage rules, and everyday culinary tips to help you cook better.',
            'canonical' => $canonical,
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Cooking Tips & Guides', 'item' => $canonical],
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => 'Cooking Tips & Guides – '.$site,
                    'description' => 'Learn cooking techniques, discover ingredient properties, and solve everyday kitchen problems.',
                    'url' => $canonical,
                ],
            ],
        ];

        return view('cooking-tips.index', [
            'seo' => $seo,
            'pageTitle' => 'Cooking Tips & Guides',
            'categories' => $categories,
            'featuredGuides' => $featuredGuides,
            'latestGuides' => $latestGuides,
            'wide' => true,
        ]);
    }

    public function category(string $categorySlug)
    {
        $category = CookingGuideCategory::query()
            ->where('slug', $categorySlug)
            ->firstOrFail();

        $guides = CookingGuide::query()
            ->where('category_id', $category->id)
            ->published()
            ->latest()
            ->paginate(12);

        $site = config('app.name', 'TopicTrail');
        $url = route('cooking-tips.category', $category->slug);

        $seo = [
            'title' => ($category->seo_title ?: $category->name.' – Cooking Guides').' | '.$site,
            'description' => $category->seo_description ?: Str::limit($category->description, 155),
            'canonical' => $url,
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Cooking Tips & Guides', 'item' => route('cooking-tips.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $category->name, 'item' => $url],
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $category->name.' Guides – '.$site,
                    'description' => $category->description,
                    'url' => $url,
                ],
            ],
        ];

        return view('cooking-tips.category', [
            'seo' => $seo,
            'category' => $category,
            'guides' => $guides,
            'pageTitle' => $category->name,
            'wide' => true,
        ]);
    }

    public function show(string $categorySlug, string $guideSlug)
    {
        $category = CookingGuideCategory::query()
            ->where('slug', $categorySlug)
            ->firstOrFail();

        $guide = CookingGuide::query()
            ->with(['recipes', 'category'])
            ->where('category_id', $category->id)
            ->where('slug', $guideSlug)
            ->published()
            ->firstOrFail();

        $relatedGuides = CookingGuide::query()
            ->with('category')
            ->where('category_id', $category->id)
            ->where('id', '!=', $guide->id)
            ->published()
            ->inRandomOrder()
            ->take(3)
            ->get();

        $site = config('app.name', 'TopicTrail');
        $canonical = route('cooking-tips.show', ['category' => $category->slug, 'guide' => $guide->slug]);

        // Build schemas: Article, Breadcrumbs, and FAQPage
        $schemas = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Cooking Tips & Guides', 'item' => route('cooking-tips.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $category->name, 'item' => route('cooking-tips.category', $category->slug)],
                    ['@type' => 'ListItem', 'position' => 4, 'name' => $guide->title, 'item' => $canonical],
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $guide->title,
                'description' => $guide->meta_description ?: $guide->excerpt,
                'image' => [$guide->image],
                'datePublished' => $guide->published_at?->toIso8601String() ?? $guide->created_at->toIso8601String(),
                'dateModified' => $guide->updated_at->toIso8601String(),
                'author' => [
                    '@type' => 'Organization',
                    'name' => $guide->author_name ?: $site,
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => $site,
                    'url' => url('/'),
                ],
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $canonical,
                ],
            ],
        ];

        if (! empty($guide->faqs)) {
            $faqEntities = [];
            foreach ($guide->faqs as $faq) {
                if (! empty($faq['question']) && ! empty($faq['answer'])) {
                    $faqEntities[] = [
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq['answer'],
                        ],
                    ];
                }
            }
            if ($faqEntities !== []) {
                $schemas[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => $faqEntities,
                ];
            }
        }

        $seo = [
            'title' => ($guide->meta_title ?: $guide->title).' | '.$site,
            'description' => $guide->meta_description ?: Str::limit($guide->excerpt, 155),
            'canonical' => $canonical,
            'type' => 'article',
            'image' => $guide->image,
            'schema' => $schemas,
        ];

        return view('cooking-tips.show', [
            'guide' => $guide,
            'category' => $category,
            'relatedGuides' => $relatedGuides,
            'seo' => $seo,
            'pageTitle' => $guide->title,
            'wide' => true,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['results' => [], 'query' => '']);
        }

        $needle = Str::lower($q);
        $escaped = addcslashes($needle, '%_\\');

        // Extract key terms excluding conversational stop words
        $stopWords = ['my', 'is', 'too', 'so', 'the', 'a', 'an', 'in', 'on', 'and', 'or', 'for', 'to', 'how', 'why', 'what', 'can', 'we', 'help', 'you', 'solve', 'did', 'not', 'got'];
        $words = collect(explode(' ', $needle))
            ->map(fn ($w) => trim($w, " \t\n\r\0\x0B.,?!'\""))
            ->filter(fn ($w) => mb_strlen($w) >= 3 && ! in_array($w, $stopWords, true))
            ->values();

        $guides = CookingGuide::query()
            ->with('category')
            ->published()
            ->where(function ($query) use ($escaped, $needle, $words) {
                // Direct full-string match
                $query->where('title', 'LIKE', "%{$escaped}%")
                    ->orWhere('excerpt', 'LIKE', "%{$escaped}%")
                    ->orWhere('quick_answer', 'LIKE', "%{$escaped}%")
                    ->orWhereJsonContains('problem_keywords', $needle)
                    ->orWhere('problem_keywords', 'LIKE', "%{$escaped}%");

                // If user typed a sentence, match when all significant words are found
                if ($words->count() >= 2) {
                    $query->orWhere(function ($sub) use ($words) {
                        foreach ($words as $w) {
                            $wEsc = addcslashes($w, '%_\\');
                            $sub->where(function ($wGroup) use ($wEsc) {
                                $wGroup->where('title', 'LIKE', "%{$wEsc}%")
                                    ->orWhere('excerpt', 'LIKE', "%{$wEsc}%")
                                    ->orWhere('problem_keywords', 'LIKE', "%{$wEsc}%");
                            });
                        }
                    });
                }
            })
            ->take(10)
            ->get();

        $results = $guides->map(fn (CookingGuide $g) => [
            'id' => $g->id,
            'title' => $g->title,
            'excerpt' => $g->excerpt,
            'quick_answer' => Str::limit($g->quick_answer, 120),
            'category' => $g->category?->name,
            'category_slug' => $g->category?->slug,
            'url' => route('cooking-tips.show', ['category' => $g->category?->slug ?? 'general', 'guide' => $g->slug]),
            'reading_time' => $g->reading_time_minutes,
        ]);

        return response()->json([
            'results' => $results,
            'query' => $q,
        ]);
    }
}
