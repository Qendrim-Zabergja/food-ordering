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

Six steps from a clean clone. MySQL must be running before step 4.

**1. Install dependencies** — both applications:

```bash
npm install
```

```bash
composer install --working-dir=apps/backend
```

**2. Create the API environment file** and generate its key:

```bash
cp apps/backend/.env.example apps/backend/.env
```

```bash
php apps/backend/artisan key:generate
```

On Windows PowerShell, use `Copy-Item` instead of `cp`.

**3. Create the frontend environment file** — one line, telling React where the
API is:

```bash
echo "VITE_API_URL=http://localhost:8000/api" > apps/frontend/.env
```

**4. Create the database.** The default credentials in `.env.example` are
`root` with no password, matching a standard Laragon or XAMPP install — change
`DB_USERNAME` and `DB_PASSWORD` in `apps/backend/.env` if yours differ:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS food_ordering CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**5. Run the migrations and seed the data:**

```bash
npm run fresh
```

This runs `migrate:fresh --seed`, which creates every table, registers the
permissions, creates the `admin` and `customer` roles, seeds a 22-item menu, and
creates the two accounts listed below.

**6. Start both applications:**

```bash
npm run dev
```

The API runs on `http://localhost:8000` and the interface on
`http://localhost:5173`. Open the interface and sign in with one of the demo
accounts.

## Demo accounts

Created by step 5. Sign in with either:

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@food-ordering.test` | `password` |
| Customer | `customer@food-ordering.test` | `password` |

Registering through the application always creates a customer — the role is
never read from the request.

## Commands

```bash
npm run dev            # both applications
npm run dev:backend    # API only, http://localhost:8000
npm run dev:frontend   # interface only, http://localhost:5173

npm run test:backend   # Pest test suite
npm run lint           # ESLint over the React app
npm run build          # production build of the interface

npm run migrate        # run pending migrations
npm run fresh          # drop everything, migrate and re-seed
```

After adding or removing a permission, sync the database with the
`PermissionSlug` enum:

```bash
php apps/backend/artisan permissions:sync --dry-run   # preview
php apps/backend/artisan permissions:sync             # apply
```

## Conventions

Engineering standards for this project are documented in [CLAUDE.md](CLAUDE.md).
Read it before contributing — the model naming, UUID and authorization rules are
non-negotiable.

## Technical record

The decisions taken during development and the reasoning behind each are in
[docs/AI-DEVELOPMENT.md](docs/AI-DEVELOPMENT.md), with an HTML version at
[docs/ai-chat-transcript.html](docs/ai-chat-transcript.html).

Development used AI assistance; the unedited session log is submitted alongside
the project.
