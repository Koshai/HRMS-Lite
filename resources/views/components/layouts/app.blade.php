@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'HRMS Lite') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4 px-4 py-4">
                <div>
                    <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight text-teal-900">HRMS Lite</a>
                    <p class="text-xs text-slate-500">{{ auth()->user()->role->label() }}</p>
                </div>
                <nav class="flex flex-wrap items-center gap-4 text-sm">
                    <a href="{{ route('dashboard') }}" class="text-slate-700 hover:text-teal-800">Dashboard</a>
                    @can('viewAny', App\Models\Employee::class)
                        <a href="{{ route('employees.index') }}" class="text-slate-700 hover:text-teal-800">Employees</a>
                    @endcan
                    @if (auth()->user()->employee)
                        <a href="{{ route('profile.mine') }}" class="text-slate-700 hover:text-teal-800">My Profile</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-700 hover:text-teal-800">Log out</button>
                    </form>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8">
            @if (session('status'))
                <p class="mb-4 rounded-md bg-teal-50 px-3 py-2 text-sm text-teal-900">{{ session('status') }}</p>
            @endif
            {{ $slot }}
        </main>
        @livewireScripts
    </body>
</html>
