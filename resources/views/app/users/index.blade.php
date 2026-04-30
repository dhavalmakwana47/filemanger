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

        .select2-container {
            z-index: 10000 !important;
        }

        .select2-dropdown {
            z-index: 10001 !important;
        }

        .select2-container.is-invalid .select2-selection {
            border-color: #dc3545 !important;
        }

        .users-toolbar {
            gap: 12px;
        }

        .users-filter-group,
        .users-action-group {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .users-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .users-table-wrap .card {
            width: 100%;
        }

        .users-table-wrap .card-body {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .users-table-wrap .dataTables_wrapper {
            width: 100%;
        }

        @media (max-width: 576px) {
            .users-toolbar {
                flex-direction: column;
                align-items: stretch !important;
            }

            .users-filter-group,
            .users-action-group {
                width: 100%;
            }

            .users-filter-group label {
                width: 100%;
                margin-bottom: 0;
                font-weight: 600;
            }

            #statusFilter {
                width: 100% !important;
            }

            .users-action-group .btn,
            .users-filter-group .btn {
                flex: 1 1 100%;
                min-height: 42px;
                justify-content: center;
                font-size: 14px;
                white-space: normal;
            }

            .users-action-group .btn i,
            .users-filter-group .btn i {
                margin-right: 6px !important;
            }

            #bulkActionButtons {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            #bulkActionButtons .btn {
                flex: 1 1 calc(50% - 8px);
                min-height: 40px;
            }

            #bulkActionButtons #selectedCount {
                width: 100%;
                margin-left: 0 !important;
            }

            .users-table-wrap .dataTables_wrapper table {
                min-width: 860px;
            }
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
                                <button type="submit" class="btn btn-primary" id="importSubmitBtn"><i class="fas fa-save"></i> Add</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
            @if (current_user()->hasPermission('Users', 'create'))
                <div class="d-flex justify-content-between align-items-center mb-3 users-toolbar">
                    <div class="users-filter-group">
                        <label class="me-2">Filter:</label>
                        <select id="statusFilter" class="form-select form-select-sm d-inline-block" style="width: 150px;">
                            <option value="">All Users</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-success ms-2" id="activateAllBtn">
                            <i class="fas fa-check-circle me-1"></i> Active All
                        </button>
                        <button type="button" class="btn btn-sm btn-warning ms-1" id="deactivateAllBtn">
                            <i class="fas fa-times-circle me-1"></i> Inactive All
                        </button>
                    </div>
                    <div class="d-flex gap-2 users-action-group">
                        <button type="button" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal"
                            data-bs-target="#createUserModal">
                            <i class="fas fa-user-plus me-2"></i> Create User
                        </button>
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
                </div>
            @endif

            <!-- Bulk Action Buttons -->
            <div id="bulkActionButtons" class="d-none mb-3">
                <button type="button" class="btn btn-success" id="enableSelectedBtn">
                    <i class="fas fa-check-circle me-2"></i> Active
                </button>
                <button type="button" class="btn btn-warning" id="disableSelectedBtn">
                    <i class="fas fa-times-circle me-2"></i> Inactive
                </button>
                <button type="button" class="btn btn-info text-white" id="enable2FABtn">
                    <i class="fas fa-shield-alt me-2"></i> Enable 2FA
                </button>
                <button type="button" class="btn btn-secondary" id="disable2FABtn">
                    <i class="fas fa-shield-virus me-2"></i> Disable 2FA
                </button>
                <button type="button" class="btn btn-danger" id="deleteSelectedBtn">
                    <i class="fas fa-trash me-2"></i> Delete Selected
                </button>
                <span class="ms-2" id="selectedCount">0 users selected</span>
            </div>

            <!-- Create User Modal -->
            <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="createUserForm">
                            @csrf
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="createUserModalLabel"><i class="fas fa-user-plus"></i> Create
                                    User</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="user_name" class="form-label">Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="user_name" name="user_name" required>
                                    <span class="text-danger" id="error_user_name"></span>
                                </div>
                                <div class="mb-3">
                                    <label for="user_email" class="form-label">Email <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="user_email" name="user_email"
                                        required>
                                    <span class="text-danger" id="error_user_email"></span>
                                </div>
                                <div class="mb-3">
                                    <label for="role_select" class="form-label">Role</label>
                                    <select class="form-control select2-modal" name="role[]" id="role_select" multiple
                                        >
                                        @foreach ($roleArr as $role)
                                            <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-danger" id="error_role"></span>
                                </div>
                                <div class="m-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                            value="1" checked>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>
                                    Create</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @php
                $columns = [
                    ['data' => 'select', 'title' => 'select', 'orderable' => false, 'searchable' => false],
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

            <p class="text-muted mb-2">Total Users: <strong>{{ $totalUsers }}</strong></p>
            <div class="users-table-wrap">
                <x-data-table id="users-table" :columns="$columns" :extraOptions="['title' => 'Users List']" />
            </div>


        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="{{ asset('select2.full.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Handle import form submission with loader
            $('#fileForm').on('submit', function() {
                const submitBtn = $('#importSubmitBtn');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');
                
                Swal.fire({
                    title: 'Importing Users',
                    html: 'Please wait while we process your file...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            });

            console.log('Roles available:', {{ count($roleArr) }});

            // Initialize Select2 immediately
            $('#role_select').select2({
                placeholder: "Select roles",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#createUserModal')
            });

            $('#role').select2({
                placeholder: "Select roles",
                allowClear: true,
                width: '100%',
                dropdownParent: $('#UserImportModal')
            });

            // Handle create user form submission
            $('#createUserForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Form submitted');

                // Clear previous errors
                $('.text-danger').text('');
                $('.form-control, .select2-modal').removeClass('is-invalid');
                $('.select2-container').removeClass('is-invalid');

                // Frontend validation
                let hasError = false;

                const userName = $('#user_name').val().trim();
                const userEmail = $('#user_email').val().trim();
                const roles = $('#role_select').val();

                console.log('Validation:', {
                    userName,
                    userEmail,
                    roles
                });

                if (!userName) {
                    $('#error_user_name').text('Name is required');
                    $('#user_name').addClass('is-invalid');
                    hasError = true;
                }

                if (!userEmail) {
                    $('#error_user_email').text('Email is required');
                    $('#user_email').addClass('is-invalid');
                    hasError = true;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userEmail)) {
                    $('#error_user_email').text('Please enter a valid email address');
                    $('#user_email').addClass('is-invalid');
                    hasError = true;
                }

                if (hasError) {
                    console.log('Validation failed');
                    return false;
                }

                console.log('Validation passed, submitting...');

                const formData = {
                    _token: '{{ csrf_token() }}',
                    user_name: userName,
                    user_email: userEmail,
                    role: roles,
                    is_active: $('#is_active').is(':checked') ? 1 : 0
                };

                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

                $.ajax({
                    url: '{{ route('users.store') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        $('#createUserModal').modal('hide');
                        $('#createUserForm')[0].reset();
                        $('#role_select').val(null).trigger('change');

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'User created successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        $('#users-table').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.user_name) {
                                $('#error_user_name').text(errors.user_name[0]);
                                $('#user_name').addClass('is-invalid');
                            }
                            if (errors.user_email) {
                                $('#error_user_email').text(errors.user_email[0]);
                                $('#user_email').addClass('is-invalid');
                            }
                            if (errors.role) {
                                $('#error_role').text(errors.role[0]);
                                $('#role_select').next('.select2-container').addClass(
                                    'is-invalid');
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'An error occurred'
                            });
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(
                            '<i class="fas fa-save"></i> Create');
                    }
                });
            });

            // Clear error on input
            $('#user_name, #user_email').on('input', function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.text-danger').text('');
            });

            // Reset modal on close
            $('#createUserModal').on('hidden.bs.modal', function() {
                $('#createUserForm')[0].reset();
                $('#role_select').val(null).trigger('change');
                $('.text-danger').text('');
                $('.form-control').removeClass('is-invalid');
            });

            // Define the columns for the users table
            const columns = [{
                    data: 'select',
                    name: 'select',
                    orderable: false,
                    searchable: false,
                    width: '5%'
                },
                {
                    data: 'id',
                    name: 'id',
                    visible: false
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
            const table = initializeDataTable('users-table', '{{ route('users.index') }}', columns, {
                searchPlaceholder: "Search...",
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                order: [1, "desc"],
            });

            // Status filter
            $('#statusFilter').on('change', function() {
                const status = $(this).val();
                $('#users-table').DataTable().ajax.url('{{ route('users.index') }}?status=' + status).load();
            });

            // Activate all users
            $('#activateAllBtn').on('click', function() {
                Swal.fire({
                    title: 'Activate All Users?',
                    text: 'This will activate all users in the system.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, activate all!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('users.activate_all') }}",
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                $('#users-table').DataTable().ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message || 'An error occurred.',
                                });
                            }
                        });
                    }
                });
            });

            // Deactivate all users
            $('#deactivateAllBtn').on('click', function() {
                Swal.fire({
                    title: 'Deactivate All Users?',
                    text: 'This will deactivate all users in the system.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, deactivate all!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('users.deactivate_all') }}",
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                $('#users-table').DataTable().ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message || 'An error occurred.',
                                });
                            }
                        });
                    }
                });
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
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        $('#users-table').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON.message ||
                                'An error occurred while updating status.',
                        });
                    }
                });
            });

            // Handle select all checkbox
            $(document).on('change', '#select-all', function() {
                $('.user-checkbox').prop('checked', $(this).is(':checked'));
                updateBulkActionButtons();
            });

            // Handle individual checkbox change
            $(document).on('change', '.user-checkbox', function() {
                updateBulkActionButtons();

                var totalCheckboxes = $('.user-checkbox').length;
                var checkedCheckboxes = $('.user-checkbox:checked').length;
                $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
            });

            // Handle enable selected button
            $('#enableSelectedBtn').on('click', function() {
                var selectedIds = [];
                $('.user-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one user.',
                    });
                    return;
                }

                Swal.fire({
                    title: 'Enable Users?',
                    text: 'Are you sure you want to change status of ' + selectedIds.length +
                        ' user(s)?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, active them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        bulkUpdateStatus(selectedIds, 1);
                    }
                });
            });

            // Handle disable selected button
            $('#disableSelectedBtn').on('click', function() {
                var selectedIds = [];
                $('.user-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one user.',
                    });
                    return;
                }

                Swal.fire({
                    title: 'Disable Users?',
                    text: 'Are you sure you want to change status of ' + selectedIds.length +
                        ' user(s)?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, inactive them!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        bulkUpdateStatus(selectedIds, 0);
                    }
                });
            });

            // Handle enable 2FA for selected users
            $('#enable2FABtn').on('click', function() {
                const selectedIds = getSelectedUserIds();
                if (!selectedIds.length) return;

                update2FAStatus(selectedIds, true);
            });

            // Handle disable 2FA for selected users
            $('#disable2FABtn').on('click', function() {
                const selectedIds = getSelectedUserIds();
                if (!selectedIds.length) return;

                update2FAStatus(selectedIds, false);
            });

            // Handle delete selected button
            $('#deleteSelectedBtn').on('click', function() {
                var selectedIds = [];
                $('.user-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Selection',
                        text: 'Please select at least one user.',
                    });
                    return;
                }

                Swal.fire({
                    title: 'Delete Users?',
                    text: 'This will remove the selected users from this company. Continue?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('users.bulk_delete') }}",
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                user_ids: selectedIds
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                // Reset checkboxes and reload table
                                $('#select-all').prop('checked', false);
                                $('#bulkActionButtons').addClass('d-none');
                                $('#users-table').DataTable().ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message ||
                                        'An error occurred while deleting users.',
                                });
                            }
                        });
                    }
                });
            });
        });

        // Update bulk action buttons visibility (outside document ready)
        function updateBulkActionButtons() {
            var checkedCount = $('.user-checkbox:checked').length;
            if (checkedCount > 0) {
                $('#bulkActionButtons').removeClass('d-none');
                $('#selectedCount').text(checkedCount + ' user(s) selected');
            } else {
                $('#bulkActionButtons').addClass('d-none');
            }
        }

        // Function to get selected user IDs
        function getSelectedUserIds() {
            const selectedIds = [];
            $('.user-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one user.',
                });
                return [];
            }
            return selectedIds;
        }

        // Function to update 2FA status for multiple users
        function update2FAStatus(userIds, enable) {
            if (!userIds.length) return;

            const action = enable ? 'enable' : 'disable';

            Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} 2FA`,
                text: `Are you sure you want to ${action} two-factor authentication for ${userIds.length} selected user(s)?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: enable ? '#0d6efd' : '#6c757d',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action}`,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('users.bulk_2fa_update') }}",
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            user_ids: userIds,
                            enable: enable ? 1 : 0
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });

                            // Reset checkboxes and reload table
                            $('#select-all').prop('checked', false);
                            $('#bulkActionButtons').addClass('d-none');
                            $('#users-table').DataTable().ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message ||
                                    `Failed to ${action} 2FA for selected users.`,
                            });
                        }
                    });
                }
            });
        }

        function bulkUpdateStatus(userIds, isActive) {
            $.ajax({
                url: "{{ route('users.bulk_status') }}",
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    user_ids: userIds,
                    is_active: isActive
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Reset checkboxes and reload table
                    $('#select-all').prop('checked', false);
                    $('#bulkActionButtons').addClass('d-none');
                    $('#users-table').DataTable().ajax.reload(null, false);
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'An error occurred while updating status.',
                    });
                }
            });
        }
    </script>
@endpush
