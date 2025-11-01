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

            <form action="{{ route('userlog.download') }}" method="POST">
                @csrf
                <div class="row">
                    @if (auth()->user()->type == '0')
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="user-id">User</label>
                                <select class="form-control" id="user-id" name="user_id">
                                    <option value="">Select User</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    @if (auth()->user()->type != 0)
                        <input type="hidden" id="user-id" value="{{ auth()->user()->id }}">
                    @endif


                    <div class="col-md-3 mt-4">
                        <button type="submit" class="btn btn-success">Export</button>

                    </div>
                </div>

            </form>
        </div>
            <x-data-table id="company-table" :columns="[
                ['data' => 'id', 'title' => 'ID'],
                ['data' => 'created_at', 'title' => 'Date & Time'],
                ['data' => 'user_name', 'title' => 'User Name'],
                ['data' => 'action', 'title' => 'Action'],
                ['data' => 'ipaddress', 'title' => 'IP'],
            ]" />
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
                    data: 'created_at',
                    name: 'created_at'
                },
                {
                    data: 'user_name',
                    name: 'user_name'
                },
                {
                    data: 'action',
                    name: 'action'
                },
                {
                    data: 'ipaddress',
                    name: 'ipaddress'
                }
            ];

            // Call the common function to initialize the DataTable
            initializeDataTable('company-table', '{{ route('userlog.index') }}', columns, {
                searchPlaceholder: "Search...",
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
                order: [
                    [0, "desc"]
                ] // <-- Added default ordering
            });

        });
    </script>
@endpush
