<x-layouts.app :title="__('My Attendance')">
    <div class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">My Attendance</h1>
        <p class="mt-1 text-sm text-slate-600">
            Server time is recorded automatically ({{ $timezone }}). You cannot edit timestamps.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-800">
            <ul class="list-disc ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mb-8 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="font-semibold">Current status</h2>

        @if ($open)
            <p class="mt-2 text-sm text-slate-700">
                Checked in for work date <strong>{{ $open->work_date->toDateString() }}</strong>
                at {{ $formatLocal($open->check_in_at) }} {{ $timezone }}.
            </p>
        @elseif ($current && ! $current->isOpen())
            <p class="mt-2 text-sm text-slate-700">
                Today's attendance is closed
                ({{ $formatLocal($current->check_in_at) }} → {{ $formatLocal($current->check_out_at) }} {{ $timezone }}).
            </p>
        @else
            <p class="mt-2 text-sm text-slate-700">You are not checked in.</p>
        @endif

        <div class="mt-4 flex flex-wrap gap-3">
            @if ($canCheckIn)
                <form method="POST" action="{{ route('attendance.check-in') }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">
                        Check In
                    </button>
                </form>
            @endif

            @if ($canCheckOut)
                <form method="POST" action="{{ route('attendance.check-out') }}">
                    @csrf
                    <button type="submit" class="rounded-md border border-teal-700 px-4 py-2 text-sm font-medium text-teal-900 hover:bg-teal-50">
                        Check Out
                    </button>
                </form>
            @endif
        </div>
    </section>

    <section>
        <h2 class="mb-3 text-lg font-semibold">History</h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">Work date</th>
                        <th class="px-4 py-3 font-medium">Check in</th>
                        <th class="px-4 py-3 font-medium">Check out</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $record)
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3">{{ $record->work_date->toDateString() }}</td>
                            <td class="px-4 py-3">{{ $formatLocal($record->check_in_at) }}</td>
                            <td class="px-4 py-3">{{ $formatLocal($record->check_out_at) ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $record->isOpen() ? 'Open' : 'Closed' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500">No attendance records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $history->links() }}</div>
    </section>
</x-layouts.app>
