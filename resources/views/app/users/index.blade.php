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

            <!-- Bulk Action Buttons -->
            <div id="bulkActionButtons" class="d-none mb-3">
                <button type="button" class="btn btn-success" id="enableSelectedBtn">
                    <i class="fas fa-check-circle me-2"></i>  Active
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
                    data: 'select',
                    name: 'select',
                    orderable: false,
                    searchable: false,
                    width: '5%'
                },
                {
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
                                text: xhr.responseJSON?.message || 'An error occurred while deleting users.',
                            });
                        }
                    });
                }
            });
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

        // Handle select all checkbox
        $(document).on('change', '#select-all', function() {
            $('.user-checkbox').prop('checked', $(this).is(':checked'));
            updateBulkActionButtons();
        });

        // Handle individual checkbox change
        $(document).on('change', '.user-checkbox', function() {
            updateBulkActionButtons();
            
            // Update select all checkbox state
            var totalCheckboxes = $('.user-checkbox').length;
            var checkedCheckboxes = $('.user-checkbox:checked').length;
            $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        // Update bulk action buttons visibility
        function updateBulkActionButtons() {
            var checkedCount = $('.user-checkbox:checked').length;
            if (checkedCount > 0) {
                $('#bulkActionButtons').removeClass('d-none');
                $('#selectedCount').text(checkedCount + ' user(s) selected');
            } else {
                $('#bulkActionButtons').addClass('d-none');
            }
        }

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
                text: 'Are you sure you want to change status of ' + selectedIds.length + ' user(s)?',
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
                text: 'Are you sure you want to change status of ' + selectedIds.length + ' user(s)?',
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

        // Bulk update status function
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
                                text: xhr.responseJSON?.message || `Failed to ${action} 2FA for selected users.`,
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
