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
                            <label for="users">Select Users</label>
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
            $('#users').select2({
                placeholder: "Select users",
                allowClear: true
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
