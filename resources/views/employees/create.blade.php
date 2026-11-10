<x-layouts.app :title="__('Add employee')">
    <h1 class="mb-6 text-3xl font-semibold tracking-tight">Add employee</h1>

    <form method="POST" action="{{ route('employees.store') }}" enctype="multipart/form-data" class="max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        @include('employees._form', ['employee' => null, 'includePassword' => true])

        <button type="submit" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">
            Create employee
        </button>
    </form>
</x-layouts.app>
