<div class="form-group mb-3">
    <label class="form-label fw-bold"><i class="fas fa-users"></i> Assign Permissions by Role</label>
    <select class="form-control select2" name="roles[]" multiple>
        @foreach ($roleArr as $role)
            <option value="{{ $role->id }}"
                {{ isset($file) && in_array($role->id, $assignedRoles) ? 'selected' : '' }}>
                {{ $role->role_name }}
            </option>
        @endforeach
    </select>
</div>
