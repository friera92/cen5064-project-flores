# NeighbourLend — local development with Docker Compose

This guide describes the **actual Docker development environment** checked into the NeighbourLend repository. The repository already includes `compose.yaml`, `.env.docker.example`, `docker/php/Dockerfile`, the Laravel app in `neighbourlend/`, and the Nuxt app in `ui/`. Run the commands from the repository root. It is a local/demo setup, **not a production deployment**.

Prerequisite: Docker Desktop with Compose. On Windows, enable the WSL 2 backend and Linux containers.

## 1. Verify the repository layout

Clone the project with `git clone https://github.com/friera92/cen5064-project-flores.git` if needed. Do **not** copy files from a separate starter kit: `compose.yaml`, `docker/`, `neighbourlend/`, and `ui/` are part of the repository.

## 2. Create local environment files

**Windows PowerShell:**

```powershell
Copy-Item .env.docker.example .env
Copy-Item .\neighbourlend\.env.example .\neighbourlend\.env
```

**macOS / Linux:**

```bash
cp .env.docker.example .env
cp neighbourlend/.env.example neighbourlend/.env
```

Only create files that do not exist; **do not overwrite existing `.env` files**. In the root `.env`, set `POSTGRES_DB=neighbourlend`, `POSTGRES_USER=neighbourlend`, and a **unique local password** in `POSTGRES_PASSWORD`. The backend receives these values through `compose.yaml` together with `DB_HOST=db` and `DB_PORT=5432` (these container variables take precedence over Laravel's `.env`). Keep `APP_URL=http://localhost:8000` in `neighbourlend/.env`. Never commit either `.env` file.

## 3. Build and start all services

```powershell
docker compose config
docker compose up -d --build
docker compose ps
docker compose logs --tail=50 backend frontend
```

On first start the backend installs Composer dependencies to a named Docker volume, and the frontend installs npm dependencies (npm ci) to another volume.

## 4. Generate Laravel APP_KEY (one time)

```powershell
docker compose exec backend php artisan key:generate
```

## 5. Migrate and seed the isolated demo database

```powershell
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan migrate
```

For a **fresh local demo database**, these extra initialization commands are **required** to show equipment and reservations and to serve uploaded images:

```powershell
docker compose exec backend php artisan db:seed
docker compose exec backend php artisan storage:link
```

`DatabaseSeeder.php` runs `UserSeeder`, `CategorySeeder`, `ToolSeeder`, `ReservationSeeder`, and `ReviewSeeder`. The seed users use demo-only credentials defined in `UserSeeder.php`; **never use them in public or production environments**. The seeders may update existing records and use relative dates, so only seed a **new disposable demo database**. The `storage:link` command makes locally uploaded photos available at `/storage/...`; seeded catalog images are external placeholders.

The Compose PostgreSQL volume is isolated from host PostgreSQL and persists across container restarts. Do **not** run `migrate:fresh` or `docker compose down -v` if you want to preserve it.

## 6. Browser access

- Nuxt: http://localhost:3000
- Laravel: http://localhost:8000
- Laravel health endpoint: http://localhost:8000/up (`bootstrap/app.php` registers `/up`)
- Seeded tool catalog: http://localhost:8000/api/tools
- Seeded categories: http://localhost:8000/api/categories
- PostgreSQL from host: localhost:5433; inside Docker: db:5432

## 7. Configure the actual Nuxt API client

The checked-in `compose.yaml` sets `NUXT_PUBLIC_API_BASE=http://localhost:8000`. `ui/nuxt.config.ts` exposes this as `runtimeConfig.public.apiBase`, and `ui/app/composables/useApi.ts` appends paths such as `/api/login`. Because Laravel prefixes the routes in `routes/api.php` with `/api`, **the base URL must not end with `/api`**.

Browser requests use `http://localhost:8000` as their base URL. If you introduce server-side requests inside the Nuxt container, use `http://backend:8000` as their separate internal base URL (also without `/api`). The current browser client uses Sanctum Bearer tokens for authenticated requests.

**CORS is already configured:** `neighbourlend/config/cors.php` allows the browser origin `http://localhost:3000` for `api/*`. Use the exact host/port in this setup. If you serve Nuxt from another origin, update `allowed_origins` in that file and clear the Laravel config cache. Check the registered API paths with `docker compose exec backend php artisan route:list --path=api`.

## 8. Run automated tests without touching development data

The backend integration tests require PostgreSQL `neighbourlend_test` and may reset database tables. Create that database **once** (skip if it already exists):

```powershell
docker compose exec db sh -c 'createdb -U "$POSTGRES_USER" neighbourlend_test'
```

Run Laravel's complete test suite with explicit isolation settings:

```powershell
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=pgsql -e DB_DATABASE=neighbourlend_test backend php artisan test
```

Never substitute `neighbourlend` for `neighbourlend_test`. GitHub Actions runs these tests against its own disposable PostgreSQL test database on pull requests to `main`.

## Common commands

```powershell
docker compose up -d              # start
docker compose down               # stop, keeping database volume
docker compose ps                 # status
docker compose logs -f backend    # API logs
docker compose logs -f frontend   # UI logs
docker compose exec -T -e APP_ENV=testing -e DB_CONNECTION=pgsql -e DB_DATABASE=neighbourlend_test backend php artisan test
docker compose exec backend php artisan route:list --path=api
docker compose exec backend php artisan migrate:status
docker compose exec frontend npm run build
```

On a dependency change, explicitly reinstall:

```powershell
docker compose exec backend composer install
docker compose exec frontend npm ci
```

If an image build fails due to a missing PHP extension, inspect `docker/php/Dockerfile` and `neighbourlend/composer.json`. Docker Compose does not automatically import existing host PostgreSQL data or execute seeders: for a new demo environment, follow step 5 explicitly. This configuration is for local development, not production.

## .gitignore

Ensure your project ignores root `.env`, Laravel `.env`, Nuxt `.env`, and any local credentials. Commit the template `.env.docker.example`, `compose.yaml`, and Dockerfile; NEVER commit your actual secrets.
