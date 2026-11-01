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




        <!-- Page Banner Start -->
        <div class="section page-banner-section" style="background-image: url(/assets/images/bg/banner-bg.jpeg)">
            <div class="container">
                <div class="page-banner-wrap">
                    <div class="row">
                        <div class="col-lg-12">
                            <!-- Page Banner Content Start -->
                            <div class="page-banner text-center">
                                <div class="section-title text-center">

@endverbatim
                                    <h1 class="title">{{ $recipe['name'] }}</h1>
@verbatim
                                </div>
                                <ul class="breadcrumb justify-content-center">
                                    <li class="breadcrumb-item"><a href="/">Home</a></li><li class="breadcrumb-item"><a href="/recipes">Recipes</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">
@endverbatim
{{ $recipe['name'] }}
@verbatim
                                    </li>
                                </ul>
                            </div>
                            <!-- Page Banner Content End -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Page Banner End -->

        <!-- Teachers Start -->

@endverbatim
        <div class="section section-padding">
            <div class="container">
                <div class="row">
                    <div class="col-lg-5">
                        <img src="{{ $recipe['image'] }}" alt="{{ $recipe['name'] }}" class="img-fluid mb-4" width="600" height="400" fetchpriority="high">
                    </div>
                    <div class="col-lg-7">
                        
                        @if (!empty($recipe['description']))<p>{!! strip_tags($recipe['description']) !!}</p>@endif
                        @if (!empty($recipe['num_servings']))<p><strong>Servings:</strong> {{ $recipe['num_servings'] }}</p>@endif
                        @if (!empty($recipe['total_time_minutes']))<p><strong>Time:</strong> {{ $recipe['total_time_minutes'] }} min</p>@endif
                        @if (!empty($recipe['sections']))
                            <h2 class="h5 mt-4">Ingredients</h2>
                            @foreach ($recipe['sections'] as $section)
                                @if (!empty($section['name']))<h6>{{ $section['name'] }}</h6>@endif
                                <ul>
                                    @foreach ($section['components'] ?? [] as $c)<li>{{ $c['raw_text'] ?? '' }}</li>@endforeach
                                </ul>
                            @endforeach
                        @endif
                        @if (!empty($recipe['instructions']))
                            <h2 class="h5 mt-4">Instructions</h2>
                            <ol>
                                @foreach ($recipe['instructions'] as $step)<li id="step-{{ $loop->iteration }}">{{ $step['display_text'] ?? '' }}</li>@endforeach
                            </ol>
                        @endif
                        <p class="mt-4 mb-0"><small class="text-muted">Photo and recipe data provided via Tasty. Adjust seasoning and cooking times to your taste and oven.</small></p>
                        @if ($category)
                            <div class="alert alert-light border mt-4"><strong>{{ $category['label'] }} tip:</strong> {{ $category['tip'] }}
                                <a href="{{ route('categories.show', $category['slug']) }}">More {{ strtolower($category['label']) }} recipes &raquo;</a></div>
                        @endif
                        <a href="{{ route('recipes.index') }}" class="btn btn-outline-primary mt-3">&laquo; Back to recipes</a>
                    </div>
                </div>
                @if ($related->isNotEmpty())
                    <h2 class="h4 mt-5 mb-4">You might also like</h2>
                    <div class="row">
                        @foreach ($related as $r)
                            <div class="col-lg-3 col-md-6 mb-4">
                                <a href="{{ route('recipes.show', $r) }}"><img src="{{ $r->image }}" alt="{{ $r->name }}" loading="lazy" width="300" height="200" style="height:200px;width:100%;object-fit:cover" class="mb-2"></a>
                                <h3 class="h6"><a href="{{ route('recipes.show', $r) }}">{{ $r->name }}</a></h3>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
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
