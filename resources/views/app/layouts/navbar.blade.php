<nav class="app-header navbar navbar-expand-lg navbar-light bg-body border-bottom">
    <div class="container-fluid">
        <button class="btn btn-outline-secondary d-lg-none me-2" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>

        <a href="{{ route('dashboard') }}" class="navbar-brand d-flex align-items-center">
            <img src="{{ asset('repository.png') }}" alt="File Manager" style="height:30px;" class="me-2">
            <span>File Manager</span>
        </a>

        <button class="navbar-toggler d-none" type="button" data-bs-toggle="collapse" data-bs-target="#topNavMenu"
            aria-controls="topNavMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse mt-2 mt-lg-0" id="topNavMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-none d-lg-flex">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('home') || request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                </li>

                @if (get_active_company())
                    <li class="nav-item">
                        <a href="{{ route('folder.index') }}" class="nav-link {{ request()->routeIs('folder.index') ? 'active' : '' }}">
                            <i class="bi bi-folder-fill me-1"></i>Folders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">
                            <i class="bi bi-people me-1"></i>Users
                        </a>
                    </li>
                @endif

                <li class="nav-item">
                    <a href="{{ route('company.index') }}" class="nav-link {{ request()->routeIs('company.index') ? 'active' : '' }}">
                        <i class="bi bi-building me-1"></i>Company
                    </a>
                </li>

                @if (get_active_company())
                    <li class="nav-item">
                        <a href="{{ route('companyrole.index') }}" class="nav-link {{ request()->routeIs('companyrole.index') ? 'active' : '' }}">
                            <i class="bi bi-person-badge me-1"></i>Role
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('userlog.index') }}" class="nav-link {{ request()->routeIs('userlog.index') ? 'active' : '' }}">
                            <i class="bi bi-journal-text me-1"></i>User Log
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('filemanager.trash.data') }}" class="nav-link {{ request()->routeIs('filemanager.trash.data') ? 'active' : '' }}">
                            <i class="bi bi-trash me-1"></i>Trash Folders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('bookmarks.index') }}" class="nav-link {{ request()->routeIs('bookmarks.index') ? 'active' : '' }}">
                            <i class="bi bi-star-fill me-1"></i>Bookmarks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('downloads.index') }}" class="nav-link {{ request()->routeIs('downloads.index') ? 'active' : '' }}">
                            <i class="bi bi-download me-1"></i>Downloads
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                            <i class="bi bi-gear me-1"></i>Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.index') ? 'active' : '' }}">
                            <i class="bi bi-file-text me-1"></i>Documents
                        </a>
                    </li>
                @endif
            </ul>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2 flex-row">
            <li class="nav-item"> <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                    <i class="bi bi-search"></i> </a>
            </li>
            <li class="nav-item d-none d-lg-block">
                <form action="{{ route('change_company') }}" method="post" id="company-form" class="d-flex align-items-center">
                    @csrf
                    <div class="custom-company-dropdown">
                        <div class="custom-dropdown-selected" onclick="toggleDropdown()">
                            <i class="bi bi-building me-2"></i>
                            <span id="selected-company">{{ get_active_company() ? fetch_company()->where('id', get_active_company())->first()->name ?? 'Select Company' : 'Select Company' }}</span>
                            <i class="bi bi-chevron-down ms-auto"></i>
                        </div>
                        <div class="custom-dropdown-options" id="company-options">
                            @foreach (fetch_company() as $company)
                                <div class="custom-dropdown-option {{ get_active_company() == $company->id ? 'selected' : '' }}" 
                                     onclick="selectCompany('{{ $company->id }}', '{{ $company->name }}')">
                                    {{ $company->name }}
                                </div>
                            @endforeach
                        </div>
                        <select name="company_id" id="hidden-company-select" style="display: none;">
                            @foreach (fetch_company() as $company)
                                <option value="{{ $company->id }}" {{ get_active_company() == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" value="{{ Route::currentRouteName() }}" name="route">
                </form>
            </li>

            <!--begin::Messages Dropdown Menu-->
            {{-- <li class="nav-item dropdown"> <a class="nav-link" data-bs-toggle="dropdown" href="#"> <i
                        class="bi bi-chat-text"></i> <span class="navbar-badge badge text-bg-danger">3</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end"> <a href="#" class="dropdown-item">
                        <!--begin::Message-->
                        <div class="d-flex">
                            <div class="flex-shrink-0"> <img src="{{ asset('app/assets/img/user1-128x128.jpg') }}"
                                    alt="User Avatar" class="img-size-50 rounded-circle me-3"> </div>
                            <div class="flex-grow-1">
                                <h3 class="dropdown-item-title">
                                    Brad Diesel
                                    <span class="float-end fs-7 text-danger"><i class="bi bi-star-fill"></i></span>
                                </h3>
                                <p class="fs-7">Call me whenever you can...</p>
                                <p class="fs-7 text-secondary"> <i class="bi bi-clock-fill me-1"></i> 4 Hours
                                    Ago
                                </p>
                            </div>
                        </div> <!--end::Message-->
                    </a>
                    <div class="dropdown-divider"></div> <a href="#" class="dropdown-item">
                        <!--begin::Message-->
                        <div class="d-flex">
                            <div class="flex-shrink-0"> <img src="{{ asset('app/assets/img/user8-128x128.jpg') }}"
                                    alt="User Avatar" class="img-size-50 rounded-circle me-3"> </div>
                            <div class="flex-grow-1">
                                <h3 class="dropdown-item-title">
                                    John Pierce
                                    <span class="float-end fs-7 text-secondary"> <i class="bi bi-star-fill"></i>
                                    </span>
                                </h3>
                                <p class="fs-7">I got your message bro</p>
                                <p class="fs-7 text-secondary"> <i class="bi bi-clock-fill me-1"></i> 4 Hours
                                    Ago
                                </p>
                            </div>
                        </div> <!--end::Message-->
                    </a>
                    <div class="dropdown-divider"></div> <a href="#" class="dropdown-item">
                        <!--begin::Message-->
                        <div class="d-flex">
                            <div class="flex-shrink-0"> <img src="{{ asset('app/assets/img/user3-128x128.jpg') }}"
                                    alt="User Avatar" class="img-size-50 rounded-circle me-3"> </div>
                            <div class="flex-grow-1">
                                <h3 class="dropdown-item-title">
                                    Nora Silvester
                                    <span class="float-end fs-7 text-warning"> <i class="bi bi-star-fill"></i>
                                    </span>
                                </h3>
                                <p class="fs-7">The subject goes here</p>
                                <p class="fs-7 text-secondary"> <i class="bi bi-clock-fill me-1"></i> 4 Hours
                                    Ago
                                </p>
                            </div>
                        </div> <!--end::Message-->
                    </a>
                    <div class="dropdown-divider"></div> <a href="#" class="dropdown-item dropdown-footer">See All
                        Messages</a>
                </div>
            </li> --}}
            {{-- <li class="nav-item dropdown"> <a class="nav-link" data-bs-toggle="dropdown" href="#"> <i
                        class="bi bi-bell-fill"></i> <span class="navbar-badge badge text-bg-warning">15</span> </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end"> <span
                        class="dropdown-item dropdown-header">15 Notifications</span>
                    <div class="dropdown-divider"></div> <a href="#" class="dropdown-item"> <i
                            class="bi bi-envelope me-2"></i> 4 new messages
                        <span class="float-end text-secondary fs-7">3 mins</span> </a>
                    <div class="dropdown-divider"></div> <a href="#" class="dropdown-item"> <i
                            class="bi bi-people-fill me-2"></i> 8 friend requests
                        <span class="float-end text-secondary fs-7">12 hours</span> </a>
                    <div class="dropdown-divider"></div> <a href="#" class="dropdown-item"> <i
                            class="bi bi-file-earmark-fill me-2"></i> 3 new reports
                        <span class="float-end text-secondary fs-7">2 days</span> </a>
                    <div class="dropdown-divider"></div> <a href="#" class="dropdown-item dropdown-footer">
                        See All Notifications
                    </a>
                </div>
            </li>  --}}
            <li class="nav-item d-none d-lg-block"> <a class="nav-link" href="#" data-lte-toggle="fullscreen"> <i
                        data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i> <i data-lte-icon="minimize"
                        class="bi bi-fullscreen-exit" style="display: none;"></i>
                </a> </li>
            <li class="nav-item dropdown user-menu"> <a href="#" class="nav-link dropdown-toggle"
                    data-bs-toggle="dropdown"> <img src="{{ asset('app/assets/img/user2-160x160.jpg') }}"
                        class="user-image rounded-circle shadow" alt="User Image"> <span class="d-none d-md-inline">
                        {{ auth()->user()->name }}</span> </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                    <li class="user-header text-bg-primary"> <img src="{{ asset('app/assets/img/user2-160x160.jpg') }}"
                            class="rounded-circle shadow" alt="User Image">
                        <p>
                            {{ auth()->user()->name }}
                            <small>Member since {{ auth()->user()->created_at->format('M. Y') }}</small>
                        </p>
                    </li>

                    <li class="user-footer  d-flex "> <a href="{{ route('users.profile') }}"
                            class="btn btn-default btn-flat">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a type="button" href="route('logout')"
                                onclick="event.preventDefault();
                                        this.closest('form').submit();"
                                class="btn
                            btn-default btn-flat float-end">Sign out</a>

                        </form>
                    </li>
                    <!--end::Menu Footer-->
                </ul>
            </li> <!--end::User Menu Dropdown-->
            </ul>
        </div>
    </div>
</nav>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="mobileNavDrawerLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <form action="{{ route('change_company') }}" method="post" class="d-flex flex-column gap-2">
                @csrf
                <label for="mobile-company-select" class="form-label mb-0 small text-muted">Active Company</label>
                <select name="company_id" id="mobile-company-select" class="form-select form-select-sm"
                    onchange="this.form.submit()">
                    @foreach (fetch_company() as $company)
                        <option value="{{ $company->id }}" {{ get_active_company() == $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" value="{{ Route::currentRouteName() }}" name="route">
            </form>
        </div>

        <ul class="nav nav-pills flex-column gap-1">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}"
                    class="nav-link {{ request()->routeIs('home') || request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            @if (get_active_company())
                <li class="nav-item">
                    <a href="{{ route('folder.index') }}" class="nav-link {{ request()->routeIs('folder.index') ? 'active' : '' }}">
                        <i class="bi bi-folder-fill me-2"></i>Folders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i>Users
                    </a>
                </li>
            @endif
            <li class="nav-item">
                <a href="{{ route('company.index') }}" class="nav-link {{ request()->routeIs('company.index') ? 'active' : '' }}">
                    <i class="bi bi-building me-2"></i>Company
                </a>
            </li>
            @if (get_active_company())
                <li class="nav-item">
                    <a href="{{ route('companyrole.index') }}" class="nav-link {{ request()->routeIs('companyrole.index') ? 'active' : '' }}">
                        <i class="bi bi-person-badge me-2"></i>Role
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('userlog.index') }}" class="nav-link {{ request()->routeIs('userlog.index') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i>User Log
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('filemanager.trash.data') }}" class="nav-link {{ request()->routeIs('filemanager.trash.data') ? 'active' : '' }}">
                        <i class="bi bi-trash me-2"></i>Trash Folders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('bookmarks.index') }}" class="nav-link {{ request()->routeIs('bookmarks.index') ? 'active' : '' }}">
                        <i class="bi bi-star-fill me-2"></i>Bookmarks
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('downloads.index') }}" class="nav-link {{ request()->routeIs('downloads.index') ? 'active' : '' }}">
                        <i class="bi bi-download me-2"></i>Downloads
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.index') ? 'active' : '' }}">
                        <i class="bi bi-gear me-2"></i>Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('documents.index') }}" class="nav-link {{ request()->routeIs('documents.index') ? 'active' : '' }}">
                        <i class="bi bi-file-text me-2"></i>Documents
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
