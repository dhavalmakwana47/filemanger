<!-- resources/views/emails/otp.blade.php -->
<x-mail::message>
# Your Login OTP

Your one-time password is:

<x-mail::button :url="''" color="success">
    <h1>{{ $code }}</h1>
</x-mail::button>

This code expires in 10 minutes.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>