# NeighbourLend — local development with Docker Compose

This folder is a starter TEMPLATE based on the assumed repository layout:

```
project-root/
  compose.yaml
  .env                        # copy from .env.docker.example, never commit
  .env.docker.example
  docker/php/Dockerfile
  neighbourlend/              # Laravel; artisan, composer.json
  ui/                         # Nuxt; package.json and package-lock.json
```

Prerequisite: Docker Desktop with Compose. On Windows, enable the WSL 2 backend and Linux containers.

## 1. Copy these files into your repository root

Copy `compose.yaml`, `.env.docker.example`, and `docker/` from the starter kit.

## 2. Create environment files (PowerShell)

From the project root:

```powershell
Copy-Item .env.docker.example .env
Copy-Item .\neighbourlend\.env.example .\neighbourlend\.env
```

If the Laravel `.env` already exists, preserve it rather than overwriting it. Change the PostgreSQL password in the ROOT `.env`. The Laravel container gets `DB_HOST=db` and DB credentials from Compose; your Laravel APP_KEY is generated in `neighbourlend/.env`.

## 3. Build and start all services

```powershell
docker compose config
docker compose up -d --build
docker compose ps
docker compose logs -f backend frontend
```

On first start the backend installs Composer dependencies to a named Docker volume, and the frontend installs npm dependencies (npm ci) to another volume.

## 4. Generate Laravel APP_KEY (one time)

```powershell
docker compose exec backend php artisan key:generate
```

## 5. Migrate the ISOLATED container database

```powershell
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan migrate
```

The Compose Postgres volume is NEW/ISOLATED and does not import your existing host PostgreSQL data. Do not run `migrate:fresh` or `docker compose down -v` if you want to keep this database.

## 6. Browser access

- Nuxt: http://localhost:3000
- Laravel: http://localhost:8000
- Laravel health endpoint (if configured): http://localhost:8000/up
- PostgreSQL from host: localhost:5433; inside Docker: db:5432

## 7. Configure the actual Nuxt API client

The frontend environment variable is `NUXT_PUBLIC_API_BASE=http://localhost:8000/api`.
Nuxt exposes `NUXT_PUBLIC_...` via runtimeConfig when the corresponding `public` key exists in `ui/nuxt.config.ts`:

```ts
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8000/api'
    }
  }
})
```

Wherever your API client calls Laravel, consume `useRuntimeConfig().public.apiBase`; avoid hard-coded old addresses. This browser-facing URL uses `localhost` because requests run in the BROWSER. If doing server-side requests within the Nuxt container, use a separate private base URL `http://backend:8000/api`, because `localhost:8000` inside a container refers to that same container.

Confirm your actual route prefix in Laravel (`php artisan route:list --path=api`) and check Laravel CORS for requests from `http://localhost:3000`. Sanctum bearer-token auth can use Authorization headers; cookie-based auth requires a more involved cross-origin setup.

## Common commands

```powershell
docker compose up -d              # start
docker compose down               # stop, keeping database volume
docker compose ps                 # status
docker compose logs -f backend    # API logs
docker compose logs -f frontend   # UI logs
docker compose exec backend php artisan test
docker compose exec backend php artisan route:list --path=api
docker compose exec backend php artisan migrate:status
docker compose exec frontend npm run build
```

On a dependency change, explicitly reinstall:

```powershell
docker compose exec backend composer install
docker compose exec frontend npm ci
```

If the image build fails due to a PHP extension required by composer.json, extend `docker/php/Dockerfile`. This template is NOT a full production image. Docker Compose does not automatically import your existing PostgreSQL tables/data, nor does it run seeders unless you do so explicitly.

## .gitignore

Ensure your project ignores root `.env`, Laravel `.env`, Nuxt `.env`, and any local credentials. Commit the template `.env.docker.example`, `compose.yaml`, and Dockerfile; NEVER commit your actual secrets.
