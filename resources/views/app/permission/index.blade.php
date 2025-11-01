@extends('app.layouts.layout')
@push('styles')
    <style>
        .message-success {
            background-color: #4caf50;
            /* Green for success */
            color: white;
            padding: 10px;
            position: fixed;
            top: 20px;
            /* Show message at the top */
            right: 20px;
            top: 70px;
            /* Align message to the right */
            border-radius: 5px;
            z-index: 1000;
            /* Ensure it stays on top of other elements */
        }

        .message-error {
            background-color: #f44336;
            /* Red for error */
            color: white;
            padding: 10px;
            position: fixed;
            top: 70px;


            /* Show message at the top */
            right: 20px;
            /* Align message to the right */
            border-radius: 5px;
            z-index: 1000;
            /* Ensure it stays on top of other elements */
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Permissions" :breadcrumbs="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Permissions', 'url' => route('permission.index')],
    ]" />
    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">Role Permissions Management</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered table-striped w-full">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-2 border text-center">Module</th>
                                <th class="px-4 py-2 border text-center">Feature</th>
                                @foreach ($roles as $role)
                                    <th class="px-4 py-2 border text-center">{{ $role->getFormattedNameAttribute() }}</th>
                                @endforeach
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
                                        <td class="px-4 py-2 border ">{{ $permission->getFormattedNameAttribute() }}</td>
                                        @foreach ($roles as $role)
                                            <td class="px-4 py-2 border text-center align-middle">
                                                <div class="d-flex justify-content-center align-items-center">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input toggle-checkbox" type="checkbox"
                                                            id="switch{{ $role->id . '-' . $permission->id }}"
                                                            value="{{ $role->id . '-' . $permission->id }}"
                                                            {{ $permission->hasRole($role->id) ? 'checked' : '' }}
                                                            {{ auth()->user()->hasPermission('Company Permission', 'update') ? '' : 'disabled' }}>
                                                    </div>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleCheckboxes = document.querySelectorAll('.toggle-checkbox');

            toggleCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {

                    // Get the checkbox value
                    const value = this.value.split('-');


                    const data = {
                        role_id: value[0],
                        permission_id: value[1],
                        status: this.checked
                    };
                    fetch('{{ route('role_permission.change_permission') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]').getAttribute(
                                    'content') // Include CSRF token for Laravel
                            },
                            body: JSON.stringify(data)
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json(); // Assuming the response is JSON
                        })
                        .then(data => {
                            showMessage(`Permission updated successfully.`, 'success');

                        })
                        .catch((error) => {
                            showMessage(`Something went wrong.`, 'error');

                        });

                });
            });
        });

        // Function to display messages dynamically
        function showMessage(message, type) {
            const messageBox = document.createElement('div');
            messageBox.textContent = message;
            messageBox.className = type === 'success' ? 'message-success' : 'message-error';
            document.body.appendChild(messageBox);

            // Remove the message after 3 seconds
            setTimeout(() => {
                messageBox.remove();
            }, 3000);
        }
    </script>
@endpush
