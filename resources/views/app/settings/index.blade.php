@extends('app.layouts.layout')


@section('content')
    <x-app-breadcrumb title="Settings" :breadcrumbs="[['name' => 'Home', 'url' => route('companyrole.index')], ['name' => 'Create']]" />
    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h4>Configure Settings</h4>
                </div>
                <div class="card-body">

                    <form action="{{ route('settings.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="watermark_image" class="form-label">Watermark Image</label>
                            <input type="file" class="form-control" name="watermark_image" accept=".png,image/png">
                            @if ($settings && $settings->watermark_image)
                                <img src="{{ asset('storage/' . $settings->watermark_image) }}" width="120"
                                    class="mt-2 rounded">
                            @endif
                            @error('watermark_image')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="ip_restriction" value="1"
                                {{ $settings && $settings->ip_restriction ? 'checked' : '' }}>
                            <label class="form-check-label">Enable IP Restriction</label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="enable_watermark" value="1"
                                {{ $settings && $settings->enable_watermark ? 'checked' : '' }}>
                            <label class="form-check-label">Enable Watermark</label>
                        </div>




                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
