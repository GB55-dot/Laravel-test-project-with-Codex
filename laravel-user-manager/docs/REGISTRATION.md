# Реєстрація користувачів

## Поточна архітектура

Проєкт використовує Laravel Breeze. Тому не потрібно встановлювати Jetstream,
Fortify або створювати паралельний `RegisterController`: Breeze вже надає
маршрути, session guard, login/logout, CSRF middleware та шаблони.

У Breeze контролер за домовленістю має назву
`App\\Http\\Controllers\\Auth\\RegisteredUserController`. Він виконує роль
запитаного `RegisterController`, але не конфліктує з кодом scaffold-а.

## Маршрути

```php
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'showRegistrationForm'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'register'])
        ->middleware('throttle:register')
        ->name('register.store');
});
```

- `guest` не дозволяє already authenticated user повторно відкрити форму;
- `GET /register` показує форму;
- `POST /register` приймає дані;
- `throttle:register` обмежує до 5 спроб на хвилину з однієї IP-адреси.

## Потік даних

```text
Blade form + @csrf
    → POST /register
    → throttle:register
    → RegisterUserRequest
    → RegisteredUserController::register
    → User::create + Hash::make
    → Auth::login
    → session ID regeneration
    → redirect /dashboard + success flash message
```

## Валідація

`RegisterUserRequest` відповідає за нормалізацію і правила:

```php
return [
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:users,email'],
    'password' => ['required', 'confirmed', Password::defaults()],
];
```

`confirmed` вимагає поле `password_confirmation` з ідентичним значенням.
Laravel перенаправляє browser request назад із `$errors` і старими даними;
`x-input-error` у Blade їх відображає.

## Безпека

- `@csrf` генерує приховане поле token; web middleware перевіряє його для POST;
- email normalizes to lowercase до unique check, тож `User@Site.test` і
  `user@site.test` не створять різні облікові записи;
- `Hash::make()` створює односторонній bcrypt hash;
- після `Auth::login()` Laravel регенерує session ID, що зменшує ризик session fixation;
- `$hidden` у `User` не дає випадково показати password у JSON;
- `$casts` із `password => hashed` є додатковою гарантією, якщо пароль
  присвоять у model в іншому місці;
- named rate limiter зменшує automated spam.

## Тести та команди

```bash
php artisan route:list --name=register
php artisan test tests/Feature/Auth/RegistrationTest.php
php artisan test
vendor/bin/pint --test
```

Тести перевіряють rendering форми, створення user у БД, hash password,
автоматичний login, окремий login після logout, duplicate email і rate limit.

## Альтернативи

| Підхід | Коли обирати | Компроміс |
|---|---|---|
| Breeze (цей проєкт) | Класична Laravel app, швидкий старт | Мінімум готових extra-функцій |
| Jetstream | Потрібні 2FA, sessions, teams | Більше коду й складніше налаштування |
| Fortify | Headless API/SPA auth | Потрібно створювати власні views/frontend |
| Власна реалізація | Нестандартний signup flow | Більше security-відповідальності |
