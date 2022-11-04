@if (count($breadcrumbs))
    <h6 class="h2 text-white d-inline-block mb-0">{{ ($breadcrumb = Breadcrumbs::current()) ? strip_tags($breadcrumb->title) : '' }}</h6>
    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
        <ol class="breadcrumb breadcrumb-links breadcrumb-dark">

            @foreach ($breadcrumbs as $breadcrumb)
                @if ($breadcrumb->url && !$loop->last)
                    <li class="breadcrumb-item"><a href="{{ $breadcrumb->url }}">{!! $breadcrumb->title !!}</a></li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">{!! $breadcrumb->title !!}</li>
                @endif

            @endforeach
        </ol>
    </nav>
@endif