<header class="navbar navbar-horizontal navbar-expand navbar-dark flex-row align-items-md-center bg-gradient-primary py-2">
    <div class="d-none d-sm-block ml-auto">
        <ul class="navbar-nav ct-navbar-nav flex-row align-items-center">
            <li class="nav-item dropdown d-none">
                <a class="btn-link text-white dropdown-toggle mr-sm-3" href="#" id="ct-versions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></a>
                <div class="dropdown-menu" aria-labelledby="ct-versions">
                    <a class="dropdown-item active" href="../../docs//">Latest - </a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-icon" href="https://www.facebook.com/CombineSell" target="_blank">
                    <i class="fab fa-facebook-square"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-icon" href="https://twitter.com/combinesell" target="_blank">
                    <i class="fab fa-twitter"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-icon" href="https://www.instagram.com/combinesell/" target="_blank">
                    <i class="fab fa-instagram"></i>
                </a>
            </li>
        </ul>
    </div>
    <a href="{{ route('register') }}" class="btn btn-neutral btn-icon">
        <span class="btn-inner--icon">
            <i class="fas fa-check mr-2"></i>
        </span>
        <span class="nav-link-inner--text">Try for FREE</span>
    </a>
    <button class="navbar-toggler ct-search-docs-toggle d-block d-md-none ml-auto ml-sm-0" type="button" data-toggle="collapse" data-target="#ct-docs-nav" aria-controls="ct-docs-nav" aria-expanded="false" aria-label="Toggle docs navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
</header>

<div class="header bg-white pb-6" id="article_header">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center">
                <div class="col-lg-10">
                    <div class="form-group pb-0 mb-0 mt-2">
                        <select class="select2" name="state" v-on:change="selectArticle()">

                        </select>
                    </div>
                </div>
            </div>
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h1 class="px-4 border-left border-primary" id="{{ Str::slug($article->title) }}">{{ $article->title }}</h1>
                </div>
                <div class="col-lg-6 col-5 text-right">
                    @yield('header-right')
                </div>
            </div>
            <div class="row mb-0 pb-0">
                <div class="col-lg-12">
                    @foreach($article->article_tags as $tags)
                        <span class="badge badge-pill bg-gradient-cyan mr-2 mb-2 text-white">#{{ $tags->name }}</span>
                    @endforeach
                </div>
            </div>
            @yield('header-body')
        </div>
    </div>
</div>

@section('script')
<script>
    $(document).ready(function(){
        $select = $('.select2').select2({
                ajax: {
                    url: "/web/articles",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                      return {
                        category: params.term,
                      };
                    },
                    processResults: function(data)
                    {
                        var items = data.response.items;

                        return {
                            results: $.map(items, function (item) {
                                return {
                                    title: item.title,
                                    id: item.id,
                                    category: item.category.name
                                }
                            })
                        }
                    }
                },
                placeholder: 'Search Article',
                templateResult: formatSelect,
                templateSelection: formatSelection
            });

            function formatSelect (select) {

            if (select.loading) {
                return select.title;
            }

              var $container = $(
                "<div class='select2-result clearfix'>" +
                  "<div class='select2-result__meta'>" +
                    "<div class='select2-result__category h3'></div>" +
                    "<div class='select2-result__title'></div>" +
                  "</div>" +
                "</div>"
              );

                $container.find(".select2-result__category").text(select.category);
                $container.find(".select2-result__title").text(select.title);

                return $container;
            }

            function formatSelection (select) {
                return select.title || "Search Article";
            }

            $select.on("change", function (e) {
                window.location.href="/knowledgebase/" + e.target.value;
            });
    });
</script>
@endsection
