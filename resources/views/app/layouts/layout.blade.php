@php
    if (get_active_company()) {
        $setting = \App\Models\Setting::where('company_id', get_active_company())->first();
    } else {
        $setting = null;
    }
@endphp
<!DOCTYPE html>
<html lang="en"> <!--begin::Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>File Manager</title><!--begin::Primary Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('manage.png') }}">
    <meta name="title" content="AdminLTE v4 | Dashboard">
    <meta name="author" content="ColorlibHQ">
    <meta name="description"
        content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS.">
    <meta name="keywords"
        content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css"
        integrity="sha256-Qsx5lrStHZyR9REqhUF8iQt73X06c8LGIUPzpOhwRrI=" crossorigin="anonymous">

    <link rel="stylesheet" href="{{ asset('app/css/adminlte.css') }}">
    <!-- Include SweetAlert CSS & JS (if not already included) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Modern Company Selector Styles */
.company-selector {
    margin: 0 15px;
}

.company-form {
    margin: 0;
}

.company-dropdown-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 25px;
    padding: 8px 16px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: all 0.3s ease;
    min-width: 200px;
}

.company-dropdown-wrapper:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.company-icon {
    color: white;
    font-size: 16px;
    margin-right: 8px;
    opacity: 0.9;
}

.company-select {
    background: transparent;
    border: none;
    color: white;
    font-weight: 500;
    font-size: 14px;
    flex: 1;
    outline: none;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.company-select option {
    background: #fff;
    color: #333;
    padding: 10px;
}

.company-select option:checked {
    background: #667eea;
    color: white;
}

.dropdown-arrow {
    color: white;
    font-size: 12px;
    margin-left: 8px;
    opacity: 0.8;
    transition: transform 0.3s ease;
}

.company-dropdown-wrapper:hover .dropdown-arrow {
    transform: rotate(180deg);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .company-dropdown-wrapper {
        min-width: 150px;
        padding: 6px 12px;
    }
    
    .company-select {
        font-size: 13px;
    }
}

/* Focus states */
.company-select:focus {
    outline: 2px solid rgba(255, 255, 255, 0.3);
    outline-offset: 2px;
}

/* Animation for selection change */
.company-dropdown-wrapper.changing {
    animation: pulse 0.6s ease-in-out;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Custom Dropdown Styles */
.custom-company-dropdown {
    position: relative;
    display: inline-block;
    max-width: min(320px, 32vw);
}

.custom-dropdown-selected {
    display: flex;
    align-items: center;
    background: #28a745;
    border-radius: 8px;
    padding: 10px 20px;
    margin: 0;
    box-shadow: 0 2px 4px rgba(40,167,69,0.2);
    transition: all 0.3s ease;
    cursor: pointer;
    min-width: 180px;
    max-width: 100%;
    color: white;
    font-size: 16px;
    font-weight: 500;
}

.custom-dropdown-selected #selected-company {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.custom-dropdown-selected:hover {
    background: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40,167,69,0.3);
}

.custom-dropdown-selected i {
    color: white;
    font-size: 14px;
    margin-right: 8px;
}

.custom-dropdown-options {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #28a745;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(40,167,69,0.3);
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
    display: none;
    scrollbar-width: thin;
    scrollbar-color: #28a745 #f1f1f1;
}

.custom-dropdown-options::-webkit-scrollbar {
    width: 6px;
}

.custom-dropdown-options::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.custom-dropdown-options::-webkit-scrollbar-thumb {
    background: #28a745;
    border-radius: 3px;
}

.custom-dropdown-options::-webkit-scrollbar-thumb:hover {
    background: #218838;
}

.custom-dropdown-option {
    padding: 12px 20px;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease;
    border-bottom: 1px solid #e8f5e8;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #155724;
    font-size: 15px;
}

