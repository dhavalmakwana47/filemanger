@extends('app.layouts.layout')

@push('styles')
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
    <x-app-breadcrumb title="Role" :breadcrumbs="[
        ['name' => 'Home', 'url' => route('companyrole.index')],
        ['name' => 'Create'],
    ]" />
    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4>{{ isset($role) ? 'Edit' : 'Add' }} Role</h4>
                </div>
                <div class="card-body">

                    <form action="{{ isset($role) ? route('companyrole.update', $role->id) : route('companyrole.store') }}"
                        method="POST">
                        @csrf
                        @if (isset($role))
                            @method('PUT')
                        @endif

                        <div class="form-group">
                            <label for="role-name">Name</label>
                            <input type="text" class="form-control" id="role-name" name="role_name"
                                placeholder="Enter role name"
                                value="{{ old('role_name', isset($role) ? $role->role_name : '') }}">

                            @error('role_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <table class="table table-bordered table-striped w-full">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-2 border text-center">Module</th>
                                    <th class="px-4 py-2 border text-center">Feature</th>
                                        <th class="px-4 py-2 border text-center">Assign
                                        </th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $previousModuleName = null;
                                @endphp
                                @foreach ($permissionsArr as $moduleName => $permissions)
                                    @foreach ($permissions as $index => $permission)
                                        <tr class="hover:bg-gray-50 text-center">
                                            @if ($moduleName !== $previousModuleName)
                                                <td class="px-4 py-2 border font-bold text-gray-700 align-middle"
                                                    rowspan="{{ count($permissions) }}">
                                                    {{ $moduleName }}
                                                </td>
                                                @php
                                                    $previousModuleName = $moduleName;
                                                @endphp
                                            @endif
                                            <td class="px-4 py-2 border ">{{ $permission->getFormattedNameAttribute() }}
                                            </td>
                                            <td class="px-4 py-2 border text-center align-middle">
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input toggle-checkbox" type="checkbox" name="permissions[]"
                                                            value="{{  $permission->id }}"
                                                            {{ isset($role) && $permission->hasRole($role->id) ? 'checked' : '' }}
                                                            {{ auth()->user()->hasPermission('Company Permission', 'update') ? '' : 'disabled' }}>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>


                        <button type="submit" class="btn btn-primary">{{ isset($role) ? 'Update' : 'Create' }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
