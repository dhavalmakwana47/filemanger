<!-- resources/views/auth/two-factor.blade.php -->
<x-guest-layout>
    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf

        <div class="mb-4 text-sm text-gray-600">
            We've sent a 6-digit code to <strong>{{ auth()->user()?->email ?? 'your email' }}</strong>
        </div>

        <!-- OTP Code -->
        <div>
            <x-input-label for="code" :value="__('Enter OTP Code')" />
            <x-text-input id="code" class="block mt-1 w-full text-center text-2xl" type="text" inputmode="numeric" name="code" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a href="{{ route('2fa.resend') }}" class="text-sm text-gray-600 hover:text-gray-900">
                Resend code
            </a>

            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>

        @if (session('status'))
            <p class="mt-4 text-sm text-green-600">{{ session('status') }}</p>
        @endif
    </form>
</x-guest-layout>