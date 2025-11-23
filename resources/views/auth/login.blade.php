<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="captcha()" id="loginForm">
        @csrf

        <!-- Email -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                          :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <!-- 6-DIGIT CAPTCHA -->
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Security Check <span class="text-red-600">*</span>
            </label>

            <div class="flex items-center gap-6">
                <!-- Random 6-digit code -->
                <div class="font-mono text-4xl tracking-widest text-indigo-600 dark:text-indigo-400 
                            bg-gray-50 dark:bg-gray-800 px-8 py-5 rounded-xl border-2 border-dashed border-indigo-300 select-none"
                     x-text="captchaCode.split('').join(' ')">
                    000000
                </div>

                <!-- Input -->
                <div class="flex-1">
                    <x-text-input x-model="userCode" @input="showAlert = false"
                                  id="captcha_input" class="block w-full text-lg" 
                                  type="text" placeholder="Enter 6 digits" maxlength="6" 
                                  inputmode="numeric" required autocomplete="off" />
                </div>
            </div>

            <!-- RED ALERT BOX - ONLY WHEN WRONG -->
            <div x-show="showAlert" 
                 x-transition.duration.500ms
                 class="mt-4 p-4 bg-red-50 border border-red-300 text-red-700 rounded-lg flex items-center gap-3"
                 role="alert">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <strong>Invalid CAPTCHA!</strong> Please type the correct 6-digit code shown above.
                </div>
            </div>

            <p class="mt-3 text-xs text-gray-500">
                This helps prevent bots. New code generated on wrong attempt.
            </p>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end mt-8">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button type="button" class="ms-4" @click="validateCaptcha()">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <!-- Alpine.js - WITH RED ALERT -->
    <script>
        function captcha() {
            return {
                captchaCode: '',
                userCode: '',
                showAlert: false,

                init() {
                    this.generateCode();
                },

                generateCode() {
                    this.captchaCode = Math.floor(100000 + Math.random() * 900000).toString();
                    this.userCode = '';
                    this.showAlert = false;
                },

                validateCaptcha() {
                    if (this.userCode.trim() === this.captchaCode) {
                        // Correct → submit form
                        document.getElementById('loginForm').submit();
                    } else {
                        // Wrong → show red alert + new code
                        this.showAlert = true;
                        this.generateCode();
                        this.$nextTick(() => document.getElementById('captcha_input').focus());
                    }
                }
            }
        }
    </script>
</x-guest-layout>