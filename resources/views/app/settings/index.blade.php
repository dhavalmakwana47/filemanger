@extends('app.layouts.layout')


@push('styles')
    <link rel="stylesheet" href="{{ asset('select2.min.css') }}">
@endpush

@section('content')
    <x-app-breadcrumb title="Settings" :breadcrumbs="[['name' => 'Home', 'url' => route('companyrole.index')], ['name' => 'Settings']]" />

    <div class="app-content">
        <div class="container-fluid">

            <!-- Success / Error Messages -->
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('ip_success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('ip_success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if (session('ip_deleted'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    {{ session('ip_deleted') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-cog me-2"></i> Company Settings
                    </h4>
                </div>
                <div class="card-body">

                    <!-- Settings Form -->
                    <form action="{{ route('settings.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Watermark Image -->
                        <div class="mb-4">
                            <label for="watermark_image" class="form-label fw-bold">Watermark Image</label>
                            <input type="file" class="form-control @error('watermark_image') is-invalid @enderror"
                                name="watermark_image" accept=".png,.jpg,.jpeg" id="watermark_image">

                            @if ($setting->watermark_image)
                                <div class="mt-3">
                                    <img src="{{ asset('storage/' . $setting->watermark_image) }}" alt="Current Watermark"
                                        class="rounded shadow-sm" width="140">
                                    <small class="text-muted d-block mt-1">Current watermark (will be replaced on
                                        upload)</small>
                                </div>
                            @endif

                            @error('watermark_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>



                        <!-- Watermark Toggle -->
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="enable_watermark" value="1"
                                id="enable_watermark" {{ $setting->enable_watermark ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="enable_watermark">
                                Enable Watermark on Images
                                <span class="text-muted fw-normal d-block small">
                                    Automatically apply watermark to generated images
                                </span>
                            </label>
                        </div>

                        <!-- NDA Content Toggle -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="nda_content_enable" value="1"
                                id="nda_content_enable" {{ $setting->nda_content_enable ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="nda_content_enable">
                                Enable NDA Content
                                <span class="text-muted fw-normal d-block small">
                                    Show NDA content to users
                                </span>
                            </label>
                        </div>

                        <!-- NDA Content -->
                        <div class="mb-4">
                            <label for="nda_content" class="form-label fw-bold">NDA Content</label>
                            <textarea class="form-control @error('nda_content') is-invalid @enderror tinymce-editor" name="nda_content"
                                id="nda_content" rows="15" placeholder="Enter NDA content here...">{{ old('nda_content', $setting->nda_content) }}</textarea>
                            @error('nda_content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <hr class="my-5">
                        <h5 class="fw-bold text-primary mb-3">
                            <i class="fas fa-network-wired me-2"></i> Allowed IP Addresses
                        </h5>
                        <!-- IP Restriction Toggle -->
                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="ip_restriction" value="1"
                                id="ip_restriction" {{ $setting->ip_restriction ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="ip_restriction">
                                Enable IP Restriction
                                <span class="text-muted fw-normal d-block small">
                                    Only allow access from whitelisted IP addresses
                                </span>
                            </label>
                        </div>

                        <div class="form-group mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="users">Select Restricted Users</label>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="selectAllUsers">
                                    <label class="form-check-label" for="selectAllUsers">Select All</label>
                                </div>
                            </div>
                            <select name="users[]" id="users" class="form-control" multiple>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ in_array($user->id, $ipRestrictedUsers) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>

                            @error('users')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save me-2"></i> Update Settings
                        </button>
                    </form>

                    <hr class="my-5">

                    <!-- IP Address Manager -->
                    <div class="ip-manager">

                        <p class="text-muted mb-4">
                            These IPs are allowed when <strong>IP Restriction</strong> is enabled.
                        </p>

                        <!-- Add IP Form -->
                        <form action="{{ route('settings.ip.add') }}" method="POST"
                            class="row g-3 mb-4 align-items-end">
                            @csrf
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">IP Address</label>
                                <input type="text" name="ip_address"
                                    class="form-control @error('ip_address') is-invalid @enderror"
                                    placeholder="e.g., 192.168.1.100" required>
                                @error('ip_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Label (Optional)</label>
                                <input type="text" name="label" class="form-control"
                                    placeholder="e.g., Office, VPN">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-plus me-1"></i> Add IP
                                </button>
                            </div>
                        </form>

                        <!-- IP List -->
                        @if ($setting->ipRestrictions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>IP Address</th>
                                            <th>Label</th>
                                            <th width="100" class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($setting->ipRestrictions as $ip)
                                            <tr>
                                                <td>
                                                    <code class="bg-light px-2 py-1 rounded">{{ $ip->ip_address }}</code>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $ip->label ?? '—' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <form action="{{ route('settings.ip.remove', $ip->id) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Remove IP {{ $ip->ip_address }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-ban fa-3x mb-3"></i>
                                <p class="mb-0">No IP addresses added yet.</p>
                                <small>Add one above to get started.</small>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('select2.full.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            tinymce.init({
                selector: '.tinymce-editor',
                plugins: 'advlist autolink lists link image charmap print preview anchor',
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                menubar: false,
                height: 300,
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
                skin: 'oxide',
                statusbar: false,
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            });
        });
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
                const allSelected = $usersSelect.find('option').length === $usersSelect.find(
                    'option:selected').length;
                $('#selectAllUsers').prop('checked', allSelected);
            });
        });
    </script>
@endpush
