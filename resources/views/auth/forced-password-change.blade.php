<x-layouts.guest :title="__('Change password')" :heading="__('Change your temporary password')">
    <form method="POST" action="{{ route('password.forced.update') }}" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <p class="text-sm text-slate-600">
            Your administrator set a temporary password. Choose a new password before using HRMS Lite.
        </p>

        <div>
            <label for="current_password" class="mb-1 block text-sm font-medium">Current password</label>
            <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('current_password')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-medium">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('password')
                <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-medium">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        </div>

        <button type="submit" class="w-full rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">
            Save new password
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-slate-600 hover:text-teal-800">Log out</button>
    </form>
</x-layouts.guest>
