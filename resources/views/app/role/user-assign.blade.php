@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('select2.min.css') }}">

    <style>
        /* Custom styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            width: 100%;
        }

        .text-danger {
            font-size: 0.875rem;
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Role" :breadcrumbs="[['name' => 'Home', 'url' => route('companyrole.index')], ['name' => 'Create']]" />
    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4>Assign Users to Role - {{ $role->role_name }}</h4>
                </div>
                <div class="card-body">

                    {{-- 🔴 Show errors on top --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('companyrole.usersassignstore') }}" method="POST">
                        @csrf
                        <input type="hidden" name="role_id" value="{{ $role->id }}">

                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="users">Select Users</label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="selectAllUsers">
                                    <label class="form-check-label" for="selectAllUsers">Select All</label>
                                </div>
                            </div>
                            <select name="users[]" id="users" class="form-control" multiple>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ in_array($user->id, $assignedUserIds) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>

                            @error('users')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Assign Users</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('select2.full.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            const $usersSelect = $('#users');
            $usersSelect.select2({
                placeholder: "Search and select users...",
                allowClear: true,
                width: '100%',
                closeOnSelect: false
            });

            // Handle Select All functionality
            $('#selectAllUsers').on('change', function() {
                if ($(this).is(':checked')) {
                    $usersSelect.find('option').prop('selected', true);
                } else {
                    $usersSelect.val(null);
                }
                $usersSelect.trigger('change');
            });

            // Update Select All checkbox when selection changes
            $usersSelect.on('change', function() {
                const allSelected = $usersSelect.find('option').length === $usersSelect.find('option:selected').length;
                $('#selectAllUsers').prop('checked', allSelected);
            });
        });
    </script>



    {{-- 🔴 SweetAlert for Errors --}}
    @if($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: `{!! implode('<br>', $errors->all()) !!}`,
            });
        </script>
    @endif
@endpush