.custom-dropdown-option:hover {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.custom-dropdown-option:last-child {
    border-bottom: none;
    border-radius: 0 0 8px 8px;
}

.custom-dropdown-option:first-child {
    border-radius: 8px 8px 0 0;
}

.custom-dropdown-option.selected {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    font-weight: 600;
}

/* Mobile navbar readability/fallback for AdminLTE + Bootstrap collapse */
.app-header .navbar-toggler {
    border-color: rgba(0, 0, 0, 0.2);
}

@media (min-width: 992px) {
    #topNavMenu {
        flex-wrap: nowrap;
        min-width: 0;
        gap: 0.5rem;
    }

    #topNavMenu .top-menu-nav {
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
        min-width: 0;
        scrollbar-width: thin;
        scrollbar-gutter: stable;
    }

    #topNavMenu .top-menu-nav .nav-link {
        padding-left: 0.6rem;
        padding-right: 0.6rem;
    }

    #topNavMenu .top-menu-actions {
        flex-wrap: nowrap;
        margin-left: auto;
    }

    #topNavMenu .top-menu-actions > .nav-item {
        flex: 0 0 auto;
    }
}

@media (max-width: 991.98px) {
    .app-header .navbar-brand span {
        font-size: 16px;
    }

    #mobileNavDrawer .nav-link {
        border-radius: 8px;
        padding: 10px 12px;
        color: #212529;
    }

    #mobileNavDrawer .nav-link.active {
        background-color: #0d6efd;
        color: #fff;
    }

    #mobileNavDrawer .form-select {
        min-height: 38px;
    }
}
</style>

    @stack('styles')
</head>

<body class="layout-fixed bg-body-tertiary">
    <div class="app-wrapper">
        @include('app.layouts.navbar')
        <main class="app-main">
            @yield('content')
            @yield('modals')
        </main>
        @include('app.layouts.footer')
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.3.0/browser/overlayscrollbars.browser.es6.min.js" integrity="sha256-H2VM7BKda+v2Z4+DRy69uknwxjyDRhszjXFhsL4gD3w=" crossorigin="anonymous"></script> <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)--> --}}
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha256-whL0tQWoY1Ku1iskqPFvmZ+CHsvmRWx/PIoEvIeWh4I=" crossorigin="anonymous"></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        integrity="sha256-YMa+wAM6QkVyz999odX7lPRxkoYAan8suedu4k2Zur8=" crossorigin="anonymous"></script> <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
    <script src="{{ asset('app/custom/js/datatable.js') }}"></script>
    <script src="{{ asset('app/js/adminlte.js') }}"></script>
    @stack('scripts')

    @if (isset($setting) && $setting->nda_content_enable && $setting->nda_content)
        <x-nda-modal :ndaContent="$setting->nda_content" />
    @endif

    {{-- ✅ SweetAlert for Success --}}
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: "{{ session('success') }}",
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ndaModalEl = document.getElementById('ndaModal');
            if (!ndaModalEl) return;

            const ndaModal = new bootstrap.Modal(ndaModalEl);
            const agreeCheckbox = document.getElementById('agreeCheckbox');
            const agreeButton = document.getElementById('agreeButton');

            @if (isset($setting) &&
                    !session('nda_agreement') &&
                    $setting->nda_content_enable &&
                    $setting->nda_content &&
                    !auth()->user()->is_master_admin() &&
                    !auth()->user()->is_super_admin())
                ndaModal.show();
            @endif

            if (agreeCheckbox) {
                agreeCheckbox.addEventListener('change', function() {
                    agreeButton.disabled = !this.checked;
                });
            }

            ndaModalEl.addEventListener('hide.bs.modal', function(e) {
                if (!@json(session('nda_agreement'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });
    </script>

    <script>
        function toggleDropdown() {
            const options = document.getElementById('company-options');
            options.style.display = options.style.display === 'block' ? 'none' : 'block';
        }

        function selectCompany(companyId, companyName) {
            document.getElementById('selected-company').textContent = companyName;
            document.getElementById('hidden-company-select').value = companyId;
            document.getElementById('company-options').style.display = 'none';
            
            // Update selected state
            document.querySelectorAll('.custom-dropdown-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.target.classList.add('selected');
            
            // Submit form
            document.getElementById('company-form').submit();
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.custom-company-dropdown');
            if (!dropdown.contains(event.target)) {
                document.getElementById('company-options').style.display = 'none';
            }
        });
    </script>
</body>

</html>
