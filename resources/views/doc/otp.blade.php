@extends('doc._layout')

@section('title', 'Enter OTP')

@section('content')
<h5 class="text-center">Verify Your Identity</h5>
<p class="doc-title text-center">{{ $document->title }}</p>

@if(session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
@endif

<p class="text-muted small mb-3">
    A 6-digit OTP was sent to <strong>{{ $email }}</strong>. It expires in 10 minutes.
</p>

<form action="{{ route('doc.verify-otp', $token) }}" method="POST">
    @csrf
    <input type="hidden" name="email" value="{{ $email }}">
    <div class="mb-3">
        <label class="form-label small fw-semibold">One-Time Password</label>
        <input type="text" name="otp" class="form-control text-center @error('otp') is-invalid @enderror"
               maxlength="6" placeholder="000000" required autofocus
               style="font-size:22px; letter-spacing:8px">
        @error('otp')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn btn-primary w-100 mb-3">Verify & View Document</button>
</form>

<div class="text-center">
    <form action="{{ route('doc.send-otp', $token) }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <button type="submit" class="btn btn-link btn-sm p-0 text-muted">Resend OTP</button>
    </form>
    &nbsp;·&nbsp;
    <a href="{{ route('doc.email', $token) }}" class="btn btn-link btn-sm p-0 text-muted">Change email</a>
</div>
@endsection
