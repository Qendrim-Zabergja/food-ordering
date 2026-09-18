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

**1. Clone the repository**

```bash
git clone https://github.com/Qendrim-Zabergja/food-ordering.git
cd food-ordering
```

**2. Install the frontend dependencies** (from the repository root)

```bash
npm install
```

**3. Install the backend dependencies**

```bash
cd apps/backend
composer install
```

**4. Create the two environment files**

In `apps/backend/`, copy the example file:

```bash
cp .env.example .env
```

In `apps/frontend/`, create a `.env` file containing one line:

```ini
VITE_API_URL=http://localhost:8000/api
```

**5. Generate the application key** (in `apps/backend/`)

```bash
php artisan key:generate
```

**6. Create the database**

Start MySQL, then create an empty database named `food_ordering`. The
credentials in `.env` default to user `root` with no password — change
`DB_USERNAME` and `DB_PASSWORD` in `apps/backend/.env` if yours differ.

```bash
mysql -u root -e "CREATE DATABASE food_ordering CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

**7. Run the migrations and seed the data** (in `apps/backend/`)

```bash
php artisan migrate --seed
```

This creates every table, registers the permissions and roles, seeds the menu,
and creates the two accounts listed below.

**8. Start the API** (in `apps/backend/`)

```bash
php artisan serve
```

Leave it running on `http://localhost:8000`.

**9. Start the frontend** — in a second terminal

```bash
cd apps/frontend
npm run dev
```

Open `http://localhost:5173`.

## Accounts

Sign in with either:

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@food-ordering.test` | `password` |
| Customer | `customer@food-ordering.test` | `password` |

Registering through the application always creates a customer account.

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
