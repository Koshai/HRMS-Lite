<x-layouts.app :title="__('Employees')">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Employees</h1>
            <p class="mt-1 text-sm text-slate-600">Provision and manage staff accounts.</p>
        </div>
        <a href="{{ route('employees.create') }}" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">Add employee</a>
    </div>

    <form method="GET" action="{{ route('employees.index') }}" class="mb-4 flex gap-2">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search name, email, designation" class="w-full max-w-md rounded-md border border-slate-300 px-3 py-2 text-sm">
        <button class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm">Search</button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Email</th>
                    <th class="px-4 py-3 font-medium">Designation</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($employees as $employee)
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3">
                            <a href="{{ route('employees.show', $employee) }}" class="font-medium text-teal-900 hover:underline">{{ $employee->user->name }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $employee->user->email }}</td>
                        <td class="px-4 py-3">{{ $employee->designation }}</td>
                        <td class="px-4 py-3">{{ $employee->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">No employees found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>
</x-layouts.app>
