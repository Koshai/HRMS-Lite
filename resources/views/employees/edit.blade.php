<x-layouts.app :title="__('Edit employee')">
    <h1 class="mb-6 text-3xl font-semibold tracking-tight">Edit {{ $employee->user->name }}</h1>

    <form method="POST" action="{{ route('employees.update', $employee) }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        @include('employees._form', ['employee' => $employee, 'includePassword' => false])

        <button type="submit" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">
            Save changes
        </button>
    </form>
</x-layouts.app>
