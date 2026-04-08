@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap4.min.css" />
@endpush

@section('content')
    <x-app-breadcrumb title="View Logs"
        :breadcrumbs="[
            ['name' => 'Home',      'url' => route('dashboard')],
            ['name' => 'Documents', 'url' => route('documents.index')],
            ['name' => 'View Logs', 'url' => '#'],
        ]" />

    <div class="app-content">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-0">
                        <i class="bi bi-eye me-2 text-primary"></i>
                        View Logs &mdash; <span class="text-muted fw-normal">{{ $document->title }}</span>
                    </h5>
                </div>
                <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Documents
                </a>
            </div>

            @php
                $columns = [
                    ['data' => 'email',       'title' => 'Email'],
                    ['data' => 'verified_at', 'title' => 'Verified At'],
                    ['data' => 'viewed_at',   'title' => 'Viewed At'],
                    ['data' => 'ip_address',  'title' => 'IP Address'],
                    ['data' => 'user_agent',  'title' => 'User Agent'],
                ];
            @endphp

            <x-data-table id="logs-table" :columns="$columns" :extraOptions="['title' => 'View Logs']" />

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function () {
            const columns = [
                { data: 'email',       name: 'email' },
                { data: 'verified_at', name: 'verified_at' },
                { data: 'viewed_at',   name: 'viewed_at' },
                { data: 'ip_address',  name: 'ip_address' },
                { data: 'user_agent',  name: 'user_agent' },
            ];

            initializeDataTable('logs-table', '{{ route('documents.view-logs', $document) }}', columns, {
                order: [[2, 'desc']],
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
            });
        });
    </script>
@endpush
