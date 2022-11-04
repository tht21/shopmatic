<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="author" content="{{ config('app.name') }}">
    <title>{{ ($breadcrumb = Breadcrumbs::current()) ? strip_tags($breadcrumb->title) . ' | ' : '' }} {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <link href="{{ mix('/css/app.css') }}" rel="stylesheet">

    @yield('head')

    @if (config('app.env') === 'production')
    <!-- Google Analytics -->
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

        ga('create', 'UA-121127372-1', 'auto');
        ga('send', 'pageview');

        @if (Auth::user())
            ga('set', {'user_id': '{{ Auth::user()->id }}'}); // Set the user ID using signed-in user_id.
        @endif
    </script>
    <script async src='https://www.google-analytics.com/analytics.js'></script>
    <!-- End Google Analytics -->

    <!-- Global site tag (gtag.js) - Google Ads: 650067007 -->
    <!-- Adwords requested by Shopmatic marketing team -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-650067007"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'AW-650067007');
    </script>

    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-KPMMCZQ');</script>
    <!-- End Google Tag Manager -->

    @endif

</head>

<body class="bg-default">
@if (config('app.env') === 'production')
    <!-- Google Tag Manager (noscript) -->
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KPMMCZQ" height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
@endif

<!-- End Google Tag Manager (noscript) -->
<div id="app" class="main-content">

    @include('partials.header')

    <div class="main-content">
        @yield('content')

        <footer class="p-5">
            <div class="container">
                <div class="row align-items-top text-center">
                    <div class="col-12 col-sm-6 col-md-4 text-sm-left">
                        <h3 class="text-light">Useful Links</h3>
                        <nav class="nav flex-column">
                            <a class="nav-link text-muted" href="{{ route('index') }}">Home</a>
                            <!-- <a class="nav-link text-muted" href="">Pricing</a> -->
                            <a class="nav-link text-muted" href="{{ route('integrations.index') }}">Integrations</a>
                            <a class="nav-link text-muted" href="{{ route('contact.index') }}">Contact</a>
                            {{--<a class="nav-link text-muted" href="{{ route('articles.index') }}">Knowledgebase</a>--}}
                        </nav>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4 mt-5 mt-sm-0 text-sm-left">
                        <h3 class="text-light">Company</h3>
                        <nav class="nav flex-column">
                            <a class="nav-link text-muted" href="{{ route('about-us.index') }}">About Us</a>
                            <a class="nav-link text-muted" href="{{ route('privacy.index') }}">Privacy Policy</a>
                            <a class="nav-link text-muted" href="{{ route('terms.index') }}">Terms of Service</a>
                        </nav>
                    </div>

                    <div class="col-12 col-md-3 ml-auto text-lg-left mt-4 mt-lg-0">
                        <h3 class="text-muted">Follow Us</h3>
                        <p class="lead">
                            <a href="https://twitter.com/CombineSell" class="mx-2"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                            <a href="https://www.facebook.com/CombineSell/" class="mx-2"><i class="fab fa-facebook" aria-hidden="true"></i></a>
                            <a href="https://www.instagram.com/combinesell/" class="mx-2"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                        </p>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col text-center">
                        © {{ date('Y') }} <a href="{{ route('index') }}" class="font-weight-bold ml-1">{{ config('app.name') }}</a> Pte. Ltd. All Rights Reserved
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<script src="{{ mix('/js/app.js') }}"></script>

<script type="text/javascript">
    @foreach (session('flash_notification', collect())->toArray() as $message)
        @if (!$message['overlay'])
        swal({
            text: '{{ $message['message'] }}',
            type: '{{ $message['level'] }}',
            buttonsStyling: false,
            confirmButtonClass: 'btn btn-success'
        });
        @endif
    @endforeach

    <?php session()->forget('flash_notification') ?>

    $(document).ready(function() {
        $(document).scroll(function () {
            var $nav = $("nav.fixed-top");
            $nav.toggleClass('scrolled', $(this).scrollTop() > ($nav.height() - 20));
        });
        // This is if they refresh while scrolled
        var $nav = $("nav.fixed-top");
        $nav.toggleClass('scrolled', $(this).scrollTop() > $nav.height());
    });
</script>

@yield('footer')
</body>

</html>
