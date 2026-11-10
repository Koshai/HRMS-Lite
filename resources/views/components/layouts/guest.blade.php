@props([
    'title' => null,
    'heading' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'HRMS Lite') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        <div class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-4 py-10">
            <div class="mb-8 text-center">
                <p class="text-sm font-semibold tracking-wide text-teal-800 uppercase">HRMS Lite</p>
                @if ($heading)
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $heading }}</h1>
                @endif
            </div>
            {{ $slot }}
        </div>
    </body>
</html>
