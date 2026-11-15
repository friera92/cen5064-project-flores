# NeighbourLend — Pending Tasks

## Final Delivery Plan (proposed)

- **Planning baseline:** November 14, 2026; issue register updated after organizing the final-delivery board.
- **Scope:** NeighbourLend final project; the exact final submission date must be checked against Canvas.
- **Tracking:** [NeighbourLend – Final Delivery (GitHub Project)](https://github.com/users/friera92/projects/2/views/1), linked issues #35–#42, and pull requests to `main`.
- **Board workflow:** Backlog → In Progress → In Review → Done. Move an issue to Done only after acceptance criteria, review, and relevant CI evidence are complete.
- **Board snapshot (November 14, 2026):** [#40](https://github.com/friera92/cen5064-project-flores/issues/40) is In Progress; #35–#39 and #41–#42 are in Backlog. This is an initial snapshot, not a live status indicator.

### Completed baseline (before this plan)

- Reservation transition rules are implemented in `ReservationState` and enforced through `Reservation::transitionTo()`.
- Laravel reservation unit and feature tests have passed locally (24 total tests, 103 assertions across the suite) and passed GitHub Actions checks on the merged reservation-testing PR.
- The equipment catalog and Tools CRUD frontend use the Laravel API rather than the original mock catalog.
- The README's reservation flows use the seven implemented states: `REQUESTED`, `APPROVED`, `ACTIVE`, `RETURNED`, `CLOSED`, `CANCELED`, and `DISPUTED`.

These are baseline accomplishments, **not** claims about work completed during the planned weeks below.

### Final-delivery issue register

All eight issues below are tracked on the public [GitHub Project board](https://github.com/users/friera92/projects/2/views/1). Their acceptance criteria live in the linked issue descriptions; this table summarizes scope, sequencing and initial status.

| Issue | Workstream / deliverable | Priority | Dependencies | Initial board status |
| --- | --- | --- | --- | --- |
| [#35 — Lender reservations and approval API](https://github.com/friera92/cen5064-project-flores/issues/35) | Backend: received-reservations list, owner detail authorization, approval endpoint | High | Existing reservation domain state machine | Backlog |
| [#36 — Handoff, return, inspection and closure APIs](https://github.com/friera92/cen5064-project-flores/issues/36) | Backend: expose and validate `LoanLifecycleService` operations | High | #35 for lender-facing flow; existing lifecycle service | Backlog |
| [#37 — Lender reservation management UI](https://github.com/friera92/cen5064-project-flores/issues/37) | Frontend: incoming requests, owner actions and state feedback | High | #35 | Backlog |
| [#38 — Borrower return and lender inspection/closure UI](https://github.com/friera92/cen5064-project-flores/issues/38) | Frontend: complete reservation state workflow through the API | High | #35, #36; coordinate with #37 | Backlog |
| [#39 — Reservation HTTP and authorization tests](https://github.com/friera92/cen5064-project-flores/issues/39) | Tests: permissions, invalid transitions, state updates, conflict responses | High | Implement alongside #35 and #36 | Backlog |
| [#40 — Final UML, weekly plan and setup documentation](https://github.com/friera92/cen5064-project-flores/issues/40) | Docs: behavioral UML, explicit seeding/CORS/storage setup, accurate plan | High | Current code and accepted issue scope | In Progress |
| [#41 — Docker Compose integration workflow](https://github.com/friera92/cen5064-project-flores/issues/41) | CI: disposable full-stack build, health checks and smoke tests | Medium | Stable Compose setup; #40 documentation review | Backlog |
| [#42 — Final workflow and release verification](https://github.com/friera92/cen5064-project-flores/issues/42) | Validation: clean install, borrower-to-lender demo, CI and submission evidence | High | #35–#40; #41 if completed | Backlog |

### Weekly milestones, dependencies, and deliverables

| Target week | Planned issues and work | Deliverable / verification evidence | Dependencies / risk focus | Target status |
| --- | --- | --- | --- | --- |
| **Nov 16–22, 2026** | Finish [#40](https://github.com/friera92/cen5064-project-flores/issues/40): UML, reproducible README/`SETUP-LOCAL.md`, and weekly plan. Start [#35](https://github.com/friera92/cen5064-project-flores/issues/35): received-reservation and approval API. Begin API tests under [#39](https://github.com/friera92/cen5064-project-flores/issues/39) as routes are added. | Documentation PR and initial backend PR(s), with targeted authorization/state tests and CI results. | Existing domain rules and seed data; R4, R5. | #40: In Progress → In Review → Done after merge; #35/#39: start |
| **Nov 23–29, 2026** | Complete [#35](https://github.com/friera92/cen5064-project-flores/issues/35). Implement [#36](https://github.com/friera92/cen5064-project-flores/issues/36): lender handoff, borrower return, lender inspection/closure HTTP endpoints. Implement [#37](https://github.com/friera92/cen5064-project-flores/issues/37): lender dashboard using real API data. Expand [#39](https://github.com/friera92/cen5064-project-flores/issues/39). | Reviewed backend/frontend PRs and automated endpoint tests; demonstrable REQUESTED → APPROVED → ACTIVE flow with role authorization. | #37 depends on #35; #36 depends on state/lifecycle services; R2, R4, R6. | #35/#36/#37/#39: progress tracked by PR |
| **Nov 30–Dec 6, 2026** | Finish [#38](https://github.com/friera92/cen5064-project-flores/issues/38) and [#39](https://github.com/friera92/cen5064-project-flores/issues/39): return, inspection, closure and dispute display, plus HTTP tests. Complete [#42](https://github.com/friera92/cen5064-project-flores/issues/42): clean install and full borrower–lender demo. Address [#41](https://github.com/friera92/cen5064-project-flores/issues/41) if capacity and course deadline permit. | Full REQUESTED → APPROVED → ACTIVE → RETURNED → CLOSED UI/API demonstration, negative-path tests, setup checklist, green CI, and release evidence. | #38 requires #35/#36; #42 requires the functional and test issues; R1–R6. | Core features and #42: Done when verified; #41: Done or documented follow-up |

**Scheduling rules:** These are *proposed future target weeks*, not historical commit claims. Confirm the actual Canvas final deadline before treating them as commitments. Update issue/board statuses from actual PR evidence; this table is not automatically synchronized with GitHub. If time is tight, prioritize #35–#40 and #42. Defer medium-priority #41 explicitly rather than silently dropping a core borrower/lender flow. Administrative dispute resolution and an optional HTTP 409 refinement remain separate future enhancements unless required for completion.

### Risk register

| ID | Risk | Risk basis | Likelihood / impact | Early warning | Mitigation / fallback |
| --- | --- | --- | --- | --- | --- |
| R1 | Clean setup has an empty catalog or seeders fail on existing data. | Potential | Medium / High | Catalog is empty after migrations, or seeding produces errors. | Document explicit demo seeding on a new isolated database; verify `DatabaseSeeder` dependencies and make seeding repeatable where feasible. |
| R2 | Environment-specific CORS, `/api` prefixes, or storage links break the Nuxt ↔ Laravel integration. | Previously observed | Medium / High | Browser CORS errors, `/api/api` requests, or image 404s. | Verify `config/cors.php`, API base URL without duplicate `/api`, and `php artisan storage:link` in the clean-install checklist. |
| R3 | CI passes automated backend tests and frontend build, but a full Docker stack fails during startup. | Potential | Medium / High | Backend/frontend health endpoints fail despite a green code CI workflow. | Add an isolated Docker Compose smoke workflow with health checks, logs on failure, and disposable volumes; keep existing CI unchanged. |
| R4 | A fix introduces a reservation regression or interacts with the development database. | Potential | Low / High | Reservation workflow assertions fail or the configured DB is not `neighbourlend_test`. | Run the complete automated test suite and isolated PostgreSQL feature tests for every relevant PR; do not run destructive migrations against the development DB. |
| R5 | Documentation or project tracking lags behind merged code. | Identified gap | High / Medium | UML, weekly plan, README, or GitHub board differs from current `main`. | Review documentation as a PR acceptance criterion; link each weekly milestone to issues, PRs, and observable checks. |
| R6 | A task exceeds the final-submission window. | Potential | Medium / High | A milestone is blocked near the Canvas deadline. | Prioritize documentation accuracy, reproducible demo, and green CI; move the Docker integration enhancement or HTTP 409 refinement to explicitly labeled follow-up work if needed. |

**Note:** Risk likelihood and impact are qualitative estimates based on the current project architecture, previously observed development issues, and remaining delivery activities. They do not indicate that the risks have already materialized.

### Final acceptance checklist

- [ ] [#40](https://github.com/friera92/cen5064-project-flores/issues/40): design UML preserves original model properties/relations and shows actual domain/service operations.
- [ ] [#40](https://github.com/friera92/cen5064-project-flores/issues/40): README and `SETUP-LOCAL.md` document a reproducible Docker setup, demo data seeding, CORS, storage links, and correct Nuxt API origin.
- [ ] [#35](https://github.com/friera92/cen5064-project-flores/issues/35): authenticated lender can list/view requests for owned tools and approve REQUESTED reservations.
- [ ] [#36](https://github.com/friera92/cen5064-project-flores/issues/36): authenticated, role-authorized HTTP actions implement handoff, return, inspection and closure without duplicating domain rules.
- [ ] [#37](https://github.com/friera92/cen5064-project-flores/issues/37) and [#38](https://github.com/friera92/cen5064-project-flores/issues/38): lender/borrower UI uses real API data and can complete the normal lifecycle.
- [ ] [#39](https://github.com/friera92/cen5064-project-flores/issues/39): HTTP integration and authorization tests pass on isolated PostgreSQL; Laravel and Nuxt CI checks are green.
- [ ] [#42](https://github.com/friera92/cen5064-project-flores/issues/42): clean-clone walkthrough verifies seeding, catalog, image display and both user roles through CLOSED; known dispute limitations are explicit.
- [ ] [#41](https://github.com/friera92/cen5064-project-flores/issues/41): optional separate Docker CI smoke workflow passes **or** is explicitly carried forward as medium-priority follow-up.
- [ ] Project board and this plan show actual issue/PR states and weekly evidence; do not change previously reviewed PR descriptions retrospectively.
- [ ] New AI-assisted PRs include a specific critique describing tools used, what was checked, what was changed, and what was retained.

---

## CI: Docker Integration Workflow

**Status:** Pending  
**Priority:** Medium  
**Suggested branch:** `ci/docker-integration`  
**Target file:** `.github/workflows/docker-integration.yml`  
**Depends on:** Merging and verifying the Docker Compose local-environment setup on `main`.

### Goal

Add a separate GitHub Actions workflow that verifies the complete NeighbourLend development stack (Nuxt frontend, Laravel API, and PostgreSQL) can build, start, and respond correctly using the repository's `compose.yaml`.

### Acceptance criteria

- [ ] Trigger automatically on pull requests targeting `main`, and allow manual execution with `workflow_dispatch`.
- [ ] Check out the repository and generate CI-only environment variables from example files; do not commit credentials or private `.env` files.
- [ ] Build and start `frontend`, `backend`, and `db` with Docker Compose.
- [ ] Confirm that PostgreSQL is healthy and Laravel connects using `DB_HOST=db` and port `5432`.
- [ ] Generate the Laravel application key and run database migrations.
- [ ] Verify the Laravel health endpoint at `http://localhost:8000/up`.
- [ ] Verify the Nuxt frontend responds at `http://localhost:3000/`.
- [ ] Run relevant Laravel tests and report failures.
- [ ] Print container logs on failure and clean up containers/volumes after the job.
- [ ] Confirm the workflow passes in a pull request before merging into `main`.

### Follow-up (optional)

- [ ] Add an end-to-end smoke test that exercises frontend → Laravel API → PostgreSQL, beyond simple health checks.
- [ ] Configure the Docker workflow as a required status check for merges into `main` after it is stable.

### Notes

- Keep this workflow separate from the existing code CI workflow.
- Use ephemeral CI database credentials and isolated volumes.
- Do not use `migrate:fresh` on a developer's existing database; CI may use a disposable database.
- Ensure the frontend API base URL does not duplicate `/api`.
