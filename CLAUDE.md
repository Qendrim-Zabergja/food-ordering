# Food Ordering — Monorepo

## Project identity
- **Name:** Food Ordering
- **Stack:** Laravel 13 REST API (PHP 8.3, MySQL 8) + React 19 SPA (TypeScript, Vite)
- **Project root:** C:\laragon\www\food-ordering\
- **Repository:** single monorepo, both applications versioned together

## Global standards
Read all global standards before starting any task:
- C:\laragon\www\claude-context\CLAUDE.md

All **backend** rules in the global standards apply to this project in full.
Documented deviations are listed at the bottom of this file — nothing else is optional.

---

## Monorepo structure

```
food-ordering/
├── apps/
│   ├── backend/   Laravel 13  — the REST API, port 8000
│   └── frontend/  React 19    — the SPA, port 5173
├── package.json  npm workspace root (workspaces: apps/frontend)
└── CLAUDE.md     this file
```

`apps/backend` is **not** an npm workspace — it is a PHP application. Laravel's own
`package.json` is unused; this API serves JSON only and has no Vite assets.

### Scaffold tool base_path
The developer-mcp scaffold tools expect a Laravel root. Pass
`C:\laragon\www\food-ordering\apps` as `base_path` and `backend` as the project —
artisan and composer.json live at `apps/backend/`.

`scaffold_vue` does **not** apply to this project. The frontend is React.

---

## Backend (apps/backend)

### Domain organisation
Models are organised by domain, never flat:

```
app/Models/
├── Products/   Product, ProductCategory
├── Carts/      Cart, CartItem
└── Orders/     Order, OrderItem
```

Controllers, Resources, Requests, Filters and Policies mirror the same domain folders:

```
app/Http/Controllers/Auth/
app/Http/Controllers/Products/
app/Http/Controllers/Carts/
app/Http/Controllers/Orders/
```

Every model gets `bigIncrements('id')` + `uuid('uuid')`, the `HasUuid` trait,
`SoftDeletes`, and `getRouteKeyName()` returning `'uuid'`. The numeric `id` is
never exposed — Resources always emit `'id' => $this->uuid`.

### Authentication
Laravel Sanctum, **bearer API tokens** (not SPA cookie auth).

- `POST /api/auth/register` — creates a customer, returns a token
- `POST /api/auth/login` — returns `plainTextToken`
- `POST /api/auth/logout` — revokes the current token
- `GET  /api/auth/me` — the authenticated user

React stores the token and sends `Authorization: Bearer <token>`. Because no
cookies are involved, `supports_credentials` stays `false` in `config/cors.php`
and there is no CSRF handshake.

### Permissions
Two-tier VIEW / MANAGE (no BROWSE — see deviations).

- `app/Enums/PermissionSlug.php` — every permission constant, no raw strings anywhere
- `database/seeders/PermissionSeeder.php` — slug, group, order
- `app/Console/Commands/SyncPermissionsCommand.php` — run after every permission change

```
view-products            manage-products
view-product-categories  manage-product-categories
view-orders              manage-orders
```

Roles: `admin` (all permissions) and `customer` (own orders and cart only).

After adding or removing any permission:

```bash
php apps/backend/artisan permissions:sync --dry-run   # preview
php apps/backend/artisan permissions:sync             # apply
```

### API conventions
- Endpoints are plural kebab-case: `/api/products`, `/api/product-categories`
- `$this->authorize()` at the start of every controller method — no exceptions
- `loadRelationships($model, $request)` before returning from `show`, `store`, `update`
- Never `->with()` in a controller
- Always `$request->validated()`, never `$request->all()`
- Index endpoints use the injected `{Entity}Filters` class

---

## Frontend (apps/frontend)

The global `frontend.md` standards target Vue 3 + `@besa-solutions/besa-components`
and therefore **do not apply** to this project. The underlying principles carry over
to React as follows:

