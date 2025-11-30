@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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
                            <div class="table-responsive">
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
                ajax: '{{ route('bookmarks.index') }}',
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'size', name: 'size' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[2, 'desc']],
                pageLength: 25,
                responsive: true
            });
        });
    </script>
@endpush