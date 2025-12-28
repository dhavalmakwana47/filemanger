<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid"> <!--begin::Start Navbar Links-->
        <ul class="navbar-nav">
            <li class="nav-item"> <a id="sidebar-toggle-btn"  class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="bi bi-list"></i> </a> </li>
            <li class="nav-item d-none d-md-block"> <a href="{{ route('dashboard') }}" class="nav-link">Home</a> </li>
        </ul> <!--end::Start Navbar Links--> <!--begin::End Navbar Links-->
        <ul class="navbar-nav ms-auto"> <!--begin::Navbar Search-->
            <li class="nav-item"> <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                    <i class="bi bi-search"></i> </a>
            </li>
            <li class="nav-item">
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
            <li class="nav-item"> <a class="nav-link" href="#" data-lte-toggle="fullscreen"> <i
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
        </ul> <!--end::End Navbar Links-->
    </div> <!--end::Container-->
</nav>
