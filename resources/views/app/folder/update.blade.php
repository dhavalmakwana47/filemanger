<div class="modal-header bg-primary text-white">
    <h5 class="modal-title" id="folderModalLabel"><i class="fas fa-folder-edit"></i>{{ $title }}</h5>
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
        <select class="form-control folderselect2" name="roles[]"  multiple>
            @foreach ($roleArr as $role)
                <option value="{{ $role->id }}"
                    {{ isset($folder) && in_array($role->id, $assignedRoles) ? 'selected' : '' }}>
                    {{ $role->role_name }}
                </option>
            @endforeach
        </select>
    </div>

     <div class="mb-3">
        <label for="folderIndex" class="form-label fw-bold"><i class="fas fa-folder"></i> Index</label>
        <input type="number" min="0" class="form-control form-control-lg" id="folderIndex" name="item_index"
            value="{{ isset($folder->item_index) ? $folder->item_index : '' }}"  placeholder="Enter Index ...">
    </div>

</div>

<div class="modal-footer bg-light">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>
        {{ isset($folder) ? 'Update' : 'Add' }}</button>
</div>
@if (isset($folder))
    <script>
        $('.folderselect2').select2({
            dropdownParent: $('#folderModal'),
            placeholder: "  Select roles",
            allowClear: true,
            width: '100%'
        });
    </script>
@endif
