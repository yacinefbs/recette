@verbatim
<!doctype html>
<html class="no-js" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    @endverbatim
@include('partials.seo')
@verbatim
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/svg+xml" href="/assets/images/favicon.svg">

    <!-- CSS
	============================================ -->

    <!-- Icon Font CSS -->
    <link rel="stylesheet" href="/assets/css/plugins/all.min.css">
    <link rel="stylesheet" href="/assets/css/plugins/flaticon.css">

    <!-- Plugins CSS -->
    <link rel="stylesheet" href="/assets/css/plugins/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/plugins/swiper-bundle.min.css">
    <link rel="stylesheet" href="/assets/css/plugins/aos.css">
    <link rel="stylesheet" href="/assets/css/plugins/animate.css">
    <link rel="stylesheet" href="/assets/css/plugins/meanmenu.css">
    <link rel="stylesheet" href="/assets/css/plugins/nice-select.css">
    <link rel="stylesheet" href="/assets/css/plugins/magnific-popup.css">

    <!-- Main Style CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/topictrail.css?v=2">


    <!--====== Use the minified version files listed below for better performance and remove the files listed above ======-->
    <!-- <link rel="stylesheet" href="/assets/css/vendor/plugins.min.css">
    <link rel="stylesheet" href="/assets/css/style.min.css"> -->

</head>

<body>
@endverbatim
@include('partials.gtm-noscript')
@verbatim

    <div class="main-wrapper">


        <!-- preloader start -->
        <div class="preloader">
            <div class="loader rubix-cube">
                <div class="layer layer-1"></div>
                <div class="layer layer-2"></div>
                <div class="layer layer-3 color-1"></div>
                <div class="layer layer-4"></div>
                <div class="layer layer-5"></div>
                <div class="layer layer-6"></div>
                <div class="layer layer-7"></div>
                <div class="layer layer-8"></div>
            </div>
        </div>
        <!-- preloader end -->


        @endverbatim
@include('partials.header')
@verbatim

        <div class="body-overlay"></div>




        @endverbatim
        <section class="tt-hero tt-hero--page">
            <div class="container">
                <span class="tt-eyebrow">{{ $category ? 'Category' : 'All recipes' }}</span>
                <h1>{{ $category ? $category['label'].' Recipes' : 'Cooking Recipes' }}</h1>
                @if ($category)<p class="tt-lead">{{ $category['intro'] }}</p>@endif
                <ul class="tt-crumbs">
                    <li><a href="/">Home</a></li>
                    @if ($category)<li><a href="/recipes">Recipes</a></li><li aria-current="page">{{ $category['label'] }}</li>@else<li aria-current="page">Recipes</li>@endif
                </ul>
            </div>
        </section>
@verbatim

        <!-- Teachers Start -->

@endverbatim
        <!-- Recipes Start -->
        <div class="section edubin-teachers-section section-padding">
            <div class="container">
                @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

                <div class="tt-chips">
                    <a class="tt-chip {{ ! $category ? 'is-active' : '' }}" href="{{ route('recipes.index') }}">All</a>
                    @foreach (\App\Support\Categories::all() as $c)
                        <a class="tt-chip {{ $category && $category['slug'] === $c['slug'] ? 'is-active' : '' }}" href="{{ route('categories.show', $c['slug']) }}">{{ $c['label'] }}</a>
                    @endforeach
                </div>

                <div class="tt-searchbar">
                    <form class="tt-search" method="GET" action="{{ route('recipes.index') }}" role="search">
                        <input type="text" name="q" value="{{ $q }}" placeholder="Search recipes..." aria-label="Search recipes">
                        <button type="submit">Search</button>
                    </form>
                    @if (app()->environment('local'))
                        <form method="POST" action="{{ route('recipes.import') }}">
                            @csrf
                            <input type="hidden" name="q" value="{{ $q }}">
                            <button type="submit" class="tt-btn tt-btn--ghost" style="border-color:#f0e6da;cursor:pointer">Fetch recipes{{ $q !== '' ? ' “'.$q.'”' : '' }}</button>
                        </form>
                    @endif
                </div>

                <div class="teachers-grid-warp">
                    <div class="row">
                        @forelse ($recipes as $recipe)
                            <div class="col-lg-3 col-md-6">
                                <div class="single-teachers text-center">
                                    <div class="image">
                                        <a href="{{ route('recipes.show', $recipe) }}"><img src="{{ $recipe->image }}" alt="{{ $recipe->name }}" loading="lazy" width="300" height="230" style="height:230px;object-fit:cover;width:100%"></a>
                                    </div>
                                    <div class="cont">
                                        <a href="{{ route('recipes.show', $recipe) }}"><h6 class="name">{{ $recipe->name }}</h6></a>
                                        <p>{{ $recipe->total_time_minutes ? $recipe->total_time_minutes.' min' : ($recipe->num_servings ? $recipe->num_servings.' portions' : '') }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center"><p>No recipes yet. Click “Fetch recipes”.</p></div>
                        @endforelse
                    </div>
                </div>

                @if ($category)
                    <div class="tt-note mt-5"><strong>Tip:</strong> {{ $category['tip'] }} <a href="{{ route('meal-planner.index') }}">Plan a full week of meals &raquo;</a></div>
                @endif
                @if ($recipes->hasPages())
                    <div class="tt-pager">
                        @if ($recipes->previousPageUrl())<a class="tt-chip" href="{{ $recipes->previousPageUrl() }}">&laquo; Previous</a>@endif
                        <span>Page {{ $recipes->currentPage() }} / {{ $recipes->lastPage() }}</span>
                        @if ($recipes->nextPageUrl())<a class="tt-chip" href="{{ $recipes->nextPageUrl() }}">Next &raquo;</a>@endif
                    </div>
                @endif
            </div>
        </div>
        <!-- Recipes End -->
@verbatim





        @endverbatim
@include('partials.footer')
@verbatim

        <!-- back to top start -->
        <div class="progress-wrap">
            <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
                <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
            </svg>
        </div>
        <!-- back to top end -->

    </div>

    <!-- JS
    ============================================ -->
    <script src="/assets/js/vendor/jquery-1.12.4.min.js"></script>
    <script src="/assets/js/vendor/modernizr-3.11.2.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="/assets/js/plugins/popper.min.js"></script>
    <script src="/assets/js/plugins/bootstrap.min.js"></script>

    <!-- Plugins JS -->
    <script src="/assets/js/plugins/meanmenu.js"></script>
    <script src="/assets/js/plugins/swiper-bundle.min.js"></script>
    <script src="/assets/js/plugins/aos.js"></script>
    <script src="/assets/js/plugins/wow.js"></script>
    <script src="/assets/js/plugins/waypoints.min.js"></script>
    <script src="/assets/js/plugins/back-to-top.js"></script>
    <script src="/assets/js/plugins/jquery.counterup.min.js"></script>
    <script src="/assets/js/plugins/jquery.nice-select.min.js"></script>
    <script src="/assets/js/plugins/jquery.magnific-popup.min.js"></script>


    <!--====== Use the minified version files listed below for better performance and remove the files listed above ======-->


    <!-- Main JS -->
    <script src="/assets/js/main.js"></script>

</body>

</html>
@endverbatim
