@extends('pages.layout')

@section('hero')
<section class="tt-hero tt-hero--tips py-5" style="background: radial-gradient(circle at 85% 15%, rgba(255, 198, 0, .25), transparent 45%), linear-gradient(135deg, #fff5ec 0%, #ffe9d6 100%);">
    <div class="container text-center py-4">
        <span class="tt-eyebrow"><i class="fas fa-lightbulb text-warning mr-1"></i> Kitchen Knowledge Base</span>
        <h1 class="display-5 fw-bold mb-3" style="color: var(--tt-ink);">Cooking Tips &amp; Guides</h1>
        <p class="tt-lead mx-auto mb-4" style="max-width: 650px; font-size: 1.15rem; color: #475569;">
            Master culinary techniques, understand your ingredients, avoid common mistakes, and make everyday home cooking easier and tastier.
        </p>

        <!-- Problem Solver Search Bar -->
        <div class="tt-problem-search mx-auto text-start position-relative" style="max-width: 640px;">
            <label for="problem-search-input" class="form-label fw-bold text-muted small text-uppercase mb-1">
                <i class="fas fa-question-circle text-primary mr-1"></i> What cooking problem can we help you solve?
            </label>
            <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden bg-white p-1 border">
                <span class="input-group-text bg-transparent border-0 ps-3"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="problem-search-input" class="form-control border-0 shadow-none px-2" placeholder="e.g. My chicken is too dry, My rice is sticky, My soup is too salty..." autocomplete="off">
                <button type="button" class="btn btn-primary rounded-pill px-4" onclick="triggerProblemSearch()">Find Fix</button>
            </div>

            <!-- Instant Search Results Dropdown -->
            <div id="problem-search-results" class="shadow-lg rounded-3 border mt-2 bg-white position-absolute w-100 d-none" style="z-index: 1050; max-height: 380px; overflow-y: auto;"></div>

            <!-- Problem Quick Shortcut Pills -->
            <div class="d-flex flex-wrap align-items-center gap-1 mt-3">
                <span class="text-muted small fw-bold me-1">Try a common issue:</span>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" onclick="quickFillSearch('My chicken is too dry')">My chicken is too dry</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" onclick="quickFillSearch('My rice is sticky')">My rice is sticky</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" onclick="quickFillSearch('My soup is too salty')">My soup is too salty</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" onclick="quickFillSearch('My cake did not rise')">My cake sank in the middle</button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" onclick="quickFillSearch('How long does cooked chicken last')">Cooked chicken shelf life</button>
            </div>
        </div>
    </div>
</section>
@endsection

