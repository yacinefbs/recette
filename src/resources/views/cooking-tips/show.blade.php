@extends('pages.layout')

@section('hero')
<section class="tt-hero tt-hero--page pb-4">
    <div class="container text-center">
        <div class="mb-2">
            <a href="{{ route('cooking-tips.category', $category->slug) }}" class="tt-eyebrow text-decoration-none">
                <i class="fas {{ $category->icon }} mr-1"></i> {{ $category->name }}
            </a>
        </div>
        <h1 class="display-6 fw-bold mb-3 mx-auto" style="max-width: 820px; color: var(--tt-ink);">{{ $guide->title }}</h1>

        <div class="d-flex justify-content-center align-items-center flex-wrap gap-3 text-muted small mb-3">
            <span><i class="far fa-user mr-1 text-primary"></i> By {{ $guide->author_name }}</span>
            <span><i class="far fa-clock mr-1 text-primary"></i> {{ $guide->reading_time_minutes }} min read</span>
            <span><i class="far fa-calendar-alt mr-1 text-primary"></i> Updated {{ $guide->updated_at->format('M d, Y') }}</span>
        </div>

        <ul class="tt-crumbs">
            <li><a href="/">Home</a></li>
            <li><a href="{{ route('cooking-tips.index') }}">Cooking Tips &amp; Guides</a></li>
            <li><a href="{{ route('cooking-tips.category', $category->slug) }}">{{ $category->name }}</a></li>
            <li aria-current="page">{{ Str::limit($guide->title, 35) }}</li>
        </ul>
    </div>
</section>
@endsection

