<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Огляд') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-semibold">Ви успішно ввійшли.</p>
                    <p class="mt-2 text-sm text-gray-600">
                        Перейдіть до
                        <a class="font-medium text-indigo-600 hover:text-indigo-500" href="{{ route('users.index') }}">
                            керування користувачами
                        </a>,
                        щоб перевірити Blade-інтерфейс та CRUD API.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