@section('content')
<div class="tt-tips-hub">
    <!-- Browse by Category Section -->
    <section class="mb-5 pt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="text-primary text-uppercase fw-bold small tracking-wider">Browse by Topic</span>
                <h2 class="h3 mb-0" style="color: var(--tt-ink);">Explore Cooking Categories</h2>
            </div>
        </div>

        <div class="row g-3">
            @foreach ($categories as $cat)
                <div class="col-lg-4 col-md-6">
                    <a href="{{ route('cooking-tips.category', $cat->slug) }}" class="card h-100 p-3 text-decoration-none shadow-sm border border-light-subtle rounded-3 transition-hover">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px; background: #fff3e6; color: var(--tt-primary-d); font-size: 1.25rem;">
                                <i class="fas {{ $cat->icon }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h3 class="h5 mb-1 text-dark fw-bold">{{ $cat->name }}</h3>
                                <p class="text-muted small mb-2" style="line-height: 1.45;">{{ $cat->description }}</p>
                                <span class="badge bg-light text-primary border">{{ $cat->published_guides_count }} {{ Str::plural('guide', $cat->published_guides_count) }} &raquo;</span>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Featured / Popular Guides -->
    @if ($featuredGuides->isNotEmpty())
        <section class="mb-5 pb-2">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <div>
                    <span class="text-warning text-darken text-uppercase fw-bold small"><i class="fas fa-star mr-1"></i> Essential Reads</span>
                    <h2 class="h3 mb-0" style="color: var(--tt-ink);">Most Popular Cooking Guides</h2>
                </div>
            </div>

            <div class="row g-4">
                @foreach ($featuredGuides as $guide)
                    <div class="col-lg-6">
                        <article class="card h-100 shadow-sm border-0 rounded-3 overflow-hidden">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-light text-primary border">{{ $guide->category?->name }}</span>
                                    <span class="text-muted small"><i class="far fa-clock mr-1"></i>{{ $guide->reading_time_minutes }} min read</span>
                                </div>
                                <h3 class="h5 fw-bold mb-2">
                                    <a href="{{ route('cooking-tips.show', ['category' => $guide->category?->slug ?? 'general', 'guide' => $guide->slug]) }}" class="text-dark text-decoration-none">
                                        {{ $guide->title }}
                                    </a>
                                </h3>
                                <p class="text-muted small mb-3 flex-grow-1">{{ $guide->excerpt }}</p>

                                <!-- Quick answer teaser -->
                                @if (!empty($guide->quick_answer))
                                    <div class="p-2 mb-3 bg-light rounded border-start border-4 border-warning small text-secondary">
                                        <strong>Quick takeaway:</strong> {{ Str::limit($guide->quick_answer, 110) }}
                                    </div>
                                @endif

                                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                    <small class="text-muted">By {{ $guide->author_name }}</small>
                                    <a href="{{ route('cooking-tips.show', ['category' => $guide->category?->slug ?? 'general', 'guide' => $guide->slug]) }}" class="fw-bold text-primary small text-decoration-none">
                                        Read full guide &raquo;
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Latest Guides Listing -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
            <div>
                <span class="text-primary text-uppercase fw-bold small">Fresh Advice</span>
                <h2 class="h3 mb-0" style="color: var(--tt-ink);">All Cooking Guides &amp; Tips</h2>
            </div>
        </div>

        <div class="row g-4">
            @foreach ($latestGuides as $guide)
                <div class="col-lg-4 col-md-6">
                    <article class="card h-100 shadow-sm border rounded-3 p-3 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-light text-dark border">{{ $guide->category?->name }}</span>
                            <span class="text-muted small"><i class="far fa-clock mr-1"></i>{{ $guide->reading_time_minutes }} min</span>
                        </div>
                        <h3 class="h6 fw-bold mb-2">
                            <a href="{{ route('cooking-tips.show', ['category' => $guide->category?->slug ?? 'general', 'guide' => $guide->slug]) }}" class="text-dark text-decoration-none">
                                {{ $guide->title }}
                            </a>
                        </h3>
                        <p class="text-muted small mb-3 flex-grow-1" style="line-height: 1.5;">{{ Str::limit($guide->excerpt, 120) }}</p>
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center mt-auto">
                            <span class="text-muted small">{{ $guide->published_at ? $guide->published_at->format('M d, Y') : '' }}</span>
                            <a href="{{ route('cooking-tips.show', ['category' => $guide->category?->slug ?? 'general', 'guide' => $guide->slug]) }}" class="small fw-bold text-primary text-decoration-none">Read &raquo;</a>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $latestGuides->links() }}
        </div>
    </section>

    <!-- Connected TopicTrail Tools Banner -->
    <section class="p-4 p-md-5 rounded-4 border bg-light my-5">
        <div class="text-center mb-4">
            <span class="tt-eyebrow">Practical Culinary Solutions</span>
            <h2 class="h3 mb-2" style="color: var(--tt-ink);">Put Knowledge Into Practice with Our Free Tools</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">
                TopicTrail guides pair seamlessly with interactive kitchen calculators and planning tools.
            </p>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-3 rounded-3 text-center">
                    <div class="fs-2 text-primary mb-2"><i class="fas fa-balance-scale"></i></div>
                    <h3 class="h6 fw-bold mb-1"><a href="{{ route('tools.converter') }}" class="text-dark text-decoration-none">Cooking Converter</a></h3>
                    <p class="small text-muted mb-0">Convert cups to grams, tablespoons, fluid ounces and oven temperatures with one click.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-3 rounded-3 text-center">
                    <div class="fs-2 text-success mb-2"><i class="fas fa-exchange-alt"></i></div>
                    <h3 class="h6 fw-bold mb-1"><a href="{{ route('tools.ingredient-substitution') }}" class="text-dark text-decoration-none">Ingredient Substitutions</a></h3>
                    <p class="small text-muted mb-0">Missing butter, eggs, or buttermilk? Find kitchen-tested alternatives with scaled ratios.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-3 rounded-3 text-center">
                    <div class="fs-2 text-warning mb-2"><i class="fas fa-calculator"></i></div>
                    <h3 class="h6 fw-bold mb-1"><a href="{{ route('tools.nutrition-calculator') }}" class="text-dark text-decoration-none">Nutrition Calculator</a></h3>
                    <p class="small text-muted mb-0">Estimate calories, protein, carbs and fat per serving from verified USDA &amp; Ciqual data.</p>
                </div>
            </div>
        </div>
    </section>
