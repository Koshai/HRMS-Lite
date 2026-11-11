<x-layouts.app :title="__('Salaries')">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Salaries</h1>
            <p class="mt-1 text-sm text-slate-600">Monthly payment tracking only — not bank transfers or payroll.</p>
        </div>
        @can('create', App\Models\MonthlySalary::class)
            <a href="{{ route('salaries.create') }}" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">Add monthly salary</a>
        @endcan
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm">
            <p class="font-medium">Paid (tracked)</p>
            <p class="mt-1 text-slate-600">{{ $paidCount }} records · {{ $currency }} {{ $paidTotal }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm">
            <p class="font-medium">Unpaid (tracked)</p>
            <p class="mt-1 text-slate-600">{{ $unpaidCount }} records · {{ $currency }} {{ $unpaidTotal }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('salaries.index') }}" class="mb-4 grid gap-3 sm:grid-cols-4">
        <select name="employee_id" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All employees</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>
                    {{ $employee->user->name }}
                </option>
            @endforeach
        </select>
        <input type="month" name="salary_month" value="{{ $filters['salary_month'] }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <select name="given_status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All statuses</option>
            <option value="unpaid" @selected($filters['given_status'] === 'unpaid')>Unpaid</option>
            <option value="paid" @selected($filters['given_status'] === 'paid')>Paid</option>
        </select>
        <button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Filter</button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Employee</th>
                    <th class="px-4 py-3 font-medium">Month</th>
                    <th class="px-4 py-3 font-medium">Salary</th>
                    <th class="px-4 py-3 font-medium">Bonus</th>
                    <th class="px-4 py-3 font-medium">Total</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3">{{ $record->employee->user->name }}</td>
                        <td class="px-4 py-3">{{ $record->salary_month->format('Y-m') }}</td>
                        <td class="px-4 py-3">{{ $record->salary_amount }}</td>
                        <td class="px-4 py-3">{{ $record->bonus_amount }}</td>
                        <td class="px-4 py-3">{{ $record->totalAmount() }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex rounded-md px-2 py-0.5 text-xs font-medium',
                                'bg-emerald-100 text-emerald-900' => $record->isPaid(),
                                'bg-amber-100 text-amber-900' => $record->isUnpaid(),
                            ])>{{ $record->given_status->label() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('salaries.show', $record) }}" class="text-teal-900 hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">No monthly salary records found. Missing months are not the same as Unpaid.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</x-layouts.app>
