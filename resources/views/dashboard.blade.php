<x-layouts.app :title="__('Dashboard')">
    <section class="space-y-8">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Dashboard</h1>
            <p class="mt-2 text-slate-600">Welcome, {{ auth()->user()->name }}.</p>
        </div>

        @if ($admin)
            <div class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 class="text-lg font-semibold">Super user overview</h2>
                    <p class="text-xs text-slate-500">Paid is an admin confirmation only — not a bank transfer proof. Month {{ $admin['monthLabel'] }}.</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs tracking-wide text-slate-500 uppercase">Active employees</p>
                        <p class="mt-2 text-2xl font-semibold">{{ $admin['activeEmployees'] }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs tracking-wide text-slate-500 uppercase">Checked in now</p>
                        <p class="mt-2 text-2xl font-semibold">{{ $admin['checkedInToday'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Open attendance as of {{ $today }} ({{ $timezone }})</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs tracking-wide text-slate-500 uppercase">Missing salary rows</p>
                        <p class="mt-2 text-2xl font-semibold">{{ $admin['missingSalaryEmployees'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Active staff without a {{ $admin['monthLabel'] }} record (not the same as Unpaid)</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-xs tracking-wide text-emerald-800 uppercase">Paid this month</p>
                        <p class="mt-2 text-2xl font-semibold text-emerald-950">{{ $admin['paidCount'] }}</p>
                        <p class="mt-1 text-sm text-emerald-900">{{ $currency }} {{ $admin['paidTotal'] }} tracked</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs tracking-wide text-amber-800 uppercase">Unpaid this month</p>
                        <p class="mt-2 text-2xl font-semibold text-amber-950">{{ $admin['unpaidCount'] }}</p>
                        <p class="mt-1 text-sm text-amber-900">{{ $currency }} {{ $admin['unpaidTotal'] }} tracked</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('employees.create') }}" class="rounded-md bg-teal-800 px-3 py-2 text-sm font-medium text-white hover:bg-teal-900">Add employee</a>
                    <a href="{{ route('attendance.create') }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Add attendance</a>
                    <a href="{{ route('salaries.create') }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Add monthly salary</a>
                    <a href="{{ route('employees.index') }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Employees</a>
                    <a href="{{ route('attendance.index') }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Attendance</a>
                    <a href="{{ route('salaries.index') }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Salaries</a>
                </div>

                @if ($admin['activeEmployees'] === 0)
                    <p class="rounded-md border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-600">
                        No employees yet. Start by adding your first staff member.
                    </p>
                @endif
            </div>
        @endif

        @if ($employeePanel)
            <div class="space-y-4">
                <h2 class="text-lg font-semibold">My day</h2>
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <p class="text-sm text-slate-500">{{ $employeePanel['profile']->designation }}</p>
                    <p class="text-xl font-semibold">{{ $employeePanel['profile']->user->name }}</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-xs tracking-wide text-slate-500 uppercase">Attendance</p>
                            @if ($employeePanel['open'])
                                <p class="mt-1 text-sm">Checked in {{ $employeePanel['formatLocal']($employeePanel['open']->check_in_at) }} ({{ $timezone }})</p>
                            @elseif ($employeePanel['current'] && ! $employeePanel['current']->isOpen())
                                <p class="mt-1 text-sm">Today closed</p>
                            @else
                                <p class="mt-1 text-sm">Not checked in</p>
                            @endif

                            <div class="mt-3 flex flex-wrap gap-2">
                                @if ($employeePanel['canCheckIn'])
                                    <form method="POST" action="{{ route('attendance.check-in') }}">
                                        @csrf
                                        <button class="rounded-md bg-teal-800 px-3 py-2 text-sm font-medium text-white hover:bg-teal-900">Check In</button>
                                    </form>
                                @endif
                                @if ($employeePanel['canCheckOut'])
                                    <form method="POST" action="{{ route('attendance.check-out') }}">
                                        @csrf
                                        <button class="rounded-md border border-teal-700 px-3 py-2 text-sm text-teal-900">Check Out</button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div>
                            <p class="text-xs tracking-wide text-slate-500 uppercase">Latest salary</p>
                            @if ($employeePanel['latestSalary'])
                                <p class="mt-1 text-sm">
                                    {{ $employeePanel['latestSalary']->salary_month->format('Y-m') }}
                                    ·
                                    <span @class([
                                        'font-medium',
                                        'text-emerald-800' => $employeePanel['latestSalary']->isPaid(),
                                        'text-amber-800' => $employeePanel['latestSalary']->isUnpaid(),
                                    ])>{{ $employeePanel['latestSalary']->given_status->label() }}</span>
                                </p>
                                <p class="mt-1 text-sm text-slate-600">{{ $currency }} {{ $employeePanel['latestSalary']->totalAmount() }}</p>
                            @else
                                <p class="mt-1 text-sm text-slate-600">No salary records yet.</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 text-sm">
                        <a href="{{ route('profile.mine') }}" class="text-teal-900 hover:underline">My Profile</a>
                        <a href="{{ route('attendance.mine') }}" class="text-teal-900 hover:underline">My Attendance</a>
                        <a href="{{ route('salaries.mine') }}" class="text-teal-900 hover:underline">My Salaries</a>
                    </div>
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold">Installation</h2>
            <p class="mt-3 text-sm text-slate-600">
                Timezone {{ $timezone }} · Currency {{ $currency }}
            </p>
        </div>
    </section>
</x-layouts.app>
