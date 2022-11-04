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

</head>

<body id="main-body">

    @include('dashboard.partials.sidebar')

    <div id="app" class="main-content">

        @include('dashboard.partials.header')

        <div class="container-fluid mt--6">
            {{--@include('flash::message')--}}
            @yield('content')

            @include('dashboard.partials.footer')
        </div>
    </div>

    <script src="{{ mix('/js/app.js') }}"></script>

    @yield('footer')
</body>

</html>
