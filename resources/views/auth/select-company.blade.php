<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Select a Company</h2>
        <p class="text-sm text-gray-500 mt-1">You have access to multiple companies. Choose one to continue.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 rounded-lg text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('select-company.store') }}">
        @csrf
        <div class="space-y-3">
            @foreach ($companies as $company)
                <label class="flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition
                              border-gray-200 hover:border-indigo-400 has-[:checked]:border-indigo-600
                              has-[:checked]:bg-indigo-50 dark:border-gray-600 dark:hover:border-indigo-400">
                    <input type="radio" name="company_id" value="{{ $company->id }}"
                           class="text-indigo-600 focus:ring-indigo-500"
                           {{ $loop->first ? 'checked' : '' }}>
                    <div>
                        <div class="font-medium text-gray-800 dark:text-gray-200">{{ $company->name }}</div>
                    </div>
                </label>
            @endforeach
        </div>

        <x-primary-button class="w-full justify-center mt-6">
            Continue
        </x-primary-button>
    </form>
</x-guest-layout>
