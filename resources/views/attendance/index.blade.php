<x-layouts.app :title="__('Attendance')">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Attendance</h1>
            <p class="mt-1 text-sm text-slate-600">All employee attendance ({{ $timezone }}). Manual entry arrives in M2B.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('attendance.index') }}" class="mb-4 grid gap-3 sm:grid-cols-4">
        <select name="employee_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All employees</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>
                    {{ $employee->user->name }}
                </option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <input type="date" name="to" value="{{ $filters['to'] }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Filter</button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Employee</th>
                    <th class="px-4 py-3 font-medium">Work date</th>
                    <th class="px-4 py-3 font-medium">Check in</th>
                    <th class="px-4 py-3 font-medium">Check out</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3">{{ $record->employee->user->name }}</td>
                        <td class="px-4 py-3">{{ $record->work_date->toDateString() }}</td>
                        <td class="px-4 py-3">{{ $formatLocal($record->check_in_at) }}</td>
                        <td class="px-4 py-3">{{ $formatLocal($record->check_out_at) ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $record->isOpen() ? 'Open' : 'Closed' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">No attendance records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</x-layouts.app>
