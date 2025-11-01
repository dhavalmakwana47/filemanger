<div class="d-flex justify-content-center">
  <div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" role="switch"
           data-id="{{ $user->id }}"
           {{ $user->companyUser->is_active ? 'checked' : '' }}>
    <label class="form-check-label"></label>
  </div>
</div>
