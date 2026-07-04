@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <style>
        .status-badge { font-size: 0.82rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .badge-pending    { background: #fff3cd; color: #856404; border: 1px solid #ffc107; }
        .badge-processing { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }
        .badge-completed  { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .badge-failed     { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .status-legend { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; }
        .status-legend h6 { font-weight: 700; margin-bottom: 10px; color: #495057; }
        .status-legend .legend-item { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 6px; }
        .status-legend .legend-item:last-child { margin-bottom: 0; }
        .status-legend .legend-item p { margin: 0; font-size: 0.85rem; color: #6c757d; }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Downloaded Logs"
        :breadcrumbs="[
            ['name' => 'Home',            'url' => route('home')],
            ['name' => 'User Logs',       'url' => route('userlog.index')],
            ['name' => 'Downloaded Logs', 'url' => route('userlog.exports.index')],
        ]" />

    <div class="app-content">
        <div class="container-fluid">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <div class="mb-3">
                <a href="{{ route('userlog.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Logs
                </a>
            </div>

            {{-- Status legend --}}
            <div class="status-legend">
                <h6><i class="fas fa-info-circle mr-1"></i> Export Status Guide</h6>
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="legend-item">
                            <span class="status-badge badge-pending"><i class="fas fa-clock"></i> Pending</span>
                            <p>Your export request is queued and waiting to be picked up.</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="legend-item">
                            <span class="status-badge badge-processing"><i class="fas fa-spinner fa-spin"></i> Processing</span>
                            <p>The file is currently being generated. Please wait.</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="legend-item">
                            <span class="status-badge badge-completed"><i class="fas fa-check-circle"></i> Completed</span>
                            <p>File is ready. Click Download to save it. Record is removed after download.</p>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="legend-item">
                            <span class="status-badge badge-failed"><i class="fas fa-times-circle"></i> Failed</span>
                            <p>Export failed. Delete this record and try again from User Logs.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Downloaded Logs</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="exports-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Format</th>
                                    <th>Status</th>
                                    <th>Requested At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function () {
            var table = $('#exports-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: '{{ route('userlog.exports.index') }}',
                    cache: true,
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'format',      orderable: false },
                    { data: 'status',      orderable: false },
                    { data: 'created_at' },
                    { data: 'actions',     orderable: false, searchable: false, className: 'text-center' },
                ],
                order: [[3, 'desc']],
                language: { search: '_INPUT_', searchPlaceholder: 'Search...' },
                pageLength: 25,
            });
        });
    </script>
@endpush
