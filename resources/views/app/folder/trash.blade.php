@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap4.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.4.0/css/select.dataTables.min.css" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.2em 0.5em;
            margin-left: 5px;
        }
        .dataTables_wrapper .dataTables_length select {
            width: auto;
            display: inline-block;
        }

        .trash-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 0.75rem;
        }

        .trash-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .trash-table-wrap .card-body {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        @media (max-width: 576px) {
            .trash-toolbar .btn {
                width: 100%;
                min-height: 42px;
                font-size: 14px;
            }

            .trash-table-wrap .dataTables_wrapper table {
                min-width: 860px;
            }
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Trash Bin" :breadcrumbs="[['name' => 'Home', 'url' => route('home')], ['name' => 'File Manager', 'url' => route('dashboard')]]" />
    <div class="app-content">
        <div class="container-fluid">
            <div class="trash-toolbar">
                <div class="w-100 text-end">
                    <a href="{{ route('folder.index') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to File Manager
                    </a>
                    <button id="bulk-delete-btn" class="btn btn-danger" disabled>
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </div>
            </div>
            <div class="trash-table-wrap">
                <x-data-table id="trash-table" :columns="[
                    ['data' => 'select', 'title' => 'select', 'orderable' => false, 'searchable' => false],
                    ['data' => 'id', 'title' => 'ID'],
                    ['data' => 'name', 'title' => 'Name'],
                    ['data' => 'type', 'title' => 'Type'],
                    ['data' => 'created_at', 'title' => 'Created At'],
                    ['data' => 'action', 'title' => 'Action', 'orderable' => false, 'searchable' => false],
                ]" :extraOptions="['title' => 'Trashed Items']" />
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/select/1.4.0/js/dataTables.select.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
            // Define the columns for the trash table
            const columns = [
                {
                    data: 'select',
                    name: 'select',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return '<input type="checkbox" class="select-item" data-id="' + row.id + '" data-type="' + row.type + '">';
                    }
                },
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name', width: '20%' },
                { data: 'type', name: 'type' },
                { data: 'created_at', name: 'created_at', width: '10%' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ];

            // Initialize the DataTable with select functionality
            const table = $('#trash-table').DataTable({
                ajax: '{{ route('filemanager.trash.data') }}',
                columns: columns,
                select: {
                    style: 'multi',
                    selector: 'td.select-checkbox input'
                },
                searchPlaceholder: "Search trashed items...",
                dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>'
            });

            // Handle select all checkbox
            $('#select-all').on('click', function() {
                const rows = table.rows({ search: 'applied' }).nodes();
                const checked = this.checked;
                $('input.select-item', rows).prop('checked', checked);
                updateBulkDeleteButton();
            });

            // Handle individual checkbox clicks
            $('#trash-table').on('change', '.select-item', updateBulkDeleteButton);

            // Update bulk delete button state
            function updateBulkDeleteButton() {
                const selected = $('.select-item:checked').length;
                $('#bulk-delete-btn').prop('disabled', selected === 0);
            }

            // Handle bulk delete
            $('#bulk-delete-btn').on('click', function() {
                const selectedItems = $('.select-item:checked').map(function() {
                    return { id: $(this).data('id'), type: $(this).data('type') };
                }).get();

                if (selectedItems.length === 0) {
                    Swal.fire('No items selected', 'Please select at least one item to delete.', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'This action will permanently delete the selected File/Folder.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete them!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait while items are being deleted.',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        
                        $.ajax({
                            url: '{{ route('filemanager.trash.bulkDelete') }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                items: selectedItems
                            },
                            success: function(response) {
                                Swal.fire('Deleted!', 'Selected File/Folder have been permanently deleted.', 'success');
                                table.ajax.reload(function() {
                                    // Uncheck all checkboxes after reload
                                    $('.select-item').prop('checked', false);
                                    $('#select-all').prop('checked', false); // Uncheck select all
                                    updateBulkDeleteButton();
                                }, false);
                            },
                            error: function(xhr) {
                                Swal.fire('Error!', 'An error occurred: ' + xhr.responseJSON.error, 'error');
                            }
                        });
                    }
                });
            });

            function confirmDelete(event, form) {
                event.preventDefault();
                Swal.fire({
                    title: "Are you sure?",
                    text: "This action will permanently delete the File/Folder.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete it!",
                    cancelButtonText: "Cancel",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait while the item is being deleted.',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        form.submit();
                    }
                });
                return false;
            }

            function confirmRestore(event, form) {
                event.preventDefault();
                Swal.fire({
                    title: "Are you sure?",
                    text: "This action will restore the File/Folder.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, restore it!",
                    cancelButtonText: "Cancel",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Restoring...',
                            text: 'Please wait while the item is being restored.',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        form.submit();
                    }
                });
                return false;
            }
        });
    </script>
@endpush