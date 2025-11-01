@extends('app.layouts.layout')

@push('styles')
    <style>
        /* Existing styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            width: 100%;
        }

        .text-danger {
            font-size: 0.875rem;
        }

        /* Enhanced toggle switch styling */
        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-check-input.toggle-switch-input {
            width: 3rem;
            height: 1.5rem;
            margin: 0;
            cursor: pointer;
            background-color: #dc3545;
            /* Red when unchecked */
            border: none;
            transition: background-color 0.3s ease;
        }

        .form-check-input.toggle-switch-input:checked {
            background-color: #28a745;
            /* Green when checked */
        }

        .form-check-input.toggle-switch-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            /* Green focus ring */
        }

        .form-check-label {
            font-size: 0.9rem;
            color: #333;
            cursor: pointer;
        }

        /* Hover effect for better UX */
        .toggle-switch:hover .form-check-label {
            color: #007bff;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('select2.min.css') }}">
@endpush

@section('content')
    <x-app-breadcrumb title="Users" :breadcrumbs="[
        ['name' => 'Home', 'url' => route('users.index')],
        ['name' => isset($user) ? 'Update' : 'Create'],
    ]" />
    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4>{{ isset($user) ? 'Edit' : 'Add' }} User</h4>
                </div>
                <div class="card-body">
                    <form action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}"
                        method="POST">
                        @csrf
                        @if (isset($user))
                            @method('PUT')
                        @endif

                        <!-- Existing fields: Name, Email, Role -->
                        <div class="form-group">
                            <label for="user-name">Name</label>
                            <input type="text" class="form-control" id="user-name" name="user_name"
                                placeholder="Enter user name"
                                value="{{ old('user_name', isset($user) ? $user->name : '') }}">
                            @error('user_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="user-email">Email</label>
                            <input type="email" class="form-control" id="user-email" name="user_email"
                                placeholder="Enter user email"
                                value="{{ old('user_email', isset($user->email) ? $user->email : '') }}">
                            @error('user_email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="role">Role</label>
                            <select class="form-control select2" name="role[]" id="role" multiple>
                                @foreach ($roleArr as $role)
                                    <option value="{{ $role->id }}" @if (
                                        (is_array(old('role')) && in_array($role->id, old('role'))) ||
                                            (isset($user) && $user->companyRoles()->where('company_role_id', $role->id)->exists())) selected @endif>
                                        {{ $role->role_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Enhanced Is Active Toggle -->
                        <div class="form-group">
                            <label for="is-active">User Status</label>
                            <div class="toggle-switch">
                                <input type="checkbox" class="form-check-input toggle-switch-input" id="is-active"
                                    name="is_active" value="1"
                                    {{ old('is_active', isset($user) ? $user->is_active : true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is-active">
                                    {{ old('is_active', isset($user) ? $user->is_active : true) ? 'Active' : 'Inactive' }}
                                </label>
                            </div>
                            @error('is_active')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- New Password Field (only for edit) -->
                        @if (isset($user))
                            <div class="form-group">
                                <label for="password">New Password (optional)</label>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Enter new password (leave blank to keep current)">
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation">Confirm New Password</label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation" placeholder="Confirm new password">
                                @error('password_confirmation')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary">{{ isset($user) ? 'Update' : 'Create' }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('select2.full.min.js') }}"></script>
    <script>
        $('.select2').select2({
            placeholder: "  Select roles",
            allowClear: true,
            width: '100%'
        });

        // Optional: Dynamic label text for toggle
        document.getElementById('is-active').addEventListener('change', function() {
            const label = this.nextElementSibling;
            label.textContent = this.checked ? 'Active' : 'Inactive';
        });
    </script>
@endpush