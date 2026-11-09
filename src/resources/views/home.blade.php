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
    <link rel="stylesheet" href="/assets/css/topictrail.css?v=3">


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

        <section class="tt-hero">
            <div class="container">
                <span class="tt-eyebrow">Easy &amp; delicious recipes</span>
                <h1>What are we <span>cooking</span> today?</h1>
                <p class="tt-lead">Browse photo-rich recipes with clear ingredients and step-by-step instructions, then plan your whole week in one click.</p>
                <form class="tt-search" method="GET" action="/recipes" role="search">
                    <input type="text" name="q" placeholder="Search recipes: chicken, pasta, brownies..." aria-label="Search recipes" maxlength="100">
                    <button type="submit">Search</button>
                </form>
                <div class="tt-hero-actions">
                    <a class="tt-btn tt-btn--primary" href="/weekly-meal-planner">Weekly Meal Planner</a>
                    <a class="tt-btn tt-btn--ghost" href="/what-can-i-cook">What can I cook?</a>
                    <a class="tt-btn tt-btn--ghost" href="/tools/cooking-converter">Cooking converter</a>
                </div>
            </div>
        </section>

        <!-- Course Start -->
        <div class="section course-section course-section-05 section-padding" style="margin-top:20px">
            <div class="container">
                <div class="row">
                    <div class="col-md-7">
                        <div class="section-title2">
                            <h2 class="title color-3">Latest <span>recipes</span></h2>
                        </div> <!-- section title -->
                    </div>
                    <div class="col-md-5">
                        <div class="course-btn">
                            <a href="/recipes" class="btn">All recipes</a>
                        </div>
                    </div>
                </div> <!-- row -->
                @endverbatim
                <div class="course-content-wrap">
                    <div class="row">
                        @foreach ($recipes as $recipe)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <div class="single-course">
                                <div class="thum">
                                    <div class="image">
                                        <a href="{{ route('recipes.show', $recipe) }}"><img src="{{ $recipe->image }}" alt="{{ $recipe->name }}" loading="lazy" width="300" height="200" style="height:200px;width:100%;object-fit:cover"></a>
                                    </div>
                                </div>
                                <div class="content">
                                    <a href="{{ route('recipes.show', $recipe) }}">
                                        <h3 class="title">{{ $recipe->name }}</h3>
                                    </a>
                                </div>
                                <div class="course-teacher">
                                    <div class="course-lesson">
                                        <ul>
                                            <li><i class="fas fa-list-ol"></i> {{ count($recipe->instructions ?? []) }} steps</li>
                                        </ul>
                                    </div>
                                    <div class="price">
                                        <span>{{ $recipe->total_time_minutes ? $recipe->total_time_minutes.' min' : ($recipe->num_servings ? $recipe->num_servings.' pers.' : '') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @verbatim
        <!-- Course End -->

         <!-- Category Start -->
        <div class="section edubin-category-section-06 edubin-category-section-07 section-padding-02" style="margin-top:30px;margin-bottom:80px">
            <div class="container">
                <div class="section-title2 text-center">
                    <h2 class="title color-3">Explore by <span>category</span></h2>
                </div>
                <div class="category-wrap-03">
                    <div class="row">
@endverbatim
                        @foreach (\App\Support\Categories::all() as $c)
                        <div class="col-xl-3 col-lg-4 col-md-6">
                            <a href="{{ route('categories.show', $c['slug']) }}" class="category-item-02">
                                <div class="category-icon"><i class="fas {{ $c['icon'] }}" style="font-size:42px;color:#309255" aria-hidden="true"></i></div>
                                <div class="category-content">
                                    <h3 class="title">{{ $c['label'] }}</h3>
                                </div>
                            </a>
                        </div>
                        @endforeach
                        <div class="col-12 mt-5">
                            <div class="tt-cta">
                                <div>
                                    <h2>Plan your week in one click</h2>
                                    <p>Free Weekly Meal Planner with a ready-made grocery list.</p>
                                </div>
                                <a href="{{ route('meal-planner.index') }}" class="tt-btn tt-btn--gold">Build my meal plan &raquo;</a>
                            </div>
                        </div>
@verbatim
                    </div>
                </div>
            </div>
        </div>
        <!-- Category End -->

        
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
    @include('partials.montag')

</body>

</html>
@endverbatim
