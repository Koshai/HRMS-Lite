<x-layouts.app :title="__('Salary details')">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $record->employee->user->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $record->salary_month->format('Y-m') }} · {{ $record->given_status->label() }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $record)
                <a href="{{ route('salaries.edit', $record) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Edit amounts</a>
            @endcan
        </div>
    </div>

    <dl class="mb-8 grid gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Salary amount</dt>
            <dd class="mt-1">{{ $currency }} {{ $record->salary_amount }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Bonus</dt>
            <dd class="mt-1">{{ $currency }} {{ $record->bonus_amount }} {{ $record->has_bonus ? '' : '(none)' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Total</dt>
            <dd class="mt-1 font-medium">{{ $currency }} {{ $record->totalAmount() }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Payment confirmation</dt>
            <dd class="mt-1">
                {{ $record->given_status->label() }}
                @if ($record->isPaid())
                    <span class="block text-xs text-slate-500">Confirmed {{ $record->paid_at?->format('Y-m-d H:i') }} UTC by {{ $record->payer?->name }}</span>
                @endif
            </dd>
        </div>
    </dl>

    <p class="mb-6 text-xs text-slate-500">Paid means an administrator confirmed tracking status. It is not proof of a bank transfer.</p>

    <section>
        <h2 class="mb-3 text-lg font-semibold">Audit history</h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">When (UTC)</th>
                        <th class="px-4 py-3 font-medium">Actor</th>
                        <th class="px-4 py-3 font-medium">Action</th>
                        <th class="px-4 py-3 font-medium">Reason</th>
                        <th class="px-4 py-3 font-medium">Before → After</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($audits as $audit)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="px-4 py-3">{{ $audit->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">{{ $audit->actor?->name }}</td>
                            <td class="px-4 py-3">{{ $audit->action }}</td>
                            <td class="px-4 py-3">{{ $audit->reason }}</td>
                            <td class="px-4 py-3 font-mono text-xs">
                                <div>{{ json_encode($audit->before_json) }}</div>
                                <div class="mt-1">{{ json_encode($audit->after_json) }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No audit events yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
