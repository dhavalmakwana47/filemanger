@extends('doc._layout')

@section('title', 'Access Document')

@section('content')
<h5 class="text-center">Access Document</h5>
<p class="doc-title text-center">{{ $document->title }}</p>

@if(session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
@endif

<p class="text-muted small mb-3">Enter your email address to receive a one-time password.</p>

<form action="{{ route('doc.send-otp', $token) }}" method="POST">
    @csrf
    <div class="mb-3">
        <label class="form-label small fw-semibold">Email Address</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary w-100">Send OTP</button>
</form>
@endsection
