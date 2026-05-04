@php
    $isActive = request()->routeIs($activeRoute)
        || ($activeRoute === 'home' && request()->routeIs('dashboard'));
@endphp
<li class="nav-item">
    <a href="{{ $route }}" class="nav-link {{ $isActive ? 'active' : '' }}">
        @if(($variant ?? 'sidebar') === 'navbar')
            <i class="{{ $icon }} me-1"></i><span>{{ $text }}</span>
        @else
            <i class="nav-icon {{ $icon }}"></i>
            <p>{{ $text }}</p>
        @endif
    </a>
</li>
