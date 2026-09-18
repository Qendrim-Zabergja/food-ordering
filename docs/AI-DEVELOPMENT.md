# Food Ordering — AI-Assisted Development & Technical Record

The decisions taken while building a Laravel 13 REST API and React 19 single-page
application, and the reasoning behind each.

| | |
|---|---|
| **Stack** | Laravel 13 · React 19 · MySQL 8 |
| **Tests** | 114 tests, 330 assertions |
| **Development** | Developed with AI assistance |

The application lets customers browse a menu by category, build a cart, place an
order and follow its status, while administrators manage the menu and move orders
through their lifecycle. It is a monorepo: a Laravel REST API in `apps/backend`
and a React single-page application in `apps/frontend`, sharing nothing but the
HTTP contract between them.

This record covers the decisions that shaped it. Each section states a question
that came up, the direction taken, and what it produced in the codebase.

Development used AI assistance. This document summarises the key AI-assisted
decisions and their outcomes. The original AI conversation is submitted
separately, and is also included in this repository as
[ai-chat-transcript.html](ai-chat-transcript.html).

---

## 01 — Architecture settled before implementation

Four decisions were made before any feature code was written, each recorded with
its trade-off rather than left as an unexamined default.

| Question | Chosen | Reasoning |
|---|---|---|
| Authentication | **Sanctum bearer tokens** | Stateless; no CSRF handshake or cookie-domain constraint between ports |
| Cart location | **Server-side** | Prices validated on the server; a client cart means trusting the browser |
| Permission tiers | **VIEW / MANAGE** | The standard permits omitting BROWSE where no autocomplete needs it |
| Who can order | **Registered users** | Makes "own order history" a scoping rule, not a guest-lookup flow |

Bearer tokens are a deliberate departure from Laravel's own recommendation for
first-party single-page applications, so it was written up as one of eight
recorded deviations from the house engineering standards rather than left
undocumented.

---

## 02 — The client–server boundary

> **Direction**
>
> Before building on top of this, set out exactly how the frontend and backend
> communicate, and what does and does not cross the boundary.

| Stays server-side | Crosses to React |
|---|---|
| Numeric `id` columns | `uuid`, serialised as `id` |
| Price calculation and order totals | The computed total, read-only |
| Permission checks (Policies) | A permissions array, for hiding UI only |
| Status transition rules | The current status |

The third row became a rule the rest of the project follows: client-side
permission checks are cosmetic, and the Policy on the server is the security
boundary. Tests assert that a customer receives `403` on write endpoints
regardless of what the interface shows.

The same principle governs money. Prices are stored as integer cents and every
total is calculated server-side; a client that posts a price is ignored, which is
the reason the cart lives in the database rather than the browser.

---

## 03 — The order lifecycle

> **Direction**
>
> I am considering reducing the order statuses to Confirmed, Preparing and
> Completed. Make the case for or against before changing anything.

The case against removing `pending`: every order is *created* pending, so without
it an order would be born "Confirmed" — the restaurant accepting it before anyone
had looked, with no state for "ordered, not yet decided" and therefore no way to
reject one. It also drives customer cancellation, which is permitted while an
order is pending or confirmed and not after.

All five were kept. The follow-up concerned how the interface presented them:

> **Direction**
>
> Keep the five, but an administrator only ever sees three options. The interface
> should show the whole lifecycle and where an order currently sits in it.

The three buttons were the next *legal* steps rather than the status list — but
the observation held: nothing showed the pipeline as a whole.

**Produced:** `pipeline()`, `step()` and `hasReached()` on the `OrderStatus`
enum; `step`, `total_steps` and a `progress` array in the API response; a
progress indicator on the order page and a "Step 3 of 5" column in the table.
Cancelled is modelled as leaving the pipeline rather than completing it, so a
cancelled order shows no misleading 5-of-5 bar.

The transition rules live only on the server. The React components render
`allowed_transitions` exactly as the API sends them, so changing the lifecycle
requires no frontend change at all.

---

## 04 — Consolidating the orders screens

> **Direction**
>
> The separate Admin section for orders looks redundant. An administrator should
> open Orders, select an order and change its status there, next to the items and
> delivery details.

Investigating it surfaced a defect: `/api/orders` already returned **every** order
to a user holding `manage-orders`, because the controller unscopes for that
permission — but the page heading still read "Your orders". The admin screen
existed only to display the same rows under a correct heading.

