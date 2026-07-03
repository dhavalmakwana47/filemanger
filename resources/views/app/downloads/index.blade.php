@extends('app.layouts.layout')
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <style>
        .downloads-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .downloads-table-wrap .card-body {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 576px) {
            .downloads-table-wrap .dataTables_wrapper table {
                min-width: 780px;
            }
        }
    </style>
@endpush

@section('content')
<x-app-breadcrumb title="My Downloads" :breadcrumbs="[
    ['name' => 'Home', 'url' => route('dashboard')],
    ['name' => 'My Downloads', 'url' => route('downloads.index')],
]" />

<div class="app-content">
    <div class="container-fluid">
        <div class="mb-3">
            <a href="{{ route('folder.index') }}" class="btn btn-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Folders
            </a>
        </div>
        
        @php
            $columns = [
                ['data' => 'DT_RowIndex', 'title' => '#', 'orderable' => false, 'searchable' => false],
                ['data' => 'folder_name', 'title' => 'Folder Name'],
                ['data' => 'status', 'title' => 'Status'],
                ['data' => 'created_at', 'title' => 'Created At'],
                ['data' => 'action', 'title' => 'Actions', 'orderable' => false, 'searchable' => false]
            ];
        @endphp

        <div class="card mb-3">
            <div class="card-header fw-semibold">Download Status Information</div>
            <div class="card-body">
                <p class="mb-2">Please check the current status of your ZIP/File download below:</p>
                <ul class="list-unstyled mb-2">
                    <li class="mb-1"><span class="badge bg-secondary me-2">Pending</span> Your download request has been received and the process has been started.</li>
                    <li class="mb-1"><span class="badge bg-warning text-dark me-2">Processing</span> The ZIP/File generation process is currently in progress. Please wait until the process is completed.</li>
                    <li class="mb-1"><span class="badge bg-success me-2">Completed</span> Your ZIP/File is ready for download. Click the Download button to successfully download the file.</li>
                </ul>
                <p class="mb-0 text-muted"><strong>Note:</strong> Once the status is marked as <strong>Completed</strong>, you can download the ZIP/File successfully by clicking on the download option.</p>
            </div>
        </div>

        <div class="downloads-table-wrap">
            <x-data-table id="downloads-table" :columns="$columns" :extraOptions="['title' => 'Downloads List']" />
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>

<script>
$(function() {
    const columns = [
        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
        {data: 'folder_name', name: 'folder_name'},
        {data: 'status', name: 'status'},
        {data: 'created_at', name: 'created_at'},
        {data: 'action', name: 'action', orderable: false, searchable: false}
    ];

    initializeDataTable('downloads-table', '{{ route('downloads.index') }}', columns, {
        searchPlaceholder: "Search downloads...",
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
    });
});

function deleteDownload(id) {
    if (confirm('Are you sure you want to delete this download?')) {
        $.ajax({
            url: '{{ route('downloads.destroy', '') }}/' + id,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#downloads-table').DataTable().ajax.reload();
                    alert('Download deleted successfully');
                }
            },
            error: function() {
                alert('Failed to delete download');
            }
        });
    }
}
</script>
@endpush