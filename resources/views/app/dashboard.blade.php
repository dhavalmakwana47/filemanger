@extends('app.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />

    <style>
        .dashboard-card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .dashboard-card .card-header {
            font-size: 1.25rem;
            font-weight: bold;
        }
        .space-info-item {
            font-size: 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .space-info-item strong {
            color: #343a40;
        }
        .space-info-item span {
            color: #495057;
        }
        .progress {
            height: 1.5rem;
            border-radius: 0.25rem;
        }
        .progress-bar {
            background-color: #28a745;
        }
        .alert-dismissible .btn-close {
            padding: 0.75rem 1.25rem;
        }
        .top-files-table {
            font-size: 0.9rem;
            margin-top: 1rem;
        }
        .top-files-table th {
            background-color: #f8f9fa;
            border-top: none;
        }
        .top-files-table td {
            border-top: none;
        }
        @media (max-width: 767px) {
            .space-info-item {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <x-app-breadcrumb title="Dashboard" :breadcrumbs="[['name' => 'Home', 'url' => route('dashboard')], ['name' => 'Dashboard', 'url' => null]]" />

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
                @if (isset($spaceDetails) && (auth()->user()->is_master_admin() || auth()->user()->is_super_admin()))
                    <!-- Existing Storage Card -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-hdd"></i> Storage Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="space-info-item">
                                    <strong>Total Space:</strong>
                                    <span>{{ $spaceDetails->total_space ?? '0' }} MB</span>
                                </div>
                                <div class="space-info-item">
                                    <strong>Used Space:</strong>
                                    <span>{{ $spaceDetails->used_space ?? '0' }} MB</span>
                                </div>
                                <div class="space-info-item">
                                    <strong>Available Space:</strong>
                                    <span>{{ $spaceDetails->available_space ?? '0' }} MB</span>
                                </div>
                                <div class="space-info-item">
                                    <strong>Storage Usage:</strong>
                                    <div class="progress w-50">
                                        <div class="progress-bar" role="progressbar"
                                             style="width: {{ ($spaceDetails->used_space / $spaceDetails->total_space * 100) ?? 0 }}%;"
                                             aria-valuenow="{{ ($spaceDetails->used_space / $spaceDetails->total_space * 100) ?? 0 }}"
                                             aria-valuemin="0" aria-valuemax="100">
                                            {{ round(($spaceDetails->used_space / $spaceDetails->total_space * 100), 2) ?? 0 }}%
                                        </div>
                                    </div>
                                </div>
                                <div class="space-info-item">
                                    <strong>Plan Start Date:</strong>
                                    <span>{{ $spaceDetails->start_date ? \Carbon\Carbon::parse($spaceDetails->start_date)->format('d M Y') : 'N/A' }}</span>
                                </div>
                                <div class="space-info-item">
                                    <strong>Plan End Date:</strong>
                                    <span>{{ $spaceDetails->end_date ? \Carbon\Carbon::parse($spaceDetails->end_date)->format('d M Y') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New: File Views Card -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-eye"></i> File Views Today</h5>
                            </div>
                            <div class="card-body">
                                <div class="space-info-item">
                                    <strong>Total Views:</strong>
                                    <span>{{ $totalViews ?? 0 }}</span>
                                </div>
                                @if(($topViewedFiles ?? [])->count() > 0)
                                    <table class="table top-files-table">
                                        <thead>
                                            <tr>
                                                <th>Top Files</th>
                                                <th>Views</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topViewedFiles as $file)
                                                <tr>
                                                    <td>{{ $file['name'] }}</td>
                                                    <td>{{ $file['count'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-muted">No views today.</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Existing: Today's Logins Card -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-sign-in-alt"></i> Today's Logins</h5>
                            </div>
                            <div class="card-body">
                                <div class="space-info-item">
                                    <strong>Total Logins Today:</strong>
                                    <span>{{ $login_count ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New: File Downloads Card -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="fas fa-download"></i> File Downloads Today</h5>
                            </div>
                            <div class="card-body">
                                <div class="space-info-item">
                                    <strong>Total Downloads:</strong>
                                    <span>{{ $totalDownloads ?? 0 }}</span>
                                </div>
                                @if(($topDownloadedFiles ?? [])->count() > 0)
                                    <table class="table top-files-table">
                                        <thead>
                                            <tr>
                                                <th>Top Files</th>
                                                <th>Downloads</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topDownloadedFiles as $file)
                                                <tr>
                                                    <td>{{ $file['name'] }}</td>
                                                    <td>{{ $file['count'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-muted">No downloads today.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="col-md-12">
                        <div class="card dashboard-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> Welcome to Your Dashboard</h5>
                            </div>
                            <div class="card-body">
                                <p>Welcome, {{ auth()->user()->name }}! This is your dashboard. You can view and manage your account details from here.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection