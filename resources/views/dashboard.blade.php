<x-layouts.app :title="__('Dashboard')">
    <section class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Dashboard</h1>
            <p class="mt-2 text-slate-600">Welcome, {{ auth()->user()->name }}.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            @can('access-admin')
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold">Super user</h2>
                    <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-slate-600">
                        <li><a href="{{ route('employees.index') }}" class="text-teal-900 hover:underline">Employees</a></li>
                        <li><a href="{{ route('attendance.index') }}" class="text-teal-900 hover:underline">Attendance</a></li>
                        <li><a href="{{ route('salaries.index') }}" class="text-teal-900 hover:underline">Salaries</a></li>
                    </ul>
                </div>
            @endcan

            @can('access-employee-self-service')
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold">Employee</h2>
                    <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-slate-600">
                        <li><a href="{{ route('profile.mine') }}" class="text-teal-900 hover:underline">My profile</a></li>
                        <li><a href="{{ route('attendance.mine') }}" class="text-teal-900 hover:underline">My attendance</a></li>
                        <li><a href="{{ route('salaries.mine') }}" class="text-teal-900 hover:underline">My salaries</a></li>
                    </ul>
                </div>
            @endcan

            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <h2 class="font-semibold">Installation</h2>
                <p class="mt-3 text-sm text-slate-600">
                    Timezone {{ config('hrms.display_timezone') }} · Currency {{ config('hrms.currency') }}
                </p>
            </div>
        </div>
    </section>
</x-layouts.app>
