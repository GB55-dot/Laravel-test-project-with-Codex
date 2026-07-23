<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ __('Керування користувачами') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Blade відображає сторінку, а Fetch API виконує CRUD без перезавантаження.
                </p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-800">
                API захищено Sanctum
            </span>
        </div>
    </x-slot>

    <div
        id="users-app"
        class="py-10"
        data-api-url="{{ url('/api/users') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-login-url="{{ route('login') }}"
    >
        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)] lg:px-8">
            <section class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">Список користувачів</h3>
                        <p id="users-summary" class="mt-1 text-sm text-gray-500" aria-live="polite">
                            Завантаження…
                        </p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        На сторінці
                        <select id="per-page" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                        </select>
                    </label>
                </div>

                <div id="api-message" class="hidden border-b px-5 py-3 text-sm" role="status" aria-live="polite"></div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Користувач</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Створено</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Дії</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body" class="divide-y divide-gray-100 bg-white"></tbody>
                    </table>
                </div>

                <div id="empty-state" class="hidden px-5 py-14 text-center">
                    <p class="font-medium text-gray-800">Користувачів ще немає.</p>
                    <p class="mt-1 text-sm text-gray-500">Створіть першого за допомогою форми праворуч.</p>
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 px-5 py-4">
                    <button id="previous-page" type="button" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                        ← Назад
                    </button>
                    <span id="page-indicator" class="text-sm text-gray-600">Сторінка 1</span>
                    <button id="next-page" type="button" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                        Далі →
                    </button>
                </div>
            </section>

            <aside class="h-fit rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200 lg:sticky lg:top-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 id="form-title" class="font-semibold text-gray-900">Новий користувач</h3>
                        <p id="form-help" class="mt-1 text-sm text-gray-500">Усі поля обов’язкові.</p>
                    </div>
                    <button id="cancel-edit" type="button" class="hidden text-sm font-medium text-gray-500 hover:text-gray-800">
                        Скасувати
                    </button>
                </div>

                <form id="user-form" class="mt-6 space-y-4" novalidate>
                    <input id="user-id" type="hidden">

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Ім’я</label>
                        <input id="name" name="name" type="text" required maxlength="255" autocomplete="name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" required maxlength="255" autocomplete="email"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Пароль</label>
                        <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <p id="password-help" class="mt-1 text-xs text-gray-500">Щонайменше 8 символів.</p>
                    </div>

                    <div>
                        <label for="password-confirmation" class="block text-sm font-medium text-gray-700">Підтвердження пароля</label>
                        <input id="password-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <ul id="form-errors" class="hidden list-disc rounded-md bg-red-50 px-8 py-3 text-sm text-red-700" aria-live="assertive"></ul>

                    <button id="submit-user" type="submit" class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                        Створити користувача
                    </button>
                </form>
            </aside>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/users.js')
    @endpush
</x-app-layout>
