<x-layouts.app :title="$employee->user->name">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $employee->user->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $employee->designation }} · {{ $employee->is_active ? 'Active' : 'Inactive' }}</p>
        </div>
        @can('update', $employee)
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('employees.edit', $employee) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Edit</a>
                @if ($employee->is_active)
                    @can('deactivate', $employee)
                        <form method="POST" action="{{ route('employees.deactivate', $employee) }}" onsubmit="return confirm('Deactivate this employee?')">
                            @csrf
                            <button class="rounded-md border border-red-300 bg-white px-3 py-2 text-sm text-red-800">Deactivate</button>
                        </form>
                    @endcan
                @else
                    @can('reactivate', $employee)
                        <form method="POST" action="{{ route('employees.reactivate', $employee) }}">
                            @csrf
                            <button class="rounded-md border border-teal-300 bg-white px-3 py-2 text-sm text-teal-900">Reactivate</button>
                        </form>
                    @endcan
                @endif
            </div>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-[180px_1fr]">
        <div>
            @if ($employee->hasPhoto())
                <img src="{{ route('employees.photo', $employee) }}" alt="Profile photo" class="h-40 w-40 rounded-xl object-cover">
            @else
                <div class="flex h-40 w-40 items-center justify-center rounded-xl border border-dashed border-slate-300 text-sm text-slate-500">No photo</div>
            @endif
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Email</dt>
                <dd class="mt-1">{{ $employee->user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Phone</dt>
                <dd class="mt-1">{{ $employee->phone ?: '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Address</dt>
                <dd class="mt-1">{{ $employee->address ?: '—' }}</dd>
            </div>
            @if ($canViewSensitive)
                <div>
                    <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Blood group</dt>
                    <dd class="mt-1">{{ $employee->blood_group?->value ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Base salary</dt>
                    <dd class="mt-1">{{ number_format((float) $employee->base_salary_amount, 2) }} {{ config('hrms.currency') }}</dd>
                </div>
            @endif
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Must change password</dt>
                <dd class="mt-1">{{ $employee->user->must_change_password ? 'Yes' : 'No' }}</dd>
            </div>
        </dl>
    </div>

    @can('resetPassword', $employee)
        <section class="mt-8 max-w-md rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Reset temporary password</h2>
            <p class="mt-1 text-sm text-slate-600">Sets a new temporary password and forces change on next login. Existing passwords are never displayed.</p>
            <form method="POST" action="{{ route('employees.reset-password', $employee) }}" class="mt-4 space-y-3">
                @csrf
                <input type="password" name="password" required autocomplete="new-password" placeholder="New temporary password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                @error('password') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
                <button class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">Reset password</button>
            </form>
        </section>
    @endcan
</x-layouts.app>
