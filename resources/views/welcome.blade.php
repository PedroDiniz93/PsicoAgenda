<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @php
            $hasVite = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));
        @endphp

        @if ($hasVite)
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="min-h-screen bg-slate-100 font-sans antialiased text-slate-900">
        <div id="app"></div>

        @unless ($hasVite)
            <div class="mx-auto mt-16 w-full max-w-lg rounded-xl border border-dashed border-slate-400 bg-white/70 p-6 text-center shadow">
                <p class="font-semibold text-slate-700">Frontend Vue indisponível.</p>
                <p class="mt-2 text-sm text-slate-500">
                    Execute <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">npm install</code> e
                    <code class="rounded bg-slate-100 px-1 py-0.5 text-xs">npm run dev</code> para iniciar o Vite e carregar a aplicação.
                </p>
            </div>
        @endunless
    </body>
</html>
