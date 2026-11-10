<x-layouts.guest :title="__('Log in')" :heading="__('Sign in')">
    @if (session('status'))
        <p class="mb-4 rounded-md bg-teal-50 px-3 py-2 text-sm text-teal-900">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                autocomplete="username"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            >
            @error('email')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="remember" class="rounded border-slate-300">
            Remember me
        </label>

        <button type="submit" class="w-full rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">
            Log in
        </button>

        @if (Route::has('password.request'))
            <p class="text-center text-sm">
                <a href="{{ route('password.request') }}" class="text-teal-800 hover:underline">Forgot password?</a>
            </p>
        @endif
    </form>
</x-layouts.guest>
