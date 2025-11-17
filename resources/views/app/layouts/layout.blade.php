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



    @stack('styles')
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        @include('app.layouts.navbar')
        @include('app.layouts.sidebar')
        <main class="app-main">
            @yield('content')
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
            const ndaModal = new bootstrap.Modal(document.getElementById('ndaModal'));
            const agreeCheckbox = document.getElementById('agreeCheckbox');
            const agreeButton = document.getElementById('agreeButton');

            // Show modal if NDA agreement is not signed
            @if (isset($setting) &&
                    !session('nda_agreement') &&
                    $setting->nda_content_enable &&
                    $setting->nda_content &&
                    !auth()->user()->is_master_admin() &&
                    !auth()->user()->is_super_admin())
                ndaModal.show();
            @endif

            // Enable/disable agree button based on checkbox
            agreeCheckbox.addEventListener('change', function() {
                agreeButton.disabled = !this.checked;
            });

            // Prevent closing modal when clicking outside or pressing escape
            document.getElementById('ndaModal').addEventListener('hide.bs.modal', function(e) {
                if (!@json(session('nda_agreement'))) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            });
        });
    </script>
</body>

</html>
