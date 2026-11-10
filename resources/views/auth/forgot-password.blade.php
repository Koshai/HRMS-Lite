<x-layouts.guest :title="__('Forgot password')" :heading="__('Reset password')">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <p class="text-sm text-slate-600">Enter your email and we will send a password reset link when mail is configured.</p>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('email')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">
            Email reset link
        </button>
    </form>
</x-layouts.guest>
