<x-layouts.app :title="__('Edit monthly salary')">
    <h1 class="mb-2 text-3xl font-semibold tracking-tight">Edit monthly salary</h1>
    <p class="mb-6 text-sm text-slate-600">
        {{ $record->employee->user->name }} · {{ $record->salary_month->format('Y-m') }} · status {{ $record->given_status->label() }}.
        Month cannot be changed. A reason is required.
    </p>

    @if ($record->isPaid())
        <p class="mb-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-900">This record is Paid. Reverse to Unpaid before changing amounts.</p>
    @endif

    <form method="POST" action="{{ route('salaries.update', $record) }}" class="max-w-xl space-y-4 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="expected_version" value="{{ $record->version }}">

        <div>
            <label for="salary_amount" class="mb-1 block text-sm font-medium">Salary amount ({{ $currency }})</label>
            <input id="salary_amount" name="salary_amount" type="text" inputmode="decimal" value="{{ old('salary_amount', $record->salary_amount) }}" required @disabled($record->isPaid()) class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('salary_amount') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="has_bonus" value="1" @checked(old('has_bonus', $record->has_bonus)) @disabled($record->isPaid()) onchange="document.getElementById('bonus_amount').disabled = !this.checked || {{ $record->isPaid() ? 'true' : 'false' }};">
            Has bonus
        </label>

        <div>
            <label for="bonus_amount" class="mb-1 block text-sm font-medium">Bonus amount ({{ $currency }})</label>
            <input id="bonus_amount" name="bonus_amount" type="text" inputmode="decimal" value="{{ old('bonus_amount', $record->bonus_amount) }}" @disabled($record->isPaid() || !old('has_bonus', $record->has_bonus)) class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('bonus_amount') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="reason" class="mb-1 block text-sm font-medium">Reason</label>
            <textarea id="reason" name="reason" rows="3" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('reason') }}</textarea>
            @error('reason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            @error('salary') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900" @disabled($record->isPaid())>Save changes</button>
    </form>
</x-layouts.app>
