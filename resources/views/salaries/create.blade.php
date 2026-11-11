<x-layouts.app :title="__('Add monthly salary')">
    <h1 class="mb-2 text-3xl font-semibold tracking-tight">Add monthly salary</h1>
    <p class="mb-6 text-sm text-slate-600">Snapshot amount defaults from the employee’s current base salary. You may confirm or adjust before saving. Status starts as Unpaid.</p>

    <form method="POST" action="{{ route('salaries.store') }}" class="max-w-xl space-y-4 rounded-xl border border-slate-200 bg-white p-6">
        @csrf

        <div>
            <label for="employee_id" class="mb-1 block text-sm font-medium">Employee</label>
            <select id="employee_id" name="employee_id" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                onchange="const opt=this.options[this.selectedIndex]; document.getElementById('salary_amount').value = opt.dataset.salary || '';">
                <option value="">Select employee</option>
                @foreach ($employees as $employee)
                    <option
                        value="{{ $employee->id }}"
                        data-salary="{{ $employee->base_salary_amount }}"
                        @selected(old('employee_id') == $employee->id)
                    >{{ $employee->user->name }} ({{ $employee->is_active ? 'active' : 'inactive' }})</option>
                @endforeach
            </select>
            @error('employee_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="salary_month" class="mb-1 block text-sm font-medium">Month</label>
            <input id="salary_month" name="salary_month" type="month" value="{{ old('salary_month', $defaultMonth) }}" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('salary_month') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="salary_amount" class="mb-1 block text-sm font-medium">Salary amount ({{ $currency }})</label>
            <input id="salary_amount" name="salary_amount" type="text" inputmode="decimal" value="{{ old('salary_amount') }}" placeholder="Defaults to current base salary" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('salary_amount') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="has_bonus" value="1" @checked(old('has_bonus')) onchange="document.getElementById('bonus_amount').disabled = !this.checked; if(!this.checked){document.getElementById('bonus_amount').value='0.00';}">
            Has bonus
        </label>

        <div>
            <label for="bonus_amount" class="mb-1 block text-sm font-medium">Bonus amount ({{ $currency }})</label>
            <input id="bonus_amount" name="bonus_amount" type="text" inputmode="decimal" value="{{ old('bonus_amount', '0.00') }}" @disabled(!old('has_bonus')) class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @error('bonus_amount') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="reason" class="mb-1 block text-sm font-medium">Reason</label>
            <textarea id="reason" name="reason" rows="3" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('reason') }}</textarea>
            @error('reason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">Save monthly salary</button>
    </form>
</x-layouts.app>
