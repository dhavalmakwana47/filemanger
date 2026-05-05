@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        .bookmarks-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        #bookmarksTable {
            width: 100% !important;
        }

        #bookmarksTable th,
        #bookmarksTable td {
            white-space: nowrap;
            vertical-align: middle;
        }

        #bookmarksTable td:last-child {
            min-width: 210px;
        }

        #bookmarksTable td:last-child .btn {
            margin-right: 4px;
            margin-bottom: 4px;
        }

        .bookmarks-table-wrap .dataTables_wrapper .row {
            row-gap: 8px;
        }

        @media (max-width: 576px) {
            .bookmarks-table-wrap table {
                min-width: 760px;
            }

            .card-title {
                font-size: 1rem;
            }

            .bookmarks-table-wrap .dataTables_length,
            .bookmarks-table-wrap .dataTables_filter {
                float: none !important;
                text-align: left !important;
            }

            .bookmarks-table-wrap .dataTables_filter input,
            .bookmarks-table-wrap .dataTables_length select {
                width: 100%;
                max-width: 100%;
            }

            .bookmarks-table-wrap .dataTables_info,
            .bookmarks-table-wrap .dataTables_paginate {
                float: none !important;
                text-align: left !important;
                margin-top: 8px;
            }
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="My Bookmarks" :breadcrumbs="[
        ['name' => 'Home', 'url' => route('dashboard')],
        ['name' => 'My Bookmarks', 'url' => route('bookmarks.index')],
    ]" />

    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-star text-warning me-2"></i>My Bookmarked Files
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive bookmarks-table-wrap">
                                <table id="bookmarksTable" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Size</th>
                                            <th>Date Added</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
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
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#bookmarksTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('bookmarks.index') }}",
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'size', name: 'size' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[2, 'desc']],
                pageLength: 25,
                autoWidth: false,
                responsive: false,
                scrollX: true,
                columnDefs: [
                    { targets: 0, width: '38%' },
                    { targets: 1, width: '14%' },
                    { targets: 2, width: '23%' },
                    { targets: 3, width: '25%' }
                ]
            });
        });
    </script>
@endpush