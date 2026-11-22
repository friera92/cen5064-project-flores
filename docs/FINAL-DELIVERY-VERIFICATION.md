# NeighbourLend — Final Delivery Verification

**Target submission:** November 22, 2026  
**Status:** Execution checklist — results must be filled in from actual runs  
**Related issues:** [#39](https://github.com/friera92/cen5064-project-flores/issues/39), [#41](https://github.com/friera92/cen5064-project-flores/issues/41), [#42](https://github.com/friera92/cen5064-project-flores/issues/42)

## 1. Release information (fill before submission)

| Field | Recorded value |
| --- | --- |
| Release branch | _Pending_ |
| Final commit SHA | _Pending_ |
| Date/time of verification | _Pending_ |
| Verified by | _Pending_ |
| Laravel/Nuxt CI run URL | _Pending_ |
| Docker integration CI run URL | _Pending / not executed_ |
| Submitted artifact or project URL | _Pending_ |

Record PASS, FAIL, or NOT RUN per item. An unexecuted check is **not** a pass. Attach screenshots, command output, or GitHub Actions URLs where appropriate. Do not include credentials, bearer tokens, patient data, or private user details in evidence.

## 2. Safety and prerequisites

- Verify the actual final submission branch and that the intended changes have been merged. The admin/dispute-inspection work originated in the `feature/admin-disputes-inspection` branch; presence of a commit in GitHub does not imply `main` contains it.
- Use a **new, disposable** clone/checkout and database for clean-install tests whenever possible. Back up any development data before destructive local setup. Do **not** run `migrate:fresh`, `docker compose down -v` or full demo seeders on a database that contains work you want to preserve.
- Docker Desktop/Compose and the repository source are required. Consult `README.md` and `SETUP-LOCAL.md` for environment file instructions.
- The root `.env` defines PostgreSQL credentials; `neighbourlend/.env` defines Laravel configuration. Inside containers Laravel connects to `DB_HOST=db` on port `5432`; the browser uses `http://localhost:3000` (Nuxt) and `http://localhost:8000` (Laravel). Nuxt API base has **no** trailing `/api`.

## 3. Automated tests — #39

Using a disposable PostgreSQL service, create the isolated test database once if it does not exist:

```bash
docker compose exec -T db sh -c 'createdb -U "$POSTGRES_USER" neighbourlend_test'
```

Then run:

```bash
docker compose exec -T \
  -e APP_ENV=testing \
  -e DB_CONNECTION=pgsql \
  -e DB_DATABASE=neighbourlend_test \
  backend php artisan test
```

**Important:** Existing feature tests use `RefreshDatabase`. Check the database name **before** running; never change it to `neighbourlend`.

| Test family | Expected result | Actual | Evidence |
| --- | --- | --- | --- |
| `ReservationApiFlowTest` | Booking/quotes, overlap rejection, cancellation, ownership | NOT RUN | _Link / output_ |
| `ReservationLenderApiTest` | Lender-only listing, detail and approval | NOT RUN | _Link / output_ |
| `LoanLifecycleApiTest` | Full success/failure paths to CLOSED or DISPUTED, tool status | NOT RUN | _Link / output_ |
| `ReservationCancellationApiTest` | Valid/invalid cancellations and reasons | NOT RUN | _Link / output_ |
| `InspectionNotesApiTest` | Notes persisted and visible to the right roles | NOT RUN | _Link / output_ |
| `AdminPanelApiTest` and admin role tests | Admin-only endpoints, no lending/borrowing/equipment mutations | NOT RUN | _Link / output_ |
| Entire test suite | Zero failures, isolated PostgreSQL | NOT RUN | _Total tests/assertions_ |

The regression test `AdminReservationParticipationTest` checks that administrators cannot request tools or mutate participant reservations. The test is not counted as passing until executed.

## 4. Local stack checks — #42

Start a clean/disposable stack using the existing setup instructions:

```bash
docker compose up -d --build
docker compose ps
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan storage:link
```

Apply pending migrations with `docker compose exec backend php artisan migrate`. On **fresh/disposable** databases only, follow `README.md` to seed demo users, categories, tools and reservations. If a storage symlink already exists, the link command may report that it exists; this is not a test failure by itself.

```bash
curl -f http://localhost:8000/up
curl -f http://localhost:8000/api/categories
curl -f http://localhost:8000/api/tools
curl -f http://localhost:3000/
```

| Check | Expected | Actual | Evidence |
| --- | --- | --- | --- |
| PostgreSQL health | `healthy` in Compose | NOT RUN | _Output_ |
| Laravel health/API | HTTP 200, valid categories/tools JSON | NOT RUN | _Output_ |
| Nuxt frontend | HTTP 200, no blocking hydration/runtime error | NOT RUN | _Screenshot/log_ |
| Demo data and login | Lender, borrower and admin can sign in | NOT RUN | _Screenshot_ |
| Catalog search and filter | Results update through Laravel data | NOT RUN | _Screenshot_ |
| Image rendering | Seeded external and uploaded public-storage images render | NOT RUN | _Screenshot_ |
| Tool ownership | Lender manages own tools only; admin cannot publish/edit/delete | NOT RUN | _Screenshot/API_ |

## 5. Browser walkthrough — regular lifecycle

Use one legitimate **lender**, a separate **borrower**, and a tool available for future dates. Keep a record of the reservation ID.

| Step | Action | Expected | Actual |
| --- | --- | --- | --- |
| 1 | Borrower selects dates, checks quote and requests tool | `REQUESTED`; correct estimated total | NOT RUN |
| 2 | Lender views Incoming Reservations | Correct borrower/tool and request | NOT RUN |
| 3 | Lender approves | `APPROVED` | NOT RUN |
| 4 | Lender hands off tool | `ACTIVE`; tool `IN_USE` | NOT RUN |
| 5 | Borrower returns tool | `RETURNED`; tool `INSPECTION` | NOT RUN |
| 6 | Lender inspects with `GOOD` | Inspection saved; reservation remains `RETURNED` | NOT RUN |
| 7 | Lender closes reservation | `CLOSED`; tool `AVAILABLE` | NOT RUN |

**Reservation ID:** _Pending_  
**Evidence:** _Screenshots or screen recording reference_

## 6. Dispute path and authorization checks

| Scenario | Expected | Actual | Evidence |
| --- | --- | --- | --- |
| Lender inspects as `DAMAGED` with notes | Reservation `DISPUTED`, tool `MAINTENANCE`, notes saved | NOT RUN | _Screenshot_ |
| Lender inspects as `BROKEN` or `MISSING_PARTS` | Reservation `DISPUTED`, tool `RETIRED` | NOT RUN | _Screenshot_ |
| Borrower views their disputed reservation | Condition and inspection notes visible | NOT RUN | _Screenshot_ |
| Lender views the same reservation | Condition and inspection notes visible | NOT RUN | _Screenshot_ |
| Admin opens `/admin/disputes` | Dispute listed with details and notes, read-only | NOT RUN | _Screenshot_ |
| Borrower or stranger attempts lender-only action | HTTP 403; no state change | NOT RUN | _API response_ |
| Admin attempts to delete a lender tool | HTTP 403; tool remains | NOT RUN | _API response_ |
| Borrower requests overlapping dates for same tool | Conflict response; no duplicate booking | NOT RUN | _API response_ |
| Invalid condition or illegal transition | Validation/conflict response; no partial write | NOT RUN | _API response_ |

This delivery **does not** include administrative resolution of DISPUTED cases, compensations, infractions, suspensions, or appeals. Do not demonstrate or claim those capabilities.

## 7. CI integration — #41

The new `.github/workflows/docker-integration.yml` is intended for PRs targeting `main` and manual `workflow_dispatch`. On GitHub Actions it should:

1. Generate disposable local CI credentials (without committing `.env` files).
2. Build the three Docker services (`db`, `backend`, `frontend`).
3. Generate the Laravel application key, apply migrations and seed a disposable catalog.
4. Verify database, Laravel `/up`, categories/tools API, and Nuxt `/`.
5. Create a separate `neighbourlend_test` database and execute all Laravel tests there.
6. Print logs on failure and destroy CI containers/volumes afterwards.

| Check | Actual | Evidence |
| --- | --- | --- |
| Existing `Laravel Backend` CI | NOT RUN | _GitHub Actions URL_ |
| Existing `Nuxt Frontend` CI | NOT RUN | _GitHub Actions URL_ |
| Docker Compose integration CI | NOT RUN | _GitHub Actions URL_ |
| Workflow cleanup and failure logs reviewed | NOT RUN | _Run URL / notes_ |

If #41 has not had a successful run before the deadline, report it as **not verified / deferred**, not complete.

## 8. Known limitations and out-of-scope items

- Admin dashboard: category management and read-only global dispute listing are in scope.
- The reservation can transition to `DISPUTED` and inspection notes can be viewed; no administrative `DISPUTED → CLOSED` resolution flow is implemented.
- Formal evidence submissions, testimony tracking, compensation, violations, account suspensions, automated settlements and appeals are **future proposals**, not delivered features.
- The administrator is not a lender/borrower and must not create reservations or manage equipment.

Add any additional limitations actually observed during verification below:

> _None recorded yet; update after executing the checklist._

## 9. Final release decision

- [ ] All essential #39 tests pass in isolated PostgreSQL.
- [ ] Successful and disputed borrower/lender scenarios have evidence.
- [ ] Known limitations are disclosed without suggesting unimplemented features work.
- [ ] Main branch includes intended delivery commits and excludes `docs/business-rules/dispute-resolution-v1.md`.
- [ ] README/setup/UML are consistent with the final branch.
- [ ] GitHub Actions state and issue statuses are accurately reported.
- [ ] Final submission completed before the Canvas deadline.

**Decision:** _Pending_  
**Final verified commit:** _Pending_  
**Notes:** _Pending_
