@extends('pages.layout')

@section('hero')
<section class="tt-hero tt-hero--page">
    <div class="container text-center">
        <span class="tt-eyebrow"><i class="fas {{ $category->icon }} mr-1"></i> Cooking Category</span>
        <h1>{{ $category->name }}</h1>
        <p class="tt-lead mx-auto" style="max-width: 680px;">{{ $category->description }}</p>
        <ul class="tt-crumbs">
            <li><a href="/">Home</a></li>
            <li><a href="{{ route('cooking-tips.index') }}">Cooking Tips &amp; Guides</a></li>
            <li aria-current="page">{{ $category->name }}</li>
        </ul>
    </div>
</section>
@endsection

@section('content')
<div class="tt-category-guides-wrap py-4">
    <div class="row g-4">
        @forelse ($guides as $guide)
            <div class="col-lg-6">
                <article class="card h-100 shadow-sm border rounded-3 p-4 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-light text-primary border">{{ $category->name }}</span>
                        <span class="text-muted small"><i class="far fa-clock mr-1"></i>{{ $guide->reading_time_minutes }} min read</span>
                    </div>

                    <h2 class="h5 fw-bold mb-2">
                        <a href="{{ route('cooking-tips.show', ['category' => $category->slug, 'guide' => $guide->slug]) }}" class="text-dark text-decoration-none">
                            {{ $guide->title }}
                        </a>
                    </h2>

                    <p class="text-muted small mb-3 flex-grow-1">{{ $guide->excerpt }}</p>

                    @if (!empty($guide->quick_answer))
                        <div class="p-2 mb-3 bg-light rounded border-start border-4 border-warning small text-secondary">
                            <strong>Quick answer:</strong> {{ Str::limit($guide->quick_answer, 120) }}
                        </div>
                    @endif

                    <div class="pt-3 border-top d-flex justify-content-between align-items-center mt-auto">
                        <span class="text-muted small">Updated {{ $guide->updated_at->format('M Y') }}</span>
                        <a href="{{ route('cooking-tips.show', ['category' => $category->slug, 'guide' => $guide->slug]) }}" class="btn btn-sm btn-outline-primary">
                            Read Guide &raquo;
                        </a>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="p-5 border rounded bg-light">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <h3 class="h5">No guides published in this category yet.</h3>
                    <p class="text-muted">Our kitchen editorial team is currently preparing tested guides for this topic.</p>
                    <a href="{{ route('cooking-tips.index') }}" class="btn btn-primary mt-2">&laquo; Back to all Cooking Tips</a>
                </div>
            </div>
        @endforelse
    </div>

    @if ($guides->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $guides->links() }}
        </div>
    @endif

    <div class="mt-5 pt-4 border-top">
        <a href="{{ route('cooking-tips.index') }}" class="btn btn-outline-secondary btn-sm">&laquo; Explore all Cooking Topics &amp; Categories</a>
    </div>
</div>
@endsection
