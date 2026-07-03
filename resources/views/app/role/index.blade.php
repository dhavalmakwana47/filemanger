@extends('app.layouts.layout')
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
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

        .role-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 0.75rem;
            flex-wrap: wrap;
        }

        .role-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .role-table-wrap .card-body {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 576px) {
            .role-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .role-toolbar .btn {
                width: 100%;
                min-height: 42px;
                font-size: 14px;
            }

            .role-table-wrap .dataTables_wrapper table {
                min-width: 920px;
            }
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Roles" :breadcrumbs="[
        ['name' => 'Home', 'url' => route('companyrole.index')],
        ['name' => 'Roles', 'url' => route('companyrole.index')],
    ]" />
    <div class="app-content">
        <div class="container-fluid">
            @if (current_user()->hasPermission('Company Role', 'create'))
                <div class="role-toolbar">
                    <div class="w-100 w-sm-auto ms-sm-auto text-end">
                        <a href="{{ route('companyrole.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Role
                        </a>
                    </div>
                </div>
            @endif

            @php
                $columns = [
                    ['data' => 'id', 'title' => 'ID'],
                    ['data' => 'name', 'title' => 'Name'],
                    ['data' => 'assign_users', 'title' => 'Assigned Users'],
                    ['data' => 'rights', 'title' => 'rights'],
                    ['data' => 'created_at', 'title' => 'Created At'],
                ];

                // Conditionally add the action column if the user has permission
                if (
                    current_user()->hasPermission('Company Role', 'update') ||
                    current_user()->hasPermission('Company Role', 'delete')
                ) {
                    $columns[] = ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false];
                }
            @endphp

            <div class="card mb-3">
                <div class="card-header fw-semibold">Permission Details</div>
                <div class="card-body">
                    <p class="mb-2">User access is controlled through folder and file permissions.</p>
                    <ul class="list-unstyled mb-2">
                        <li class="mb-1"><strong>View:</strong> User can only view the assigned files/folders.</li>
                        <li class="mb-1"><strong>Download:</strong> User can view and download the assigned files/folders.</li>
                    </ul>
                    <p class="mb-0 text-muted">Access will be available only as per the permission set by the Admin.</p>
                </div>
            </div>

            <p class="text-muted mb-2">Total Roles: <strong>{{ $totalRoles }}</strong></p>
            <div class="role-table-wrap">
                <x-data-table id="companyrole-table" :columns="$columns" :extraOptions="['title' => 'Role List']" />
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(function() {
            // Define the columns for the users table
            const columns = [{
                    data: 'id',
                    name: 'id',
                    width: '10%',
                    visible: false
                },
                {
                    data: 'role_name',
                    name: 'role_name',
                    width: '20%'
                },
                {
                    data: 'assign_users',
                    name: 'assign_users',
                    width: '20%',
                    orderable: false
                },
                {
                    data: 'rights',
                    name: 'rights',
                    width: '20%'
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    width: '10%'
                },

                @if (current_user()->hasPermission('Company Role', 'update') || current_user()->hasPermission('Company Role', 'delete'))
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        width: '15%'
                    }
                @endif
            ];

            // Call the common function to initialize the DataTable
            initializeDataTable('companyrole-table', '{{ route('companyrole.index') }}', columns, {
                searchPlaceholder: "Search...",
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                order: [[0, 'desc']]
            });
        });
    </script>
@endpush