| Global rule (Vue) | React equivalent in this project |
|---|---|
| Extend the base `Model` class | Typed model classes in `src/models/`, hydrated from responses |
| Never use raw response objects | `response.data.map((item) => new Product().hydrate(item))` |
| Use includes enums, not string literals | `ProductIncludes` enum per model |
| Navigate via `RouteName` enum | `RouteName` enum, never raw path strings |
| `authStore.userModel?.can()` | `useAuth().can(PermissionSlug.MANAGE_PRODUCTS)` |
| Never plain JavaScript | TypeScript only — unchanged, applies in full |

```
src/
├── api/          axios instance + per-entity request functions
├── components/   organised by feature
├── enums/        PermissionSlug, RouteName, OrderStatus
├── models/       Product, ProductCategory, Cart, CartItem, Order, OrderItem
├── hooks/        TanStack Query hooks, one file per entity
├── pages/        route components
└── lib/          shared utilities
```

Frontend permission checks hide UI only. Every one of them is backed by a Policy
on the server — the backend check is the security boundary, the frontend check is
cosmetic.

---

## Environment

```bash
npm run dev            # both applications
npm run dev:backend    # http://localhost:8000
npm run dev:frontend   # http://localhost:5173
npm run test:backend   # Pest
npm run lint           # ESLint
```

Database: `food_ordering` on MySQL 8 (start MySQL from the Laragon panel).

---

## Documented deviations from the global standards

Each of these is a deliberate decision, not an oversight.

**1. React instead of Vue 3 + besa-components.**
Required by the project brief. `standards/frontend/*` does not apply; the mapping
table above governs the frontend instead.

**2. No BROWSE permission tier.**
`standards/backend/permissions.md` states: *"If the project uses only VIEW/MANAGE
(no BROWSE), do not retrofit BROWSE unless the autocomplete separation is actually
needed."* This application has no autocomplete or dropdown that needs a restricted
data projection, so the BROWSE tier and `BaseResource::$browseProperties` split are
omitted. Add them if that use case appears.

**3. `OrderStatus` as a PHP backed enum, not a model.**
`model-naming.md` shows `class OrderStatus extends Model {}` as correct naming for
a status model. Here the status set is fixed in code
(`pending → confirmed → preparing → delivering → completed`, plus `cancelled`),
is never user-editable, and drives transition rules that live in PHP. A backed enum
on a string column expresses that better than a lookup table.

**4. Native PHP backed enums instead of `BenSampo\Enum\Enum`.**
`permissions.md` shows `final class PermissionSlug extends Enum` using the
bensampo/laravel-enum package. This project uses native PHP 8.1 backed enums
(`enum PermissionSlug: string`) instead: no third-party dependency, no Laravel 13
compatibility question, and real type safety at call sites rather than loose
string constants. The invariant the standard protects — permission strings are
never raw literals — is unchanged. Enum cases are compared by `->value` when a
string is required.

**5. No `SoftDeletes` on the `Permission` model.**
`models.md` states that all main entity models use SoftDeletes. Permissions are
not user-managed data — they are defined by the `PermissionSlug` enum and
reconciled by `permissions:sync`, which prunes rows for cases that no longer
exist. A soft-deleted row would keep occupying its unique `slug` and collide if
that permission were ever reintroduced, so pruning is a hard delete. `Role`,
which users do manage, keeps SoftDeletes.

**6. `auth/register` and `auth/login` are not behind `$this->authorize()`.**
`controllers.md` requires an `authorize()` call at the start of every controller
method and allows no unprotected endpoints. These two cannot comply: their
purpose is to issue the token every other endpoint requires. They are protected
by rate limiting instead (`throttle:6,1`), registration always assigns the
`customer` role and never reads a role from the payload, and login returns one
identical error for a wrong password and an unknown address so it cannot be used
to enumerate accounts. Every other endpoint in the API is inside the
`auth:sanctum` group and calls a Policy.