</div>

@push('head')
<style>
.transition-hover {
    transition: transform .18s ease-in-out, box-shadow .18s ease-in-out;
}
.transition-hover:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(27, 42, 65, .12) !important;
}
.tt-problem-result-item {
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.tt-problem-result-item:hover {
    background-color: #f8fafc;
}
</style>
@endpush

@push('scripts')
<script>
let searchTimer = null;

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('problem-search-input');
    const resultsBox = document.getElementById('problem-search-results');

    searchInput.addEventListener('input', function (e) {
        clearTimeout(searchTimer);
        const q = e.target.value.trim();
        if (q.length < 2) {
            resultsBox.classList.add('d-none');
            resultsBox.innerHTML = '';
            return;
        }

        searchTimer = setTimeout(() => {
            fetchProblemGuides(q);
        }, 220);
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.classList.add('d-none');
        }
    });
});

function quickFillSearch(term) {
    const input = document.getElementById('problem-search-input');
    input.value = term;
    input.focus();
    fetchProblemGuides(term);
}

function triggerProblemSearch() {
    const input = document.getElementById('problem-search-input');
    const term = input.value.trim();
    if (term.length >= 2) {
        fetchProblemGuides(term);
    }
}

function fetchProblemGuides(query) {
    const resultsBox = document.getElementById('problem-search-results');
    resultsBox.innerHTML = '<div class="p-3 text-center text-muted"><i class="fas fa-spinner fa-spin mr-1"></i> Finding the best cooking guide...</div>';
    resultsBox.classList.remove('d-none');

    fetch(`{{ route('cooking-tips.search') }}?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (!data.results || data.results.length === 0) {
                resultsBox.innerHTML = `
                    <div class="p-4 text-center">
                        <i class="fas fa-search fa-2x text-muted mb-2"></i>
                        <p class="mb-1 text-dark fw-bold">No direct guide found for "${escapeHtml(query)}"</p>
                        <p class="small text-muted mb-0">Browse our 6 categories above or search for a general technique like "chicken", "rice", "salt" or "bake".</p>
                    </div>
                `;
                return;
            }

            let html = '<div class="p-2 border-bottom bg-light small fw-bold text-uppercase text-secondary"><i class="fas fa-check-circle text-success mr-1"></i> Recommended Cooking Guides:</div>';
            data.results.forEach((g, i) => {
                const isTopMatch = (i === 0);
                const badge = isTopMatch ? '<span class="badge bg-warning text-dark me-2">Suggested Guide</span>' : '';
                html += `
                    <a href="${g.url}" class="tt-problem-result-item d-block p-3 border-bottom text-decoration-none text-dark">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-light text-primary border">${escapeHtml(g.category)}</span>
                            <span class="small text-muted"><i class="far fa-clock mr-1"></i>${g.reading_time} min read</span>
                        </div>
                        <div class="fw-bold mb-1">${badge}${escapeHtml(g.title)}</div>
                        <p class="small text-muted mb-0">${escapeHtml(g.quick_answer || g.excerpt)}</p>
                    </a>
                `;
            });
            resultsBox.innerHTML = html;
        })
        .catch(err => {
            resultsBox.innerHTML = '<div class="p-3 text-danger text-center">Failed to load suggestions. Please try again.</div>';
        });
}

function escapeHtml(string) {
    const str = String(string || '');
    return str.replace(/[&<>"']/g, function (m) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[m];
    });
}
</script>
@endpush
@endsection
