<nav class="sidenav navbar navbar-vertical fixed-left navbar-expand-xs navbar-light bg-white" id="sidenav-main">
    <div class="scrollbar-inner">
        <div class="sidenav-header d-flex align-items-center">
            <a class="navbar-brand" href="{{ route('dashboard.index') }}">
                <img src="{{ asset('images/logo.png') }}" class="navbar-brand-img" alt="CombineSell Logo">
            </a>
            <div class="ml-auto">
                <div class="sidenav-toggler d-none d-xl-block" data-action="sidenav-unpin" data-target="#sidenav-main">
                    <div class="sidenav-toggler-inner">
                        <i class="sidenav-toggler-line"></i>
                        <i class="sidenav-toggler-line"></i>
                        <i class="sidenav-toggler-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="navbar-inner">
            <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                @php $article_categories = \App\Models\Article::select('article_categories_id')->with('category')->groupBy('article_categories_id')->get(); @endphp
                @foreach($article_categories as $category)
                <div class="navbar-heading p-0 text-muted h6">{{ $category->category->name }}</div>

                <hr class="my-1">

                <ul class="navbar-nav mb-2">
                    @php $articles = \App\Models\Article::select(['id', 'title'])->where('article_categories_id', $category->article_categories_id)->get(); @endphp
                    @foreach($articles as $article)
                    <li class="nav-item">
                        <a class="nav-link {{ Request::url() == route('articles.show', $article->id) ? 'active' : '' }}" href="{{ route('articles.show', $article->id) }}">
                            <span class="nav-link-text">{{ $article->title }}</span>
                        </a>
                    </li>
                    @endforeach
                </ul>
                @endforeach
            </div>
        </div>
    </div>
</nav>
