@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('select2.min.css') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('app/css/files.css') }}">
@endpush

@section('content')
    <x-app-breadcrumb title="Folders" :breadcrumbs="[['name' => 'Home', 'url' => route('home')], ['name' => 'Folders', 'url' => route('folder.index')]]" />

    <div class="app-content">
        <div class="container-fluid">

            <!-- Modal for Folder Creation -->
            <div class="modal fade " id="createFolderModal" tabindex="-1" aria-labelledby="createFolderModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="createFolderForm">
                            <div class="modal-header">
                                <h5 class="modal-title" id="createFolderModalLabel">Create New Folder</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label for="folderName">Folder Name</label>
                                    <input type="text" id="newfolderName" name="name" class="form-control" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="role">Grant Access to Role</label>
                                    <select name="role[]" id="role-select" class="select2" multiple="multiple"
                                        style="width: 100%;">
                                        @foreach ($roleArr as $role)
                                            <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label for="parentFolder">Parent Folder</label>
                                    <select id="parentFolder" name="parent_id" class="form-select">
                                        <option value="">None (Root Folder)</option>
                                        @foreach ($allFolderArr as $folder)
                                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Create</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Folder Tree View -->
            <div class="tree">
                <ul>
                    <li>
                        <details>

                            <summary>
                                <i class="fa fa-folder"></i>Main
                                @if (current_user()->hasPermission('Folder', 'create'))
                                    <button class="btn btn-primary" onclick="create_folder_form()" style="margin-left: 0px;">Create Folder
                                    </button>
                                @endif

                            </summary>
                            <ul>
                                @foreach ($folderArr as $folder)
                                    <x-folder :folder="$folder" />
                                @endforeach
                            </ul>
                        </details>
                    </li>
                </ul>
            </div>

            <!-- Create/Edit Folder Modal -->
            <div class="modal fade" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="folderForm" action="">
                            <div class="modal-header">
                                <h5 class="modal-title" id="folderModalLabel">Create/Edit Folder</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="folderName" class="form-label">Folder Name</label>
                                    <input type="text" class="form-control" id="folderName" name="name" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="role">Grant Access to Role</label>
                                    <select name="role[]" id="role-select-edit" class="select2" multiple="multiple"
                                        style="width: 100%;">
                                        @foreach ($roleArr as $role)
                                            <option value="{{ $role->id }}">{{ $role->role_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('select2.full.min.js') }}"></script>

    <script>
        $('#role-select').select2({
            dropdownParent: $('#createFolderModal')
        });
        $('#role-select-edit').select2({
            dropdownParent: $('#folderModal')
        });

        // $('.select2').select2({
        //     placeholder: "Select roles",
        //     allowClear: true
        // });

        function create_folder_form(id) {
            $('#createFolderForm')[0].reset();
            $('#role-select').change();
            $('#createFolderModal').modal('show');
        }

        $(document).ready(function() {
            // Handle modal open for folder edit
            $(document).on('click', '.edit-folder', function() {
                const folderName = $(this).closest('summary').contents().filter(function() {
                    return this.nodeType === 3; // Get the text node only
                }).text().trim();
                const updateUrl = $(this).data('url');

                // Set folder data in modal
                $('#folderForm').attr('action', updateUrl)
                $('#folderName').val(folderName);
                $('#folderModalLabel').text('Edit Folder');
                $.ajax({
                    url: updateUrl,
                    type: 'GET',

                    success: function(response) {
                        $('#role-select-edit').val(response).change()
                        $('#folderModal').modal('show');
                    },
                    error: function(xhr) {
                        alert('Error: ' + (xhr.responseJSON.message ||
                            'An unexpected error occurred.'));
                    }
                });
            });

            // Submit folder form for create/edit
            $('#folderForm').on('submit', function(e) {
                e.preventDefault();

                const editUrl = $(this).attr('action');

                $.ajax({
                    url: editUrl,
                    type: 'PUT',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                            'content') // Include CSRF token
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // Reload page for simplicity
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + (xhr.responseJSON.message ||
                            'An unexpected error occurred.'));
                    }
                });
            });


            $(document).on('click', '.delete-folder', function() {
                if (confirm('Are you sure you want to delete this folder?')) {
                    const deleteUrl = $(this).data('url');

                    $.ajax({
                        url: deleteUrl, // Use the resource route format
                        type: 'DELETE', // Use DELETE method for deletion
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'), // CSRF token
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                location.reload(); // Reload page to reflect the changes
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr) {
                            alert('Error: ' + xhr.responseJSON.message ||
                                'An unexpected error occurred.');
                        }
                    });
                }
            });


            // Handle folder creation
            $('#createFolderForm').on('submit', function(e) {
                e.preventDefault();

                // Get the CSRF token value
                let csrfToken = $('meta[name="csrf-token"]').attr('content');

                $.ajax({
                    url: "{{ route('folder.store') }}", // Adjust the URL as per your route
                    type: "POST",
                    data: $(this).serialize(), // Serialize the form data
                    headers: {
                        'X-CSRF-TOKEN': csrfToken, // Include CSRF token for security
                    },
                    success: function(response) {
                        if (response.success) {
                            // Close the modal after successful folder creation
                            $('#createFolderModal').modal('hide');
                            // Optionally, reload the page or update the UI with new data
                            alert(response.message); // Display success message
                            location
                                .reload(); // Reload the page to show the new folder
                        } else {
                            alert(response.message); // Display any error messages
                        }
                    },
                    error: function(xhr) {
                        // Handle any AJAX errors
                        alert('Error: ' + xhr.responseJSON.message ||
                            'An unexpected error occurred.');
                    }
                });
            });

        });
    </script>
@endpush
