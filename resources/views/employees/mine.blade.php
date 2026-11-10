<x-layouts.app :title="__('My profile')">
    <h1 class="mb-6 text-3xl font-semibold tracking-tight">My profile</h1>

    <div class="grid gap-6 lg:grid-cols-[180px_1fr]">
        <div>
            @if ($employee->hasPhoto())
                <img src="{{ route('profile.mine.photo') }}" alt="Profile photo" class="h-40 w-40 rounded-xl object-cover">
            @else
                <div class="flex h-40 w-40 items-center justify-center rounded-xl border border-dashed border-slate-300 text-sm text-slate-500">No photo</div>
            @endif
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Name</dt>
                <dd class="mt-1">{{ $employee->user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Email</dt>
                <dd class="mt-1">{{ $employee->user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Designation</dt>
                <dd class="mt-1">{{ $employee->designation }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Phone</dt>
                <dd class="mt-1">{{ $employee->phone ?: '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Address</dt>
                <dd class="mt-1">{{ $employee->address ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Blood group</dt>
                <dd class="mt-1">{{ $employee->blood_group?->value ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Base salary</dt>
                <dd class="mt-1">{{ number_format((float) $employee->base_salary_amount, 2) }} {{ config('hrms.currency') }}</dd>
            </div>
        </dl>
    </div>
</x-layouts.app>
