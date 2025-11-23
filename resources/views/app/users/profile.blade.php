@extends('app.layouts.layout')
@push('styles')
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />

    <style>
        .form-label {
            font-weight: bold;
        }

        .form-control {
            border-radius: 0.25rem;
        }

        .alert-dismissible .btn-close {
            padding: 0.75rem 1.25rem;
        }

        .card-space-info {
            background-color: #f8f9fa;
        }

        .space-info-item {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .space-info-item strong {
            color: #343a40;
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Profile Update" :breadcrumbs="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Profile', 'url' => route('users.updateprofile')],
    ]" />

    <div class="app-content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <!-- Profile Update Card -->
                <div class="col-md-{{isset($spaceDetails) ? '6' : '12'}}">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-user-edit"></i> Update Profile</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('users.updateprofile') }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="name" class="form-label"><i class="fas fa-user"></i> Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', current_user()->name) }}"
                                        required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email', current_user()->email) }}"
                                        required>
                                    @error('email')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label"><i class="fas fa-lock"></i>Password
                                        (optional)</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password">
                                    @error('password')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label"><i class="fas fa-lock"></i>
                                        Confirm Password</label>
                                    <input type="password" class="form-control" id="password_confirmation"
                                        name="password_confirmation">
                                </div>

                                <div class="mb-3 form-check form-switch ml-3">
                                    <input type="checkbox" class="form-check-input" id="two_factor_enabled" name="two_factor_enabled" value="1" {{ old('two_factor_enabled', current_user()->two_factor_enabled) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="two_factor_enabled">Enable Two-Factor Authentication</label>
                                    <div class="form-text text-muted">Add an extra layer of security to your account</div>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @if (isset($spaceDetails))
                    <!-- Space and Plan Information Card -->
                    <div class="col-md-6">
                        <div class="card card-space-info">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-hdd"></i> Plan & Space Information</h5>
                            </div>
                            <div class="card-body">

                                <div class="space-info-item">
                                    <strong>Total Space:</strong> {{ $spaceDetails->total_space ?? '0' }} MB
                                </div>
                                <div class="space-info-item">
                                    <strong>Used Space:</strong> {{ $spaceDetails->used_space ?? '0' }} MB
                                </div>
                                <div class="space-info-item">
                                    <strong>Available Space:</strong> {{ $spaceDetails->available_space ?? '0' }} MB
                                </div>
                                <div class="space-info-item">
                                    <strong>Plan Start Date:</strong>
                                    {{ $spaceDetails->start_date ? \Carbon\Carbon::parse($spaceDetails->start_date)->format('d M Y') : 'N/A' }}
                                </div>
                                <div class="space-info-item">
                                    <strong>Plan End Date:</strong>
                                    {{ $spaceDetails->end_date ? \Carbon\Carbon::parse($spaceDetails->end_date)->format('d M Y') : 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
