<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">

    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <img src="{{ asset('repository.png') }}" alt="AdminLTE Logo" class="brand-image opacity-75 shadow">
            <span class="brand-text fw-light">File Manager</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                <x-nav-item route="{{ route('dashboard') }}" activeRoute="home" icon="bi bi-speedometer"
                    text="Dashboard" module="Dashboard" permission="view" />
                    
                @if (get_active_company())
                    <x-nav-item route="{{ route('folder.index') }}" activeRoute="folder.index" icon="bi bi-folder-fill"
                        text="Folders" module="Folder" permission="view" />
                    <x-nav-item route="{{ route('users.index') }}" activeRoute="users.index" icon="bi bi-people"
                        text="Users" module="Users" permission="view" />
                @endif

                <x-nav-item route="{{ route('company.index') }}" activeRoute="company.index" icon="bi bi-building"
                    text="Company" module="Company" permission="view" />
                @if (get_active_company())
                    <x-nav-item route="{{ route('companyrole.index') }}" activeRoute="companyrole.index"
                        icon="bi bi-person-badge" text="Role" module="Company Role" permission="view" />

                    <x-nav-item route="{{ route('userlog.index') }}" activeRoute="userlog.index"
                        icon="bi bi-person-badge" text="User Log" module="Dashboard" permission="view" />
                    {{-- <x-nav-item route="{{ route('permission.index') }}" activeRoute="permission.index" icon="bi bi-gear"
                        text="Role Permisison" module="Company Permission" permission="view" /> --}}

                         <x-nav-item route="{{ route('filemanager.trash.data') }}" activeRoute="filemanager.trash.data" icon="bi bi-trash"
                        text="Trash Folders" module="Company Role" permission="view" />
                @endif

            </ul>
        </nav>
    </div>
</aside>
