<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'HRMS Lite') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
        <main class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center px-4 py-16">
            <p class="text-sm font-semibold tracking-wide text-teal-800 uppercase">HRMS Lite</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight">Employee attendance and salary status</h1>
            <p class="mt-4 max-w-xl text-base text-slate-600">
                Single-business portal for provisioning staff, recording daily attendance, and tracking monthly salary paid status.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">Open dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-md bg-teal-800 px-4 py-2 text-sm font-medium text-white hover:bg-teal-900">Sign in</a>
                @endauth
            </div>
        </main>
    </body>
</html>
