<!-- Sidenav -->
<nav class="sidenav navbar navbar-vertical fixed-left navbar-expand-xs navbar-light bg-white" id="sidenav-main">
    <div class="scrollbar-inner">
        <!-- Brand -->
        <div class="sidenav-header d-flex align-items-center">
            <a class="navbar-brand" href="{{ route('dashboard.index') }}">
                <img src="{{ asset('images/logo.png') }}" class="navbar-brand-img" alt="CombineSell Logo">
            </a>
            <div class="ml-auto">
                <!-- Sidenav toggler -->
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
            <!-- Collapse -->
            <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                <!-- Nav items -->
                <ul class="navbar-nav">

                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('dashboard.index') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">
                            <i class="ni ni-shop text-orange"></i>
                            <span class="nav-link-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#navbar-orders" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-maps">
                            <i class="ni ni-cart text-green"></i>
                            <span class="nav-link-text">Orders</span>
                        </a>
                        <div class="collapse" id="navbar-orders">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.orders.index') }}" class="nav-link">All Orders</a>
                                </li>
                                {{--<li class="nav-item">
                                    <a href="{{ route('dashboard.orders.pickup') }}" class="nav-link">Pickup List</a>
                                </li>--}}
                            </ul>
                        </div>
                        <div class="collapse" id="navbar-orders">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.orders.bulk') }}" class="nav-link">Bulk Orders</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#navbar-products" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-maps">
                            <i class="ni ni-archive-2 text-primary"></i>
                            <span class="nav-link-text">Products</span>
                        </a>
                        <div class="collapse" id="navbar-products">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.products.index') }}" class="nav-link">All Products</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.products.export') }}" class="nav-link">Export</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.products.import') }}" class="nav-link">Import</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.products.bulk') }}" class="nav-link">Bulk Products</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.products.alerts.index') }}" class="nav-link">Alerts &nbsp;
                                        @if (session('shop') && $count = session('shop')->unreadAlerts()->count())
                                            <span class="badge badge-danger">{{ $count }}</span>
                                        @endif
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#navbar-inventory" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-maps">
                            <i class="ni ni-building text-danger"></i>
                            <span class="nav-link-text">Inventory</span>
                        </a>
                        <div class="collapse" id="navbar-inventory">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.inventory.index') }}" class="nav-link">All Inventory</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.inventory.composite.index') }}" class="nav-link">Composite</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.inventory.update.index') }}" class="nav-link">Bulk Update</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#navbar-integrations" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-components">
                            <i class="ni ni-ui-04 text-info"></i>
                            <span class="nav-link-text">Accounts</span>
                        </a>
                        <div class="collapse" id="navbar-integrations">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.accounts.index') }}" class="nav-link">View All</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.accounts.create') }}" class="nav-link">Add New</a>
                                </li>
                                <!-- Not for initial launch, enable when we are launching for woocommerce, prestashop -->
                                <!-- <li class="nav-item">
                                    <a href="#navbar-multilevel" class="nav-link" data-toggle="collapse" role="button" aria-expanded="true" aria-controls="navbar-multilevel">Categories</a>
                                    <div class="collapse show" id="navbar-multilevel" style="">
                                        <ul class="nav nav-sm flex-column">
                                            <li class="nav-item">
                                                <a href="{{ route('dashboard.account.categories.index') }}" class="nav-link">View All</a>
                                            </li>
                                            <li class="nav-item">
                                                <a href="{{ route('dashboard.account.categories.create') }}" class="nav-link">Add New</a>
                                            </li>
                                        </ul>
                                    </div>
                                </li> -->
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#navbar-shop" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-components">
                            <i class="ni ni-shop text-black"></i>
                            <span class="nav-link-text">Shops</span>
                        </a>
                        <div class="collapse" id="navbar-shop">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.shops.index') }}" class="nav-link">View All</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    @if (config('app.env') == 'staging')
                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('dashboard.reports.*') ? 'active' : '' }}" href="#" data-toggle="collapse" role="button" aria-expanded="{{ Request::routeIs('dashboard.reports.*') ? 'true' : 'false' }}" data-target="#navbar-report">
                            <i class="ni ni-single-copy-04 text-warning"></i>
                            <span class="nav-link-text">Reporting</span>
                        </a>
                        <div class="collapse {{ Request::routeIs('dashboard.reports.*') ? 'show' : '' }}" id="navbar-report">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a class="nav-link {{ (Request::routeIs('dashboard.reports.index') && request()->keyword == 'retail') ? 'active' : '' }}" href="{{ route('dashboard.reports.index', 'retail') }}">Retail Dashboard</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ (Request::routeIs('dashboard.reports.index') && request()->keyword == 'sales') ? 'active' : ''  }}" href="{{ route('dashboard.reports.index', 'sales') }}">Sales Report</a>
                                </li>
                                <!-- <li class="nav-item">
                                    <a class="nav-link {{ (Request::routeIs('dashboard.reports.index') && request()->keyword == 'inventory') ? 'active' : '' }}" href="{{ route('dashboard.reports.index', 'inventory') }}">Inventory Reports</a>
                                </li> -->
                            </ul>
                        </div>
                    </li>
                    @endif
                </ul>
                <!-- Divider -->
                <hr class="my-3">
                <!-- Heading -->
                <h6 class="navbar-heading p-0 text-muted">Support</h6>
                <!-- Navigation -->
                <ul class="navbar-nav mb-md-3">
                    <li class="nav-item">
                        <a class="nav-link" href="#navbar-tickets" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-components">
                            <i class="ni ni-spaceship text-primary"></i>
                            <span class="nav-link-text">Tickets</span>
                        </a>
                        <div class="collapse" id="navbar-tickets">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.tickets.index') }}" class="nav-link">View All</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link" href="https://demos.creative-tim.com/argon-dashboard/docs/foundation/colors.html" target="_blank">
                            <i class="ni ni-single-copy-04 text-pink"></i>
                            <span class="nav-link-text">Knowledgebase</span>
                        </a>
                    </li> -->
                </ul>
            </div>
        </div>
    </div>
</nav>