@section('content')
<div class="row g-4 py-4">
    <!-- Main Article Body Column -->
    <div class="col-lg-8">
        <article class="tt-guide-article bg-white p-4 p-md-5 rounded-4 shadow-sm border">
            <!-- Share partial -->
            <div class="mb-4 pb-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small fst-italic">Verified kitchen guidance from TopicTrail editors</span>
                @include('partials.share', [
                    'url' => $seo['canonical'],
                    'title' => $guide->title,
                    'image' => $guide->image ?? null,
                    'meta' => $guide->reading_time_minutes . ' min read',
                ])
            </div>

            <!-- Quick Answer Box (The immediate solution for readers) -->
            @if (!empty($guide->quick_answer))
                <div class="p-4 mb-4 rounded-3 border-start border-4 border-warning shadow-sm" style="background-color: #fffdf5;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-bolt text-warning fs-5"></i>
                        <h2 class="h5 mb-0 fw-bold text-dark">Quick Answer</h2>
                    </div>
                    <p class="mb-0 text-secondary" style="font-size: 1.05rem; line-height: 1.6;">
                        {{ $guide->quick_answer }}
                    </p>
                </div>
            @endif

            <!-- Excerpt / Problem Introduction -->
            <div class="tt-guide-lead mb-4 lead" style="color: #334155; line-height: 1.75;">
                {{ $guide->excerpt }}
            </div>

            <!-- What You'll Need Box -->
            @if (!empty($guide->what_you_need) && count($guide->what_you_need) > 0)
                <div class="p-4 mb-4 rounded-3 border bg-light">
                    <h2 class="h5 fw-bold mb-3 text-dark">
                        <i class="fas fa-clipboard-list text-primary mr-2"></i> What You'll Need
                    </h2>
                    <div class="row g-2">
                        @foreach ($guide->what_you_need as $item)
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-check text-success small"></i>
                                    <span class="text-dark small">{{ $item }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Step by Step Content Sections -->
            @if (!empty($guide->content_sections))
                <div class="tt-guide-sections my-4">
                    @foreach ($guide->content_sections as $index => $section)
                        <section class="mb-4 pb-2">
                            <h2 class="h4 fw-bold text-dark mb-2">
                                {{ $section['title'] ?? 'Step ' . ($index + 1) }}
                            </h2>
                            <p class="text-secondary" style="line-height: 1.75; font-size: 1.02rem;">
                                {!! nl2br(e($section['body'] ?? '')) !!}
                            </p>
                        </section>
                    @endforeach
                </div>
            @endif

            <!-- Common Mistakes to Avoid -->
            @if (!empty($guide->common_mistakes) && count($guide->common_mistakes) > 0)
                <div class="p-4 mb-4 rounded-3 border" style="background-color: #fff9f9;">
                    <h2 class="h5 fw-bold text-danger mb-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Common Mistakes &amp; How to Avoid Them
                    </h2>
                    <ul class="list-unstyled mb-0">
                        @foreach ($guide->common_mistakes as $mistake)
                            <li class="mb-3 pb-2 border-bottom border-danger-subtle">
                                <strong class="text-dark d-block mb-1">&times; {{ $mistake['mistake'] }}</strong>
                                <span class="text-secondary small d-block"><strong class="text-success">&check; The Fix:</strong> {{ $mistake['fix'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Storage & Pro Tips -->
            @if (!empty($guide->pro_tips) && count($guide->pro_tips) > 0)
                <div class="p-4 mb-4 rounded-3 border" style="background-color: #f0fdf4;">
                    <h2 class="h5 fw-bold text-success mb-3">
                        <i class="fas fa-lightbulb mr-2"></i> Chef's Pro Tips
                    </h2>
                    <ul class="mb-0 ps-3">
                        @foreach ($guide->pro_tips as $tip)
                            <li class="mb-2 text-secondary">{{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Connected TopicTrail Tool Box -->
            @if (!empty($guide->related_tool_key))
                @php
                    $toolRoutes = [
                        'converter' => ['route' => route('tools.converter'), 'icon' => 'fa-balance-scale', 'color' => 'primary', 'btn' => 'Open Cooking Converter'],
                        'substitution' => ['route' => route('tools.ingredient-substitution'), 'icon' => 'fa-exchange-alt', 'color' => 'success', 'btn' => 'Open Substitution Tool'],
                        'nutrition' => ['route' => route('tools.nutrition-calculator'), 'icon' => 'fa-calculator', 'color' => 'warning', 'btn' => 'Open Nutrition Calculator'],
                        'meal-planner' => ['route' => route('meal-planner.index'), 'icon' => 'fa-calendar-alt', 'color' => 'info', 'btn' => 'Open Weekly Meal Planner'],
                        'what-can-i-cook' => ['route' => route('tools.what'), 'icon' => 'fa-utensils', 'color' => 'primary', 'btn' => 'Find Recipes for Leftovers'],
                    ];
                    $t = $toolRoutes[$guide->related_tool_key] ?? $toolRoutes['converter'];
                @endphp
                <div class="p-4 my-4 rounded-3 border bg-light d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 shadow-sm">
                    <div class="d-flex align-items-start gap-3">
                        <div class="fs-2 text-{{ $t['color'] }}"><i class="fas {{ $t['icon'] }}"></i></div>
                        <div>
                            <h3 class="h6 fw-bold mb-1 text-dark">{{ $guide->related_tool_headline ?: 'Try TopicTrail Kitchen Tools' }}</h3>
                            <p class="small text-muted mb-0">{{ $guide->related_tool_text ?: 'Put this culinary advice into action with our free tools.' }}</p>
                        </div>
                    </div>
                    <a href="{{ $t['route'] }}" class="btn btn-outline-{{ $t['color'] }} btn-sm text-nowrap fw-bold">
                        {{ $t['btn'] }} &raquo;
                    </a>
                </div>
            @endif

            <!-- Related Recipes Section -->
            @if ($guide->recipes->isNotEmpty())
                <div class="mt-5 pt-4 border-top">
                    <h2 class="h5 fw-bold mb-3"><i class="fas fa-utensils text-primary mr-2"></i> Recipes That Use This Guide</h2>
                    <div class="row g-3">
                        @foreach ($guide->recipes as $r)
                            <div class="col-md-4">
                                <a href="{{ route('recipes.show', $r) }}" class="card h-100 text-decoration-none shadow-sm border rounded-3 overflow-hidden">
                                    <img src="{{ $r->image }}" alt="{{ $r->name }}" class="card-img-top" style="height: 120px; object-fit: cover;">
                                    <div class="p-2">
                                        <h4 class="small fw-bold text-dark mb-0 text-truncate">{{ $r->name }}</h4>
                                        <small class="text-muted">{{ $r->total_time_minutes ? $r->total_time_minutes . ' min' : 'Easy' }}</small>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Frequently Asked Questions -->
            @if (!empty($guide->faqs) && count($guide->faqs) > 0)
                <div class="mt-5 pt-4 border-top">
                    <h2 class="h4 fw-bold mb-3"><i class="fas fa-question-circle text-primary mr-2"></i> Frequently Asked Questions</h2>
                    <div class="accordion" id="guideFaqAccordion">
                        @foreach ($guide->faqs as $fIndex => $faq)
                            <div class="accordion-item mb-2 border rounded">
                                <h3 class="accordion-header" id="faq-h-{{ $fIndex }}">
                                    <button class="accordion-button {{ $fIndex > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c-{{ $fIndex }}" aria-expanded="{{ $fIndex === 0 ? 'true' : 'false' }}" aria-controls="faq-c-{{ $fIndex }}">
                                        {{ $faq['question'] }}
                                    </button>
                                </h3>
                                <div id="faq-c-{{ $fIndex }}" class="accordion-collapse collapse {{ $fIndex === 0 ? 'show' : '' }}" aria-labelledby="faq-h-{{ $fIndex }}" data-bs-parent="#guideFaqAccordion">
                                    <div class="accordion-body text-secondary small" style="line-height: 1.6;">
                                        {{ $faq['answer'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Authoritative Sources & Citations -->
            @if (!empty($guide->sources) && count($guide->sources) > 0)
                <div class="mt-4 pt-3 border-top small text-muted">
                    <div class="fw-bold mb-1"><i class="fas fa-shield-alt text-success mr-1"></i> Scientific &amp; Culinary References:</div>
                    <ul class="ps-3 mb-0">
                        @foreach ($guide->sources as $source)
                            <li>
                                <strong>{{ $source['title'] }}</strong> &ndash; <span class="text-secondary">{{ $source['authority'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </article>
    </div>

    <!-- Right Sidebar Column -->
    <div class="col-lg-4">
        <!-- Guide Navigation & Category Card -->
        <div class="card shadow-sm border-0 rounded-4 mb-4 p-4 sticky-top" style="top: 25px;">
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: #fff3e6; color: var(--tt-primary-d);">
                    <i class="fas {{ $category->icon }}"></i>
                </div>
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Topic Category</span>
                    <h3 class="h6 mb-0 fw-bold"><a href="{{ route('cooking-tips.category', $category->slug) }}" class="text-dark text-decoration-none">{{ $category->name }}</a></h3>
                </div>
            </div>
            <p class="small text-muted mb-3">{{ $category->description }}</p>

            <hr class="my-2">

            <!-- More Guides in this Category -->
            @if ($relatedGuides->isNotEmpty())
                <h4 class="h6 fw-bold my-3">More in {{ $category->name }}:</h4>
                <ul class="list-unstyled mb-0">
                    @foreach ($relatedGuides as $rg)
                        <li class="mb-3 pb-2 border-bottom">
                            <a href="{{ route('cooking-tips.show', ['category' => $category->slug, 'guide' => $rg->slug]) }}" class="d-block text-dark fw-bold text-decoration-none small mb-1">
                                {{ $rg->title }}
                            </a>
                            <small class="text-muted"><i class="far fa-clock mr-1"></i>{{ $rg->reading_time_minutes }} min read</small>
                        </li>
                    @endforeach
                </ul>
            @endif

            <!-- Problem quick link -->
            <div class="p-3 bg-light rounded border mt-3 text-center">
                <span class="small fw-bold d-block text-dark mb-1">Got another cooking question?</span>
                <a href="{{ route('cooking-tips.index') }}" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-search mr-1"></i> Use Cooking Problem Solver
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
