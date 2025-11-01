    
    <li class="nav-item">
        <a href="{{ $route }}" class="nav-link {{ request()->routeIs($activeRoute) ? 'active' : '' }}">
            <i class="nav-icon {{ $icon }}"></i>
            <p>{{ $text }}</p>
        </a>
    </li>
