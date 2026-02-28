@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap4.min.css" />
    <style>
        .form-group {
            margin-bottom: 1.5rem;
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Role" :breadcrumbs="[['name' => 'Home', 'url' => route('companyrole.index')], ['name' => 'Assign Users']]" />
    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4>Assign Users to Role - {{ $role->role_name }}</h4>
                </div>
                <div class="card-body">
                    <form id="assignUsersForm" action="{{ route('companyrole.usersassignstore') }}" method="POST">
                        @csrf
                        <input type="hidden" name="role_id" value="{{ $role->id }}">
                        <input type="hidden" name="users" id="selectedUsers" value="">

                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-primary" id="selectAllBtn">Select All</button>
                            <button type="button" class="btn btn-sm btn-secondary" id="deselectAllBtn">Deselect All</button>
                            <span class="ms-3" id="selectedCount">0 users selected</span>
                        </div>

                        <table id="users-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">
                                        <input type="checkbox" id="selectAllCheckbox">
                                    </th>
                                    <th>Name</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                        </table>

                        <button type="submit" class="btn btn-primary mt-3">Assign Users</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            const assignedUserIds = @json($assignedUserIds);
            let selectedUsers = new Set(assignedUserIds);

            const table = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('companyrole.usersassign', $role->id) }}',
                columns: [
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: function(data) {
                            const checked = selectedUsers.has(data) ? 'checked' : '';
                            return `<input type="checkbox" class="user-checkbox" value="${data}" ${checked}>`;
                        }
                    },
                    { data: 'name', name: 'name' },
                    { data: 'email', name: 'email' }
                ],
                pageLength: 25,
                drawCallback: function() {
                    updateSelectedCount();
                }
            });

            // Handle individual checkbox
            $(document).on('change', '.user-checkbox', function() {
                const userId = parseInt($(this).val());
                if ($(this).is(':checked')) {
                    selectedUsers.add(userId);
                } else {
                    selectedUsers.delete(userId);
                }
                updateSelectedCount();
            });

            // Select all on current page
            $('#selectAllCheckbox').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.user-checkbox').each(function() {
                    const userId = parseInt($(this).val());
                    $(this).prop('checked', isChecked);
                    if (isChecked) {
                        selectedUsers.add(userId);
                    } else {
                        selectedUsers.delete(userId);
                    }
                });
                updateSelectedCount();
            });

            // Select all users (all pages)
            $('#selectAllBtn').on('click', function() {
                $.ajax({
                    url: '{{ route('companyrole.getalluserids', $role->id) }}',
                    success: function(data) {
                        selectedUsers = new Set(data.user_ids);
                        table.draw(false);
                        updateSelectedCount();
                    }
                });
            });

            // Deselect all
            $('#deselectAllBtn').on('click', function() {
                selectedUsers.clear();
                table.draw(false);
                updateSelectedCount();
            });

            // Update count
            function updateSelectedCount() {
                $('#selectedCount').text(selectedUsers.size + ' users selected');
            }

            // Form submission
            $('#assignUsersForm').on('submit', function(e) {
                $('#selectedUsers').val(JSON.stringify(Array.from(selectedUsers)));
            });

            updateSelectedCount();
        });
    </script>
@endpush
