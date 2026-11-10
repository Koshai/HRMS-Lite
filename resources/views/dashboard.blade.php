<x-layouts.app :title="__('Dashboard')">
    <section class="space-y-6">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Dashboard</h1>
            <p class="mt-2 text-slate-600">Welcome, {{ auth()->user()->name }}.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            @can('access-admin')
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold">Super user placeholders</h2>
                    <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-slate-600">
                        <li>Employees (M1)</li>
                        <li>Attendance (M2)</li>
                        <li>Salaries (M3)</li>
                    </ul>
                </div>
            @endcan

            @can('access-employee-self-service')
                <div class="rounded-xl border border-slate-200 bg-white p-5">
                    <h2 class="font-semibold">Employee placeholders</h2>
                    <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-slate-600">
                        <li>My profile (M1)</li>
                        <li>My attendance (M2)</li>
                        <li>My salaries (M3)</li>
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
