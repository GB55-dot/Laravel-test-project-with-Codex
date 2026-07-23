# User API

Base URL локально: `http://localhost:8000/api`.

Усі endpoints захищені `auth:sanctum`, project middleware `user.auth` і rate
limit 60 запитів за хвилину.

## Автентифікація

Blade-інтерфейс використовує web session: після login браузер автоматично
надсилає cookie, а JavaScript — CSRF header.

Для зовнішнього API client створіть personal access token у Tinker:

```bash
php artisan tinker
```

```php
$user = App\Models\User::query()->firstOrFail();
$token = $user->createToken('local-docs')->plainTextToken;
echo $token;
```

Передавайте token:

```bash
curl -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/users
```

Не комітьте й не логуйте plaintext token: Sanctum показує його лише один раз.

## Response model

```json
{
  "data": {
    "id": 1,
    "name": "Ada Lovelace",
    "email": "ada@example.test",
    "email_verified_at": null,
    "created_at": "2026-07-23T16:00:00.000000Z",
    "updated_at": "2026-07-23T16:00:00.000000Z",
    "links": {
      "self": "http://localhost:8000/api/users/1"
    }
  }
}
```

`password` і `remember_token` ніколи не повертаються.

## GET /api/users

Пагінований список, newest ID first.

Query parameters:

| Поле | Тип | Default | Обмеження |
|---|---|---:|---:|
| `page` | integer | 1 | min 1 |
| `per_page` | integer | 10 | 1–100 |

```bash
curl -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     "http://localhost:8000/api/users?page=1&per_page=10"
```

Response: `200 OK`. Laravel додає `links` і `meta` для пагінації:

```json
{
  "data": [],
  "links": {
    "first": "http://localhost:8000/api/users?page=1",
    "last": "http://localhost:8000/api/users?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": null,
    "last_page": 1,
    "per_page": 10,
    "to": null,
    "total": 0
  }
}
```

## POST /api/users

```bash
curl -X POST \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{"name":"Ada Lovelace","email":"ada@example.test","password":"Strong-password-123","password_confirmation":"Strong-password-123"}' \
     http://localhost:8000/api/users
```

Payload:

| Поле | Правила |
|---|---|
| `name` | required, string, max 255 |
| `email` | required, valid, unique; перед validation нормалізується до lowercase |
| `password` | required, confirmed, password defaults |
| `password_confirmation` | має дорівнювати password |

Response: `201 Created` і User Resource.

## GET /api/users/{id}

```bash
curl -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/users/1
```

Response: `200 OK` або `404 Not Found`.

## PUT /api/users/{id}

```bash
curl -X PUT \
     -H "Accept: application/json" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -d '{"name":"Ada Byron","email":"ada.byron@example.test"}' \
     http://localhost:8000/api/users/1
```

`name` і `email` обов'язкові. `password` optional; якщо його немає, старий
hash зберігається. Якщо password передано, потрібне `password_confirmation`.

Response: `200 OK`.

## DELETE /api/users/{id}

```bash
curl -X DELETE \
     -H "Accept: application/json" \
     -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/users/1
```

Response: `204 No Content`.

## Помилки

### 401

```json
{
  "message": "Unauthenticated."
}
```

### 404

```json
{
  "message": "Користувача не знайдено."
}
```

### 422

```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

Validation messages залишені framework-default англійською. Для повної
локалізації опублікуйте language files та додайте `lang/uk/validation.php`.

## HTTP semantics

- `GET` safe й idempotent;
- `PUT` і `DELETE` idempotent за задумом;
- `POST` створює новий resource;
- `201` означає створення;
- `204` означає успіх без response body;
- `401` — actor не authenticated;
- `403` — authenticated, але не authorized (для майбутньої Policy);
- `404` не знайдено;
- `422` синтаксично правильний JSON, але не пройшов domain validation;
- `429` перевищено rate limit.
