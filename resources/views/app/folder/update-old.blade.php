<div class="modal-header bg-primary text-white">
    <h5 class="modal-title" id="folderModalLabel"><i class="fas fa-folder-edit"></i>{{  $title}}</h5>
    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
    <div class="mb-3">
        <label for="folderName" class="form-label fw-bold"><i class="fas fa-folder"></i> Folder Name</label>
        <input type="text" class="form-control form-control-lg" id="folderName" name="name"
            value="{{ isset($folder->name) ? $folder->name : '' }}" required placeholder="Enter folder name...">
    </div>

    <div class="form-group mb-3">
        <label class="form-label fw-bold"><i class="fas fa-users"></i> Assign Permissions by Role</label>
        <div class="border p-3 rounded bg-light">
            @foreach ($roleArr as $role)
                @php
                    $permissions = isset($assignedPermissions[$role->id]) ? $assignedPermissions[$role->id] : [];
                @endphp

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="fas fa-user-tag"></i> {{ $role->role_name }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="can_view_{{ $role->id }}"
                                        name="permissions[{{ $role->id }}][]" value="can_view"
                                        {{ !empty($permissions) && isset($permissions['can_view']) && $permissions['can_view'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="can_view_{{ $role->id }}">
                                        <i class="fas fa-eye text-success"></i> View
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="can_create_{{ $role->id }}"
                                        name="permissions[{{ $role->id }}][]" value="can_create"
                                        {{ !empty($permissions) && isset($permissions['can_create']) && $permissions['can_create'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="can_create_{{ $role->id }}">
                                        <i class="fas fa-plus-circle text-success"></i> Create
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="can_update_{{ $role->id }}"
                                        name="permissions[{{ $role->id }}][]" value="can_update"
                                        {{ !empty($permissions) && isset($permissions['can_update']) && $permissions['can_update'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="can_update_{{ $role->id }}">
                                        <i class="fas fa-edit text-warning"></i> Update
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="can_delete_{{ $role->id }}"
                                        name="permissions[{{ $role->id }}][]" value="can_delete"
                                        {{ !empty($permissions) && isset($permissions['can_delete']) && $permissions['can_delete'] ? 'checked' : '' }}>
                                    <label class="form-check-label" for="can_delete_{{ $role->id }}">
                                        <i class="fas fa-trash-alt text-danger"></i> Delete
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="modal-footer bg-light">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ isset($folder) ? 'Update' : 'Add' }}</button>
</div>
