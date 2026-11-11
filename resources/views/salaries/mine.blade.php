<x-layouts.app :title="__('My Salaries')">
    <h1 class="mb-2 text-3xl font-semibold tracking-tight">My Salaries</h1>
    <p class="mb-6 text-sm text-slate-600">Read-only history of your monthly salary tracking records. Paid means an admin confirmation, not a bank transfer.</p>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Month</th>
                    <th class="px-4 py-3 font-medium">Base salary</th>
                    <th class="px-4 py-3 font-medium">Bonus</th>
                    <th class="px-4 py-3 font-medium">Total</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3">{{ $record->salary_month->format('Y-m') }}</td>
                        <td class="px-4 py-3">{{ $currency }} {{ $record->salary_amount }}</td>
                        <td class="px-4 py-3">{{ $currency }} {{ $record->bonus_amount }}</td>
                        <td class="px-4 py-3">{{ $currency }} {{ $record->totalAmount() }}</td>
                        <td class="px-4 py-3">{{ $record->given_status->label() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">No salary records yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $records->links() }}</div>
</x-layouts.app>
