# Food Ordering

A food ordering web application: customers browse products by category, build a cart,
place an order and follow its status. Administrators manage products, categories and
incoming orders through an admin panel.

Monorepo containing a Laravel REST API and a React single-page application.

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13 (PHP 8.3), MySQL 8 |
| Auth | Laravel Sanctum, bearer API tokens |
| Frontend | React 19, TypeScript, Vite 8 |
| Routing / data | React Router, TanStack Query, axios |
| Testing | Pest 4 (backend) |

## Structure

```
food-ordering/
├── apps/
│   ├── backend/   Laravel REST API    → http://localhost:8000
│   └── frontend/  React SPA           → http://localhost:5173
└── package.json  npm workspace root
```

The two applications are fully decoupled. React never touches the database; it
communicates with Laravel exclusively over JSON HTTP under `/api`.

## Requirements

- PHP 8.3 – 8.5
- Composer 2
- Node 20.19+ / 22.13+ / 24+ (Node 23 works but emits engine warnings)
- MySQL 8

## Setup

Clone, then install both applications:

```bash
npm install
```

```bash
composer install --working-dir=apps/backend
```

Create the API environment file and generate a key:

```bash
cp apps/backend/.env.example apps/backend/.env && php apps/backend/artisan key:generate
```

Create the database (MySQL must be running):

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS food_ordering CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Run migrations and seed permissions, roles and demo data:

```bash
npm run fresh
```

Point the frontend at the API — create `apps/frontend/.env`:

```ini
VITE_API_URL=http://localhost:8000/api
```

## Running

Both applications at once:

```bash
npm run dev
```

Or separately:

```bash
npm run dev:backend    # http://localhost:8000
npm run dev:frontend   # http://localhost:5173
```

## Other commands

```bash
npm run lint           # ESLint over the React app
npm run test:backend   # Pest test suite
npm run migrate        # run pending migrations
```

After any change to permissions, sync them:

```bash
php apps/backend/artisan permissions:sync --dry-run   # preview
php apps/backend/artisan permissions:sync             # apply
```

## Conventions

Engineering standards for this project are documented in [CLAUDE.md](CLAUDE.md).
Read it before contributing — the model naming, UUID and authorization rules are
non-negotiable.
