@extends('app.layouts.layout')
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Custom styles */
        .dataTables_wrapper {
            width: 100%;
            margin: 0 auto;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.2em 0.5em;
            margin-left: 5px;
        }

        #user-logs-table {
            table-layout: fixed;
            width: 100% !important;
        }

        #user-logs-table th,
        #user-logs-table td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dataTables_wrapper .dataTables_length select {
            width: auto;
            display: inline-block;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }

        .filter-section .form-group {
            margin-bottom: 0;
        }

        .btn-apply {
            margin-top: 30px;
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="User Logs" :breadcrumbs="[['name' => 'Home', 'url' => route('home')], ['name' => 'User Logs', 'url' => route('userlog.index')]]" />
    <div class="app-content">


        <div class="container-fluid">
            <div class="filter-section">
                <form id="filterForm">
                    <div class="row align-items-end">
                        @if (auth()->user()->is_master_admin() || auth()->user()->is_super_admin())
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="user-id">Filter by User</label>
                                    <select class="form-control" id="user-id" name="user_id">
                                        <option value="">All Users</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"
                                                {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="from_date">From Date</label>
                                <input type="text" class="form-control datepicker" id="from_date" name="from_date"
                                    value="{{ request('from_date') }}" placeholder="Select start date">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="to_date">To Date</label>
                                <input type="text" class="form-control datepicker" id="to_date" name="to_date"
                                    value="{{ request('to_date') }}" placeholder="Select end date">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary" id="applyFilters">Apply Filters</button>
                            <button type="button" class="btn btn-secondary ml-2" id="resetFilters">Reset</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="row mb-2">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">User Logs</h4>
                                <div class="export-options d-flex align-items-center">
                                    <div class="btn-group btn-group-toggle mr-2" data-toggle="buttons">
                                        <label class="btn btn-outline-secondary active">
                                            <input type="radio" name="export_type" value="xlsx" checked> Excel
                                        </label>
                                        <label class="btn btn-outline-danger">
                                            <input type="radio" name="export_type" value="pdf"> PDF
                                        </label>
                                    </div>
                                    <form id="exportForm" action="{{ route('userlog.download') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="user_id" id="export_user_id">
                                        <input type="hidden" name="from_date" id="export_from_date">
                                        <input type="hidden" name="to_date" id="export_to_date">
                                        <input type="hidden" name="export_type" id="export_type" value="xlsx">
                                        <button type="button" id="exportButton" class="btn btn-success">
                                            <i class="fas fa-download"></i> Export
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="user-logs-table" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Date & Time</th>
                                            <th>User Name</th>
                                            <th width="5%">Action</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize date pickers
            $('.datepicker').flatpickr({
                dateFormat: 'Y-m-d',
                allowInput: true
            });

            // Initialize DataTable
            var table = $('#user-logs-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('userlog.index') }}',
                    data: function(d) {
                        d.user_id = $('#user-id').val();
                        d.from_date = $('#from_date').val();
                        d.to_date = $('#to_date').val();
                    }
                },
                scrollX: false,
                autoWidth: true,
                columnDefs: [{
                        width: '5%',
                        targets: 0
                    }, // ID column
                    {
                        width: '25%',
                        targets: 1
                    }, // Date column
                    {
                        width: '10%',
                        targets: 2
                    }, // User Name column
                    {
                        width: '50%',
                        targets: 3
                    }, // Action column
                    {
                        width: '10%',
                        targets: 4
                    }, // IP Address column
                    {
                        className: 'text-center',
                        targets: [ 3, 4]
                    } // Center align ID, Action, and IP columns
                ],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        title: '#',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        render: function(data, type, row) {
                            return moment(data).format('DD-MMM-YYYY hh:mm A');
                        }
                    },
                    {
                        data: 'user_name',
                        name: 'user.name'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'ipaddress',
                        name: 'ipaddress',
                        className: 'text-center'
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>" +
                    "<'no-print'B>",
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    ['10 rows', '25 rows', '50 rows', '100 rows', 'Show all']
                ],
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search...",
                }
            });

            // Apply filters
            $('#applyFilters').on('click', function() {
                table.ajax.reload();
            });

            // Reset filters
            $('#resetFilters').on('click', function() {
                $('#user-id').val('');
                $('#from_date').val('');
                $('#to_date').val('');
                table.ajax.reload();
            });

            // Handle enter key in date inputs
            $('.datepicker').keypress(function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    table.ajax.reload();
                }
            });

            // Handle export button click
            $('#exportButton').on('click', function() {
                // Get current filter values
                $('#export_user_id').val($('#user-id').val());
                $('#export_from_date').val($('#from_date').val());
                $('#export_to_date').val($('#to_date').val());
                $('#export_type').val($('input[name="export_type"]:checked').val());

                // Submit the form
                $('#exportForm').submit();
            });

            // Update export button text based on selection
            $('input[name="export_type"]').change(function() {
                const type = $(this).val().toUpperCase();
                $('#exportButton').html(`<i class="fas fa-download"></i> Export as ${type}`);
            });
        });
    </script>
@endpush
