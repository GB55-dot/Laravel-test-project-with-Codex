<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel User Manager') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100 antialiased">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-6 py-16 sm:px-8">
        {{-- Декоративні фони не впливають на читабельність або доступність сторінки. --}}
        <div aria-hidden="true" class="absolute -left-32 top-0 h-80 w-80 rounded-full bg-indigo-500/20 blur-3xl"></div>
        <div aria-hidden="true" class="absolute -bottom-40 -right-24 h-96 w-96 rounded-full bg-cyan-400/10 blur-3xl"></div>

        <section class="relative w-full max-w-2xl rounded-3xl border border-white/10 bg-white/[0.06] p-8 shadow-2xl shadow-black/30 backdrop-blur sm:p-12">
            <div class="mb-10 flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-500 text-lg font-bold text-white shadow-lg shadow-indigo-500/30">
                    {{ mb_strtoupper(mb_substr(config('app.name', 'L'), 0, 1)) }}
                </div>
                <span class="text-sm font-semibold tracking-wide text-slate-300">{{ config('app.name', 'Laravel User Manager') }}</span>
            </div>

            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-indigo-300">Панель керування</p>
            <h1 class="max-w-xl text-4xl font-bold tracking-tight text-white sm:text-5xl">
                Керуйте користувачами без зайвого.
            </h1>
            <p class="mt-5 max-w-xl text-base leading-7 text-slate-300 sm:text-lg">
                Увійдіть до свого облікового запису або створіть новий, щоб перейти до робочої панелі.
            </p>

            @guest
                <div class="mt-10 grid gap-4 sm:grid-cols-2">
                    <a
                        href="{{ route('login') }}"
                        class="group rounded-2xl border border-slate-600 bg-slate-900/70 p-5 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-950"
                    >
                        <span class="block text-lg font-semibold text-white">Увійти</span>
                        <span class="mt-1 block text-sm text-slate-400 group-hover:text-slate-300">Продовжити роботу з акаунтом</span>
                    </a>

                    <a
                        href="{{ route('register') }}"
                        class="group rounded-2xl bg-indigo-500 p-5 shadow-lg shadow-indigo-500/25 transition hover:-translate-y-0.5 hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2 focus:ring-offset-slate-950"
                    >
                        <span class="block text-lg font-semibold text-white">Зареєструватися</span>
                        <span class="mt-1 block text-sm text-indigo-100 group-hover:text-white">Створити новий обліковий запис</span>
                    </a>
                </div>
            @else
                {{-- Резервний варіант, якщо цей view буде повернуто поза кореневим маршрутом. --}}
                <a
                    href="{{ route('dashboard') }}"
                    class="mt-10 inline-flex rounded-xl bg-indigo-500 px-5 py-3 font-semibold text-white transition hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:ring-offset-2 focus:ring-offset-slate-950"
                >
                    Відкрити Dashboard
                </a>
            @endguest

            <p class="mt-8 text-sm text-slate-400">Ваші дані захищені стандартними механізмами автентифікації Laravel.</p>
        </section>
    </main>
</body>
</html>
