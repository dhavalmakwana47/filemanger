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
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Company" :breadcrumbs="[['name' => 'Home', 'url' => route('home')], ['name' => 'Comoany', 'url' => route('company.index')]]" />
    <div class="app-content">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col text-right">
                    <a href="{{ route('company.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Company
                    </a>
                </div>
            </div>
            <x-data-table id="company-table" :columns="[
                ['data' => 'id', 'title' => 'ID'],
                ['data' => 'name', 'title' => 'Name'],
                ['data' => 'admin', 'title' => 'Admin Users'],
                ['data' => 'created_at', 'title' => 'Created At'],
                ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false],
            ]" :extraOptions="['title' => 'Company List']" />
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
                    name: 'id'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'admin',
                    name: 'admin'
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ];

            // Call the common function to initialize the DataTable
            initializeDataTable('company-table', '{{ route('company.index') }}', columns, {
                searchPlaceholder: "Search...",
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
            });
        });
    </script>
@endpush
