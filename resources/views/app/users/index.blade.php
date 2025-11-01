@extends('app.layouts.layout')
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" href="{{ asset('select2.min.css') }}">

    <style>
        /* Custom styles */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.2em 0.5em;
            margin-left: 5px;
        }

        .dataTables_wrapper .dataTables_length select {
            width: auto;
            display: inline-block;
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Users" :breadcrumbs="[['name' => 'Home', 'url' => route('users.index')], ['name' => 'Users', 'url' => route('users.index')]]" />
    <div class="app-content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="modal fade" id="UserImportModal" tabindex="-1" aria-labelledby="UserImportModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="fileForm"action="{{ route('users.upload') }}" method="POST"
                            enctype="multipart/form-data" method="POST">
                            @csrf

                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="UserImportModalLabel"><i class="fas fa-file-edit"></i>Add File
                                </h5>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="fileUpload" class="form-label fw-bold">
                                        <i class="fas fa-folder"></i> Upload File
                                    </label>
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="fileUpload" name="file" required
                                            multiple>
                                        <label class="input-group-text" for="fileUpload">
                                            <i class="fas fa-upload"></i> Choose File
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="role">Role</label>
                                    <select class="form-control select2" name="role[]" id="role" multiple>
                                        @foreach ($roleArr as $role)
                                            <option value="{{ $role->id }}"
                                                @if (
                                                    (is_array(old('role')) && in_array($role->id, old('role'))) ||
                                                        (isset($user) && $user->companyRoles()->where('company_role_id', $role->id)->exists())) selected @endif>
                                                {{ $role->role_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="modal-footer bg-light">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>Add</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
            @if (current_user()->hasPermission('Users', 'create'))
                <div class="d-flex justify-content-end gap-2 mb-3">
                    <a href="{{ route('users.create') }}" class="btn btn-primary d-flex align-items-center">
                        <i class="fas fa-user-plus me-2"></i> Create User
                    </a>
                    <a href="{{ route('users.export') }}" class="btn btn-warning d-flex align-items-center">
                        <i class="fas fa-file-download me-2"></i> Export
                    </a>
                    <a href="{{ route('download.sample.csv') }}" class="btn btn-success d-flex align-items-center">
                        <i class="fas fa-file-download me-2"></i> Sample CSV
                    </a>
                    <button type="button" class="btn btn-outline-primary d-flex align-items-center" data-bs-toggle="modal"
                        data-bs-target="#UserImportModal">
                        <i class="fas fa-file-upload me-2"></i> Import Users
                    </button>
                </div>
            @endif

            @php
                $columns = [
                    ['data' => 'id', 'title' => 'ID'],
                    ['data' => 'name', 'title' => 'Name'],
                    ['data' => 'email', 'title' => 'Email'],
                    ['data' => 'status', 'title' => 'Status'],

                    ['data' => 'created_at', 'title' => 'Created At'],
                ];

                // Conditionally add the action column if the user has permission
                if (
                    current_user()->hasPermission('Users', 'update') ||
                    current_user()->hasPermission('Users', 'delete')
                ) {
                    $columns[] = ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false];
                }
            @endphp

            <x-data-table id="users-table" :columns="$columns" :extraOptions="['title' => 'Users List']" />

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="{{ asset('select2.full.min.js') }}"></script>

    <script>
        $(function() {
            // Define the columns for the users table
            const columns = [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'status',
                    name: 'status',
                    width: '10%',
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                },
                @if (current_user()->hasPermission('Users', 'update') || current_user()->hasPermission('Users', 'delete'))
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                @endif
            ];

            // Call the common function to initialize the DataTable
            initializeDataTable('users-table', '{{ route('users.index') }}', columns, {
                searchPlaceholder: "Search...",
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
            });
        });

        $('.select2').select2({
            placeholder: "  Select roles",
            allowClear: true,
            width: '100%'
        });

        // Handle status toggle
        $(document).on('change', 'input[type="checkbox"][data-id]', function() {
            var userId = $(this).data('id');
            var isActive = $(this).is(':checked') ? 1 : 0;

            $.ajax({
                url: "{{ route('users.change_status') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    user_id: userId,
                    is_active: isActive
                },
                success: function(response) {
                    swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#users-table').DataTable().ajax.reload(null, false); // Reload table data without resetting pagination
                },
                error: function(xhr) {
                    swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON.message ||
                            'An error occurred while updating status.',
                    });
                }
            });
        });
    </script>
@endpush
