# Festival AI staging deployment

## Security rules

- Do not put an OpenAI key in source code, a Git commit, a ticket, or a screenshot.
- Keep the staging `.env` only on the staging server. All `.env.*` files are ignored by Git.
- Add the OpenAI key after deployment in **Admin → Settings → AI Configuration**. The diagnostic command never prints or logs the key.
- Set `APP_ENV=staging`, `APP_DEBUG=false`, an HTTPS `APP_URL`, and a unique `APP_KEY` on staging.
- Keep `storage/logs` outside the public web root and restrict it to server administrators.

## Deploy commands

Run these commands in the staging project folder after the source code has been deployed:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan festival-ai:staging-check
```

`festival-ai:staging-check` verifies migrations, the database queue configuration, the presence of the admin-configured Artera AI key, and outbound HTTPS access to OpenAI. It intentionally makes an unauthenticated request, so HTTP `401` or `403` means the server connection is working. No key is displayed, written to logs, or sent by this check.

## Queue worker

The supplied Docker entrypoint now starts and supervises the dedicated Festival AI
worker automatically. After a container rebuild/deploy, check the container logs
for `Starting Festival AI queue worker...`.

If staging uses a non-Docker deployment, keep one managed worker running
continuously with the equivalent of:

```bash
php artisan queue:work festival-ai --queue=festival-ai --sleep=1 --tries=1 --timeout=210 --max-time=3600
```

After each non-Docker deploy, run `php artisan queue:restart`; the process manager
should start a fresh worker automatically.

## Logs and failure diagnosis

Run this to watch the secure server log:

```bash
tail -f storage/logs/laravel.log
```

For Docker deployments, the dedicated worker output is also available at:

```bash
tail -f storage/logs/festival-ai-worker.log
```

Festival AI failures are logged with generation ID, provider, HTTP status, and provider error code only. API keys, authorization headers, prompts containing credentials, and raw request payloads are never logged.

## Final staging test

1. Configure the OpenAI key in the staging Admin panel.
2. Enable one Festival AI festival, style, and image model for a test subscription plan.
3. Generate one image from the staging mobile app.
4. Confirm the generation reaches `completed` and the generated file is accessible from the staging domain.
