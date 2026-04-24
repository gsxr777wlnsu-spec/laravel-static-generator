# Laravel Static Generator

## Setup

```bash
composer run setup   # install deps, copy .env, migrate, npm install, build
composer run dev     # runs: php artisan serve + queue:listen + pail + npm run dev
composer run test     # clears config cache, then runs php artisan test
npm run build        # vite build for frontend assets
```

## Tech Stack

- Laravel 13 (PHP 8.3)
- React 19 frontend via Vite + Tailwind CSS v4 (`@tailwindcss/vite`)
- Blade views for static HTML generation + React for admin UI
- MySQL (production), SQLite in-memory (tests)
- Queue: database driver; Jobs: `GenerateSiteJob`, `DeploySiteJob`, `GenerateWebPJob`
- Horizon for queue monitoring

## Storage

Three custom disks in `config/filesystems.php`:
- `generated` — output for generated HTML (root: `GENERATED_DISK_ROOT` env, default `generated/`)
- `staging` — build artifacts before deploy (root: `STAGING_DISK_ROOT` env, default `staging/`)
- `sites` — static site source files (root: `SITES_DISK_ROOT` env, default `sites/`)

Generated files go to `generated/site{siteId}/`. Jobs auto-commit to git on generate.

## Architecture

- **Contract-based DI**: All services/repos have interfaces bound in `RepositoryServiceProvider`
- **`app/Services/`** — business logic: `HtmlGeneratorService`, `DeployService`, `SftpClient`, `SeoService`, etc.
- **`app/Repositories/`** — data access layer
- **`app/Models/`** — Site, Page, Section, Media, TemplateSet, Deployment, AuditLog, User
- **`app/Contracts/`** — interface definitions

## Routing

- `routes/web.php` — admin panel routes (auth-protected, prefix `/admin`)
- `routes/api.php` — REST API (auth + throttle:api, prefix `/api`)

Key API endpoints: `POST /api/sites/{id}/generate`, `POST /api/sites/{id}/deploy`, `POST /api/preview/{token}/{path?}`.

## Testing

Tests use SQLite in-memory, sync queue, array cache/session. Config overrides in `phpunit.xml`.

## Frontend

- `resources/js/app.jsx` + `resources/css/app.css` — Vite entry points
- React components live in `resources/js/components/`
- Admin views in `resources/views/admin/`, templates in `resources/views/templates/`

## Import

Import functionality allows importing site content from YAML/MD files:

- `POST /api/import` — upload `.md`, `.yaml`, or `.txt` file to import site/pages
- `GET /api/import/templates` — list available import templates
- Import templates stored in `storage/import/templates/{templateName}/`
- Import data stored in `storage/import/md/{siteName}/` with YAML frontmatter including `domain` key
- Import button added to `/admin/sites` page next to "Create Site"