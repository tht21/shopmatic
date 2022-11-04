<nav id="navbar-main" class="navbar navbar-horizontal fixed-top navbar-main navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ route('index') }}">
            <img src="{{ asset('images/logo-white.png') }}"  alt="CombineSell Logo">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-collapse" aria-controls="navbar-collapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-collapse navbar-custom-collapse collapse" id="navbar-collapse">
            <div class="navbar-collapse-header">
                <div class="row">
                    <div class="col-6 collapse-brand">
                        <a href="{{ route('index') }}">
                            <img src="{{ asset('images/logo.png') }}"  alt="CombineSell Logo">
                        </a>
                    </div>
                    <div class="col-6 collapse-close">
                        <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbar-collapse" aria-controls="navbar-collapse" aria-expanded="false" aria-label="Toggle navigation">
                            <span></span>
                            <span></span>
                        </button>
                    </div>
                </div>
            </div>
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a href="{{ route('about-us.index') }}" class="nav-link">
                        <span class="nav-link-inner--text">About</span>
                    </a>
                </li>
                <!-- <li class="nav-item">
                    <a href="#" class="nav-link">
                        <span class="nav-link-inner--text">Pricing</span>
                    </a>
                </li> -->
                <li class="nav-item">
                    <a href="{{ route('enterprise.index') }}" class="nav-link">
                        <span class="nav-link-inner--text">Enterprise</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('contact.index') }}" class="nav-link">
                        <span class="nav-link-inner--text">Contact</span>
                    </a>
                </li>
            </ul>
            <hr class="d-lg-none" />
            <ul class="navbar-nav align-items-lg-center ml-lg-auto">
                <li class="nav-item">
                    @if (Auth::guest())
                    <a class="nav-link" href="https://app.combinesell.com/login" alt="Login">
                        <span class="nav-link-inner--text">Login</span>
                    </a>
                    @else 
                    <a href="#!" onclick="document.getElementById('logout-form').submit();" class="nav-link">
                        <span class="nav-link-inner--text">Logout</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    @endif
                </li>
                <li class="nav-item d-none d-lg-block ml-lg-4">
                    <a href="{{ route('end-to-end.index') }}" class="btn btn-neutral btn-icon">
                        <span class="btn-inner--icon">
                            <i class="fas fa-check mr-2"></i>
                        </span>
                        <span class="nav-link-inner--text">Request Free Consultation</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
