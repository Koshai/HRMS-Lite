<x-layouts.app :title="__('Attendance details')">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $record->employee->user->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">Work date {{ $record->work_date->toDateString() }} · {{ $timezone }}</p>
        </div>
        @can('update', $record)
            <a href="{{ route('attendance.edit', $record) }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Correct</a>
        @endcan
    </div>

    <dl class="mb-8 grid gap-4 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Check in</dt>
            <dd class="mt-1">{{ $formatLocal($record->check_in_at) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Check out</dt>
            <dd class="mt-1">{{ $formatLocal($record->check_out_at) ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">Status</dt>
            <dd class="mt-1">{{ $record->isOpen() ? 'Open' : 'Closed' }}</dd>
        </div>
    </dl>

    <section>
        <h2 class="mb-3 text-lg font-semibold">Corrections / audit</h2>
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
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No manual audit events yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
