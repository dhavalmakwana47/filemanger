@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('select2.min.css') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('app/css/folders.css') }}">
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/22.2.6/css/dx.light.css">
    <!-- FilePond CSS -->
    <link href="https://unpkg.com/filepond/dist/filepond.css" rel="stylesheet" />

    <!-- FilePond JS -->
    <script src="https://unpkg.com/filepond/dist/filepond.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-type@1.x.x/dist/filepond-plugin-file-validate-type.min.js">
    </script>

    <style>
        
        .modal-content {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .modal-header {
            background: #007bff;
            color: #fff;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            flex-shrink: 0;
        }

        .modal-title {
            font-size: 1.5rem;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
            max-height: 70vh;
        }

        .modal-footer {
            flex-shrink: 0;
            position: sticky;
            bottom: 0;
            background: white;
            z-index: 1000;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }

        .properties-card {
            background: #f9f9f9;
            border-radius: 6px;
            padding: 10px;
        }

        .properties-table {
            width: 100%;
            border-collapse: collapse;
        }

        .properties-table tr {
            border-bottom: 1px solid #e0e0e0;
        }

        .properties-table tr:last-child {
            border-bottom: none;
        }

        .property-label {
            padding: 8px 10px;
            font-weight: 600;
            color: #555;
            width: 30%;
            vertical-align: top;
        }

        .property-label i {
            margin-right: 8px;
            color: #007bff;
        }

        .property-value {
            padding: 8px 10px;
            color: #333;
        }

        .index-column {
            background-color: #f5f5f5;
            /* light gray */
            font-weight: bold;
            color: #333;
            text-align: center;
        }

       /* ==========================================================
   CUSTOM CHECKBOX DESIGN FOR DEVEXTREME FILEMANAGER
   FULLY FIXED + REFRESH SAFE + CLEAN VERSION
   ========================================================== */

/* Ensure checkbox column has proper space */
.dx-filemanager .dx-datagrid .dx-command-select {
    width: 40px !important;
    min-width: 40px !important;
    max-width: 40px !important;
    padding: 0 !important;
    overflow: visible !important;
    text-align: center !important;
}

/* Center checkbox inside the cell */
.dx-filemanager .dx-datagrid .dx-command-select .dx-select-checkbox {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 24px !important;
    height: 24px !important;
    margin: auto !important;
    cursor: pointer !important;
    transition: all 0.2s ease-in-out !important;
    user-select: none !important;
}

/* Checkbox base look */
.dx-filemanager .dx-select-checkbox .dx-checkbox-icon {
    width: 18px !important;
    height: 18px !important;
    border-radius: 6px !important;
    border: 2px solid #0078d4 !important;
    background-color: #ffffff !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    transition: all 0.25s ease-in-out !important;
}

/* Hover effect */
.dx-filemanager .dx-select-checkbox:hover .dx-checkbox-icon {
    border-color: #005a9e !important;
    background-color: #f3f9ff !important;
}

/* Checked state */
.dx-filemanager .dx-select-checkbox.dx-checkbox-checked .dx-checkbox-icon {
    background-color: #0078d4 !important;
    border-color: #0078d4 !important;
    box-shadow: 0 0 4px rgba(0, 120, 212, 0.6);
    position: relative;
}

/* Checkmark animation */
.dx-filemanager .dx-select-checkbox.dx-checkbox-checked .dx-checkbox-icon::after {
    content: "";
    position: absolute;
    width: 6px;
    height: 12px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    top: 0px;
    left: 5px;
    animation: checkmarkAppear 0.2s ease forwards;
}

/* Row highlighting */
.dx-filemanager .dx-data-row.dx-selection {
    background-color: #eaf4ff !important;
    transition: background 0.2s ease;
}

.dx-filemanager .dx-data-row:hover {
    background-color: #f7fbff !important;
}

/* Checkmark animation keyframes */
@keyframes checkmarkAppear {
    from {
        opacity: 0;
        transform: scale(0.8) rotate(45deg);
    }
    to {
        opacity: 1;
        transform: scale(1) rotate(45deg);
    }
}

/* ==========================================================
   DARK MODE SUPPORT
   ========================================================== */
@media (prefers-color-scheme: dark) {

    .dx-filemanager .dx-select-checkbox .dx-checkbox-icon {
        background-color: #222 !important;
        border-color: #3399ff !important;
    }

    .dx-filemanager .dx-select-checkbox.dx-checkbox-checked .dx-checkbox-icon {
        background-color: #3399ff !important;
        border-color: #3399ff !important;
    }

    .dx-filemanager .dx-data-row.dx-selection {
        background-color: #2d3748 !important;
    }

    .dx-filemanager .dx-data-row:hover {
        background-color: #1f2733 !important;
    }
}

    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Folders" :breadcrumbs="[
        ['name' => 'Home', 'url' => route('folder.index')],
        ['name' => 'Folders', 'url' => route('folder.index')],
    ]" />

    <div class="app-content">
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('filemanger.data', ['is_download' => true]) }}" class="btn btn-primary" id="downloadTreeBtn">
                <i class="fas fa-download me-2"></i>Download Tree
            </a>
        </div>
        <div class="container-fluid">
            <div class="modal fade " id="createFolderModal" tabindex="-1" aria-labelledby="createFolderModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="createFolderForm">
                            @include('app.folder.update')
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="folderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="folderForm" action="" method="POST">

                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="fileModal" tabindex="-1" aria-labelledby="fileModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="fileForm" action="{{ route('file.store') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="fileModalLabel"><i class="fas fa-file-edit"></i> Add File</h5>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <input type="file" id="file-upload" class="filepond" name="file[]" multiple />

                                @include('app.folder.filepermissions')
                                <div class="mb-3">
                                    <label for="fileIndex" class="form-label fw-bold"><i class="fas fa-folder"></i>
                                        Index</label>
                                    <input type="number" min="0" class="form-control form-control-lg" id="fileIndex"
                                        name="item_index" placeholder="Enter Index...">
                                </div>
                            </div>

                            <div class="modal-footer bg-light">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="folderUploadModalModal" tabindex="-1" aria-labelledby="folderUploadModalModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="folderuploadForm" action="{{ route('folder.uploadstructure') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="folderModalLabel"><i class="fas fa-file-edit"></i> Add File</h5>
                                <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <input type="file" id="folder-upload" class="filepond" webkitdirectory multiple />

                                @include('app.folder.filepermissions')
                                <div class="mb-3">
                                    <label for="fileIndex" class="form-label fw-bold"><i class="fas fa-folder"></i>
                                        Index</label>
                                    <input type="number" min="0" class="form-control form-control-lg"
                                        id="folderIndex" name="item_index" placeholder="Enter Index...">
                                </div>
                            </div>

                            <div class="modal-footer bg-light">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>
                                    Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="processModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="processForm">
                            <div class="modal-header">
                                <h5 class="modal-title">Assign Roles to Selected Items</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <input type="hidden" name="file_ids" id="file_ids">
                                <input type="hidden" name="folder_ids" id="folder_ids">

                                <div class="form-group mb-3">
                                    <label class="form-label fw-bold"><i class="fas fa-users"></i> Assign Permissions by
                                        Role</label>
                                    <select class="form-control select2" name="roles[]" multiple id="rolesv2">
                                        @foreach ($roleArr as $role)
                                            <option value="{{ $role->id }}"
                                                {{ isset($file) && in_array($role->id, $assignedRoles) ? 'selected' : '' }}>
                                                {{ $role->role_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- In resources/views/file_manager.blade.php, inside <body> -->
            <div class="modal fade" id="propertiesModal" tabindex="-1" aria-labelledby="propertiesModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="propertiesModalLabel">File/Folder Properties</h5>
                            <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="propertiesModalBody"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dx-viewport demo-container">
                <div id="file-manager"></div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('select2.full.min.js') }}"></script>
    <script src="https://cdn3.devexpress.com/jslib/22.2.6/js/dx.all.js"></script>

    <script>
        let getFileMangerRoute = "{{ route('filemanger.data') }}";
        let createFolderRoute = "{{ route('folder.store') }}";
        let deleteFolderRoute = "{{ route('folders.delete') }}";
        let createFileRoute = "{{ route('file.store') }}";
        let multiItemAssignRolesRoute = "{{ route('folder.multiassignroles') }}"
        let getPropertiesRoute = "{{ route('folder.getproperties') }}"

        let createFolderPermission = "{{ current_user()->hasPermission('Folder', 'create') }}"
        let deleteFolderPermission = "{{ current_user()->hasPermission('Folder', 'delete') }}"
        let updateFolderPermission = "{{ current_user()->hasPermission('Folder', 'update') }}"
        let isMultiSelect = '{{ $multiSelect ?? 'false' }}' === '1' ? true : false;

        $('#fileModal').on('shown.bs.modal', function() {
            $(this).find('.select2').select2({
                dropdownParent: $('#fileModal'),
                placeholder: "  Select roles",
                allowClear: true,
                width: '100%'
            });
        });



        $('.select2').select2({
            dropdownParent: $('#processModal'),
            placeholder: "  Select roles",
            allowClear: true,
            width: '100%'
        });

        $('.folderselect2').select2({
            dropdownParent: $('#createFolderModal'),
            placeholder: "  Select roles",
            allowClear: true,
            width: '100%'
        });

        $('#folderUploadModalModal').on('shown.bs.modal', function() {
            $(this).find('.select2').select2({
                dropdownParent: $('#folderUploadModalModal'),
                placeholder: "  Select roles",
                allowClear: true,
                width: '100%'
            });
        });
    </script>
    <script src="{{ asset('app/js/folders.js') }}"></script>
    <script></script>
@endpush
