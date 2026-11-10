<x-layouts.app :title="__('Correct attendance')">
    <h1 class="mb-2 text-3xl font-semibold tracking-tight">Correct attendance</h1>
    <p class="mb-6 text-sm text-slate-600">
        {{ $record->employee->user->name }} · work date {{ $record->work_date->toDateString() }} ({{ $timezone }}).
        Work date is not changed by corrections.
    </p>

    <form method="POST" action="{{ route('attendance.update', $record) }}" class="max-w-xl space-y-4 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="expected_check_in_at" value="{{ $record->check_in_at?->toIso8601String() }}">
        <input type="hidden" name="expected_check_out_at" value="{{ $record->check_out_at?->toIso8601String() }}">

        <div>
            <label for="check_in_time" class="mb-1 block text-sm font-medium">Check-in time</label>
            <input id="check_in_time" name="check_in_time" type="time" value="{{ old('check_in_time', $checkInTime) }}" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('check_in_time') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="check_out_time" class="mb-1 block text-sm font-medium">Check-out time (optional)</label>
            <input id="check_out_time" name="check_out_time" type="time" value="{{ old('check_out_time', $checkOutTime) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('check_out_time') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="checkout_next_day" value="1" @checked(old('checkout_next_day', $checkoutNextDay))>
            Check-out is next calendar day
        </label>

        <div>
            <label for="reason" class="mb-1 block text-sm font-medium">Reason</label>
            <textarea id="reason" name="reason" rows="3" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('reason') }}</textarea>
            @error('reason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            @error('attendance') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">Save correction</button>
    </form>
</x-layouts.app>
