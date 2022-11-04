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
                        <a class="nav-link {{ Request::routeIs('admin.index') ? 'active' : '' }}" href="{{ route('admin.index') }}">
                            <i class="ni ni-shop text-orange"></i>
                            <span class="nav-link-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                            <i class="ni ni-single-02 text-green"></i>
                            <span class="nav-link-text">Users</span>
                        </a>
                    </li>
                </ul>

                <!-- Divider -->
                <hr class="my-3">
                <!-- Heading -->
                <h6 class="navbar-heading p-0 text-muted">Support</h6>
                <!-- Navigation -->
                <ul class="navbar-nav mb-md-3">
                    <li class="nav-item">
                        <a class="nav-link" href="#navbar-tickets" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-components">
                            <i class="ni ni-headphones text-primary"></i>
                            <span class="nav-link-text">Tickets</span>
                        </a>
                        <div class="collapse" id="navbar-tickets">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('admin.tickets.index') }}" class="nav-link">View All</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.tickets.category.index') }}" class="nav-link">Ticket Categories</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#navbar-articles" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-components">
                            <i class="ni ni-paper-diploma text-pink"></i>
                            <span class="nav-link-text">Knowledgebase</span>
                        </a>
                        <div class="collapse" id="navbar-articles">
                            <ul class="nav nav-sm flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('admin.articles.index') }}" class="nav-link">View All</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('admin.articles.category.index') }}" class="nav-link">Article Categories</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
