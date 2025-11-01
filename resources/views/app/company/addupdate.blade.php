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
    <x-app-breadcrumb title="Create Company" :breadcrumbs="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Companies', 'url' => route('company.index')],
        ['name' => isset($company) ? 'Update' : 'Create' ],
    ]" />
    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4>Company</h4>
                </div>
                <div class="card-body">
<form action="{{ isset($company) ? route('company.update', $company->id) : route('company.store') }}"
    method="POST">
    @csrf
    @if (isset($company))
        @method('PUT')
    @endif

    <div class="form-group">
        <label for="company-name">Company Name</label>
        <input type="text" class="form-control" id="company-name" name="company_name"
            placeholder="Enter company name"
            value="{{ old('company_name', isset($company) ? $company->name : '') }}">
        @error('company_name')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="start-date">Start Date</label>
        <input type="date" class="form-control" id="start-date" name="start_date"
            value="{{ old('start_date', isset($company) ? $company->start_date : '') }}">
        @error('start_date')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="end-date">End Date</label>
        <input type="date" class="form-control" id="end-date" name="end_date"
            value="{{ old('end_date', isset($company) ? $company->end_date : '') }}">
        @error('end_date')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-group">
        <label for="storage-size">Storage Size (MB)</label>
        <input type="number" class="form-control" id="storage-size" name="storage_size_mb"
            placeholder="Enter storage size in MB" min="0"
            value="{{ old('storage_size_mb', isset($company) ? $company->storage_size_mb : '') }}">
        @error('storage_size_mb')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>

    @if (!isset($company))
        <h5>Admin User Details</h5>

        <div class="form-group">
            <label for="user-name">Name</label>
            <input type="text" class="form-control" id="user-name" name="user_name"
                placeholder="Enter user name" value="{{ old('user_name') }}">
            @error('user_name')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="user-email">Email</label>
            <input type="email" class="form-control" id="user-email" name="user_email"
                placeholder="Enter user email" value="{{ old('user_email') }}">
            @error('user_email')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password"
                placeholder="Enter password">
            @error('password')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="confirm-password">Confirm Password</label>
            <input type="password" class="form-control" id="confirm-password"
                name="password_confirmation" placeholder="Confirm password">
            @error('password_confirmation')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>
    @endif

    <button type="submit" class="btn btn-primary">{{ isset($company) ? 'Update' : 'Create' }} Company</button>
</form>

                </div>
            </div>
        </div>
    </div>
@endsection
