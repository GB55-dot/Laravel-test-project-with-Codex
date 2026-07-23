# Production deployment checklist

Цей проєкт не прив'язаний до одного хостингу. Він працює на VPS, managed PHP
platform або container platform, якщо платформа підтримує PHP 8.3+, Composer,
Node build step і persistent database.

## 1. Інфраструктура

- PHP 8.3+ з потрібними extensions;
- Nginx/Apache document root: `/path/to/app/public`;
- production database: PostgreSQL/MySQL або persistent SQLite volume;
- Redis рекомендований для cache/session/queues при кількох instances;
- TLS/HTTPS;
- process manager для queue workers і scheduler.

Ніколи не робіть repository root публічним: інакше `.env`, source та інші
файли можуть стати доступними.

## 2. Environment

Скопіюйте `.env.production.example` у secret manager платформи. Не комітьте
реальний `.env`.

```bash
php artisan key:generate --show
```

Збережіть результат як `APP_KEY`. Не генеруйте новий ключ при кожному deploy:
він використовується для encryption і cookies.

Для HTTPS:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://users.example.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
LOG_CHANNEL=stderr
LOG_LEVEL=warning
```

`APP_DEBUG=true` у production може показати stack traces і secrets.

## 3. Immutable build

```bash
composer install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --classmap-authoritative

npm ci
npm run build
```

Build artifact має містити application source, `vendor/` і `public/build/`.
`node_modules/` у runtime не потрібен.

## 4. Release

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

`optimize` кешує config, events, routes і views. Після зміни environment:

```bash
php artisan optimize:clear
php artisan optimize
```

Permissions:

- source — read-only для PHP process;
- `storage/` — writable;
- `bootstrap/cache/` — writable;
- SQLite-файл і його directory — writable, якщо використовується SQLite.

## 5. Workers

Queue worker:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Scheduler (cron щохвилини):

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Після deploy перезапустіть довгоживучі workers:

```bash
php artisan queue:restart
```

## 6. Health, logs і rollback

- load balancer health check: `GET /up`;
- application logs надсилайте в centralized log service;
- налаштуйте error alerts, latency, 5xx rate, DB і queue metrics;
- робіть automated database backups;
- source rollback не завжди означає schema rollback — плануйте backward
  compatible migrations.

Smoke test після release:

```bash
curl --fail https://users.example.com/up
php artisan migrate:status
```

## 7. Zero-downtime принцип

Безпечна послідовність:

1. зібрати новий immutable artifact;
2. запустити backward-compatible migrations;
3. переключити traffic на нову версію;
4. перезапустити workers;
5. виконати smoke tests;
6. лише в наступному release видаляти старі columns/code paths.

Деструктивна migration в одному deploy із кодом часто робить швидкий rollback
неможливим.

Офіційна документація:

- [Deployment](https://laravel.com/docs/13.x/deployment)
- [Queues](https://laravel.com/docs/13.x/queues)
- [Task Scheduling](https://laravel.com/docs/13.x/scheduling)
- [Redis](https://laravel.com/docs/13.x/redis)
