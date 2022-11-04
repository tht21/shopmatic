<nav class="navbar navbar-top navbar-expand navbar-dark bg-primary border-bottom">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Search form -->
            {{--<form class="navbar-search navbar-search-light form-inline mr-sm-3" id="navbar-search-main">
                <div class="form-group mb-0">
                    <div class="input-group input-group-alternative input-group-merge">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input class="form-control" placeholder="Search" type="text">
                    </div>
                </div>
                <button type="button" class="close" data-action="search-close" data-target="#navbar-search-main" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </form>--}}
            <!-- Navbar links -->
            <ul class="navbar-nav align-items-center ml-md-auto">
                <li class="nav-item d-xl-none">
                    <!-- Sidenav toggler -->
                    <div class="pr-3 sidenav-toggler sidenav-toggler-dark" data-action="sidenav-pin" data-target="#sidenav-main">
                        <div class="sidenav-toggler-inner">
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                        </div>
                    </div>
                </li>
                <li class="nav-item d-sm-none">
                    <a class="nav-link" href="#" data-action="search-show" data-target="#navbar-search-main">
                        <i class="ni ni-zoom-split-in"></i>
                    </a>
                </li>
{{--                <li class="nav-item dropdown">--}}
{{--                    <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">--}}
{{--                        <i class="ni ni-bell-55"></i>--}}
{{--                    </a>--}}
{{--                    <div class="dropdown-menu dropdown-menu-xl dropdown-menu-right py-0 overflow-hidden">--}}
{{--                        <!-- Dropdown header -->--}}
{{--                        <div class="px-3 py-3">--}}
{{--                            <h6 class="text-sm text-muted m-0 font-weight-normal">You have <strong class="text-primary">@{{ notifications.unread_count }}</strong> new notifications.</h6>--}}
{{--                        </div>--}}
{{--                        <!-- List group -->--}}
{{--                        <div class="list-group list-group-flush">--}}
{{--                            <a v-for="notification in notifications.data" href="#" class="list-group-item list-group-item-action">--}}
{{--                                <div class="row align-items-center">--}}
{{--                                    <div class="col-auto">--}}
{{--                                        <!-- Avatar -->--}}
{{--                                        <img alt="Image placeholder" src="" class="avatar rounded-circle">--}}
{{--                                    </div>--}}
{{--                                    <div class="col ml--2">--}}
{{--                                        <div class="d-flex justify-content-between align-items-center">--}}
{{--                                            <div>--}}
{{--                                                <h4 class="mb-0 text-sm">John Snow</h4>--}}
{{--                                            </div>--}}
{{--                                            <div class="text-right text-muted">--}}
{{--                                                <small>2 hrs ago</small>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        <p class="text-sm mb-0">Let's meet at Starbucks at 11:30. Wdyt?</p>--}}
{{--                                    </div>--}}
{{--                                </div>--}}
{{--                            </a>--}}
{{--                        </div>--}}
{{--                        <!-- View all -->--}}
{{--                        <a href="{{ route('dashboard.notifications.index') }}" class="dropdown-item text-center text-primary py-3">View all</a>--}}
{{--                    </div>--}}
{{--                </li>--}}
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="ni ni-ungroup"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-dark bg-default dropdown-menu-right">
                        <div class="row shortcuts px-4">
                            <a href="{{ route('dashboard.orders.index') }}" class="col-4 shortcut-item">
                                <span class="shortcut-media avatar rounded-circle bg-gradient-success">
                                    <i class="ni ni-basket"></i>
                                </span>
                                <small>Orders</small>
                            </a>
                            <a href="{{ route('dashboard.products.index') }}" class="col-4 shortcut-item">
                                <span class="shortcut-media avatar rounded-circle bg-gradient-cyan">
                                    <i class="ni ni-archive-2"></i>
                                </span>
                                <small>Products</small>
                            </a>
                            <a href="{{ route('dashboard.accounts.index') }}" class="col-4 shortcut-item">
                                <span class="shortcut-media avatar rounded-circle bg-gradient-purple">
                                    <i class="ni ni-ui-04"></i>
                                </span>
                                <small>Accounts</small>
                            </a>
                            <a href="{{ route('dashboard.reports.index', 'retail') }}" class="col-4 shortcut-item">
                                <span class="shortcut-media avatar rounded-circle bg-gradient-red">
                                    <i class="ni ni-books"></i>
                                </span>
                                <small>Reports</small>
                            </a>
                            <a href="{{ route('dashboard.shops.index') }}" class="col-4 shortcut-item">
                                <span class="shortcut-media avatar rounded-circle bg-gradient-blue">
                                    <i class="ni ni-basket"></i>
                                </span>
                                <small>Shops</small>
                            </a>
                            <a href="{{ route('dashboard.tickets.index') }}" class="col-4 shortcut-item">
                                <span class="shortcut-media avatar rounded-circle bg-gradient-info">
                                    <i class="ni ni-spaceship"></i>
                                </span>
                                <small>Support</small>
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
            <ul class="navbar-nav align-items-center ml-auto ml-md-0">
                <li class="nav-item dropdown">
                    <a class="nav-link pr-0" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="media align-items-center">
                            <span class="avatar avatar-sm rounded-circle">
                                <img alt="Profile picture" src="{{ asset('images/user.png') }}">
                            </span>
                            <div class="media-body ml-2 d-none d-lg-block">
                                <span class="mb-0 text-sm  font-weight-bold">{{ Auth::user()->name }}</span>
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" style="z-index: 1030">
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome!</h6>
                            @if(Session::has('shop'))
                                <span> Merchant Id:{{Session::get('shop')->id}} </span>
                            @endif
                        </div>
                        <!-- <a href="{{ route('dashboard.billing.index') }}" class="dropdown-item">
                            <i class="ni ni-money-coins"></i>
                            <span>Billing</span>
                       </a>
                       <a href="{{ route('dashboard.subscriptions.index') }}" class="dropdown-item">
                            <i class="fas fa-file-invoice"></i>
                            <span>Subscription</span>
                       </a> -->
{{--                        <a href="#!" class="dropdown-item">--}}
{{--                            <i class="ni ni-settings-gear-65"></i>--}}
{{--                            <span>Settings</span>--}}
{{--                        </a>--}}
{{--                        <a href="#!" class="dropdown-item">--}}
{{--                            <i class="ni ni-calendar-grid-58"></i>--}}
{{--                            <span>Activity</span>--}}
{{--                        </a>--}}
{{--                        <a href="#!" class="dropdown-item">--}}
{{--                            <i class="ni ni-support-16"></i>--}}
{{--                            <span>Support</span>--}}
{{--                        </a>--}}
                        <div class="dropdown-divider"></div>
                        <a href="#!"onclick="document.getElementById('logout-form').submit();" class="dropdown-item">
                            <i class="ni ni-user-run"></i>
                            <span>Logout</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="header bg-primary pb-6">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    {{ Breadcrumbs::render() }}
                </div>
                <div class="col-lg-6 col-5 text-right">
                    @yield('header-right')
                </div>
            </div>
            @yield('header-body')
        </div>

        @include('flash::message')
    </div>
</div>