**Produced:** the duplicate screen removed. One orders page that adapts to the
viewer — customer column, status filters and a "Manage" action for
administrators, a plain list for customers. Status transitions moved onto the
order detail page. No backend change was required, because the API had been
scoping correctly the whole time.

---

## 05 — An operational view

> **Direction**
>
> Add a filter to the orders page for administrators: orders placed today whose
> status is not completed.

Built as two composable query filters rather than one fixed flag:

```
?filter[placed_on]=today
?filter[exclude_status][]=completed
```

"Placed today" and "not finished" are independent questions, so a single combined
filter would have meant a new endpoint for the next variation. Administrators
land on this view by default, since it is the reason to open the page during
service. `cancelled` is excluded alongside `completed`, as the view answers what
is still to do and a cancelled order is as finished as a delivered one.

Six tests cover it, including one asserting that a customer using the same
filters is still scoped to their own orders — the filter cannot be used to widen
access.

---

## 06 — Extending the permission model

> **Direction**
>
> Document what is involved in adding a new role and granting it permissions,
> including anything that could go wrong.

Adding a role is an enum case, two `match` arms the compiler will not let you
skip, and a seeder run. Four constraints were documented alongside it:

1. `hasPermissions()` requires all permissions to come from a **single** role, so
   two narrow roles do not combine into a broad one
2. `defaultPermissions()` grants but never revokes
3. `permissions:sync` grants new permissions only to full-access roles
4. Roles are defined in code rather than managed at runtime — a scope decision,
   recorded as such

The first is the one that would cause a real bug, and it is covered by a test
asserting that a user holding two narrow roles is refused the combination.

---

## 07 — Issues found during development

### A server error the test suite could not reach

With the suite passing at 77 tests, a request from a browser returned:

```
GET /api/products  (Accept: */*)
→ 500  "Route [login] not defined."
```

Laravel's `Authenticate` middleware redirects unauthenticated users to a login
route, which an API-only application does not have. Laravel's test client always
sends `Accept: application/json`, which takes the JSON path and never exercises
the redirect — so no test could have caught it. A browser takes the broken path,
meaning every unauthenticated request from the React application would have hit
it.

Found by running the application against a real server rather than relying on a
green suite. Fixed by returning no redirect target for guests, with a regression
test that sends `Accept: */*` explicitly.

### Generated code accepted without verification

A scaffolding tool reported success while having written PHP that would not
parse. Eleven further defects were found in its output, including `uuid` in
`$fillable`, which would have allowed a client to choose its own identifier. All
of it was rewritten against the project's conventions.

A tool's own success report is not evidence. Checking that the project still
parsed afterwards is what caught it.

### Two interface defects found by inspection

The signed-in user's name was styled as a header link and read as a fourth
navigation button; it became an account block pinned to the right of the header.
Separately, the orders page greeted an administrator with "Your orders" while
showing everyone's. Neither was detectable by automated checks — both needed
someone looking at the running application and noticing it communicated the wrong
thing.

---

## Verification

| Check | Result |
|---|---|
| Backend test suite (Pest 4) | 114 tests, 330 assertions, passing |
| Backend formatting (Pint) | Clean |
| Frontend type check and build | Clean |
| Frontend lint (ESLint, react-hooks, typescript-eslint) | Clean |
| Migrations against MySQL 8 | `migrate:fresh --seed` verified |
| End-to-end over HTTP | Register → browse → cart → checkout → order → status lifecycle |
| Browser | Login, cart quantities, checkout validation, order history, permission refusals |

Tests assert behaviour that could actually regress, not only happy paths:

- A numeric id in a URL returns **404** — UUID route binding cannot be bypassed
- A `uuid` supplied in a create payload is **ignored**
- A client posting `price`, `price_cents` and `unit_price_cents` is charged the
  product's real price
- An unknown filter key does **not** widen a result set
- A `PATCH` of one field does not clear the others
- Two narrow roles do **not** combine into a broad one
- A customer receives **403** on another user's cart item and order
- A cancelled order is treated as leaving the pipeline, not completing it

---

Eight deviations from the house engineering standards are documented with their
reasoning in the project's [CLAUDE.md](../CLAUDE.md). The original AI
conversation this record summarises is at
[ai-chat-transcript.html](ai-chat-transcript.html).
