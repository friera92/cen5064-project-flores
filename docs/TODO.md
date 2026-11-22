# NeighbourLend — Final Delivery Status & Follow-up

**Status date:** November 21, 2026  
**Final submission deadline:** November 22, 2026 (confirmed by the project team; verify the submission time in Canvas).  
**Tracking:** [NeighbourLend — Final Delivery board](https://github.com/users/friera92/projects/2/views/1) and issues [#35–#42](https://github.com/friera92/cen5064-project-flores/issues).  
**Scope freeze:** Complete and verify the existing lender/borrower workflow and administrative read-only dispute visibility. Do not implement advanced dispute resolution before the final submission.

## 1. Current issue register

The following states were checked against GitHub on November 21, 2026. **Closed** reflects the GitHub issue status; it does not substitute for the final end-to-end validation in #42. An open issue must not be reported as complete until its acceptance criteria have been verified.

| Issue | Deliverable | GitHub state | Delivery assessment / remaining evidence |
| --- | --- | --- | --- |
| [#35](https://github.com/friera92/cen5064-project-flores/issues/35) | Lender reservation list, detail access, approval API | **Closed** | Implemented; included in reservation HTTP test suite. |
| [#36](https://github.com/friera92/cen5064-project-flores/issues/36) | Handoff, return, inspection, closure APIs | **Closed** | Implemented; state transitions and ownership tests exist. |
| [#37](https://github.com/friera92/cen5064-project-flores/issues/37) | Lender reservation management UI | **Closed** | Implemented; final browser walkthrough still part of #42. |
| [#38](https://github.com/friera92/cen5064-project-flores/issues/38) | Borrower return and lender inspection/closure UI | **Closed** | Implemented; inspection notes and DISPUTED display included. |
| [#39](https://github.com/friera92/cen5064-project-flores/issues/39) | Reservation HTTP and authorization test coverage | **Open** | Existing `ReservationLenderApiTest`, `LoanLifecycleApiTest`, `ReservationApiFlowTest`, `ReservationCancellationApiTest`, and `InspectionNotesApiTest` cover most acceptance criteria. Confirm all tests pass against `neighbourlend_test`, including final admin-participation regression tests, and record CI evidence before closing. |
| [#40](https://github.com/friera92/cen5064-project-flores/issues/40) | UML, documentation, setup instructions | **Closed** | README, `SETUP-LOCAL.md`, and design documentation already available. Recheck their accuracy during #42. |
| [#41](https://github.com/friera92/cen5064-project-flores/issues/41) | Isolated Docker Compose CI smoke workflow | **Open** | Proposed `.github/workflows/docker-integration.yml` prepared for review; requires an actual green GitHub Actions run on a PR before closing. |
| [#42](https://github.com/friera92/cen5064-project-flores/issues/42) | Clean setup, end-to-end borrower/lender demo, release verification | **Open** | Use `docs/FINAL-DELIVERY-VERIFICATION.md`; capture reproducible evidence and any remaining limitations before closing. |

### Additional administrative functionality implemented

The administrative dashboard (`/admin`), category management, global read-only DISPUTED listing, inspection notes and user/role restrictions were implemented in commit [`099edea`](https://github.com/friera92/cen5064-project-flores/commit/099edea863348f2d12149b428e5d5e21c548aa7c) on the `feature/admin-disputes-inspection` branch. The team reports that its automated tests passed and the DISPUTED flow with notes was successfully exercised manually. **Verify its merge into `main` and final CI status separately**; a commit existing on a feature branch is not equivalent to code being on `main`.

## 2. Final-delivery priorities (deadline: November 22)

1. **#39 — Required:** Run the full Laravel test suite against isolated PostgreSQL and confirm that authorization/state regression coverage passes. Preserve evidence of the command and results.
2. **#42 — Required:** Perform the documented clean-start and role-based browser walkthrough; check API, images, booking conflict, successful closure and DISPUTED flow. Record results and limitations.
3. **#41 — Medium priority:** Integrate the Docker Compose CI workflow and confirm it passes in GitHub Actions. If it cannot be verified before the deadline, mark it explicitly as incomplete follow-up; never claim that an unrun workflow passed.
4. Confirm Nuxt build and existing Laravel/Nuxt CI checks are green at the submission commit. Ensure README/UML/setup match the final code and that the feature branch is merged as intended.

The former proposed weekly milestones extending into December are **superseded** by the November 22 final deadline and are intentionally removed from this live checklist.

## 3. Final acceptance checklist

- [ ] **#39:** Entire Laravel suite passes against `neighbourlend_test`, not the development database.
- [ ] **#39:** API tests cover lender-only approval/handoff/inspection/closure; borrower-only return; invalid transitions; unauthenticated and unrelated users; failed actions leaving data unchanged; overlapping bookings; admin non-participation.
- [ ] **#42:** Laravel API and Nuxt frontend start from a clean checkout with PostgreSQL and documented environment files.
- [ ] **#42:** Demo users, categories and tools are available; no destructive seeding was run on persistent development data.
- [ ] **#42:** Catalog/search, images, authentication and tool ownership are manually checked.
- [ ] **#42:** Borrower request → lender approval → handoff → borrower return → GOOD inspection → CLOSED works in browser.
- [ ] **#42:** DAMAGED/BROKEN/MISSING_PARTS inspection → DISPUTED, inspection notes visible to lender/borrower/admin; no admin resolution action claimed.
- [ ] **#42:** Unauthorized tool/reservation mutations and overlapping dates are rejected.
- [ ] **#42:** Existing code CI and Nuxt production build pass on the proposed release revision.
- [ ] **#41:** Separate Docker CI workflow passes on a PR and cleans up its disposable database/volumes; otherwise document as pending.
- [ ] **#42:** README, `SETUP-LOCAL.md`, UML, ADR links, this TODO and evidence checklist agree with the final commit.
- [ ] **#42:** Capture submission SHA, screenshots/logs, remaining limitations and final submission evidence.

Refer to [Final Delivery Verification](FINAL-DELIVERY-VERIFICATION.md) for commands, manual test cases and the evidence template.

## 4. Risks and controls

| Risk | Mitigation |
| --- | --- |
| Resetting developer data during tests or seeding | Use disposable Docker resources for clean setup and **only** PostgreSQL `neighbourlend_test` for `RefreshDatabase` tests. Never run `migrate:fresh`, `docker compose down -v`, or full demo seeders on valuable development data. |
| Nuxt/ Laravel configuration mismatch | Use `NUXT_PUBLIC_API_BASE=http://localhost:8000`, not a URL ending in `/api`; verify CORS, `DB_HOST=db`, and the Laravel public storage symlink. |
| CI passing but the browser flow failing | Validate real Nuxt ↔ Laravel ↔ PostgreSQL behavior in #42; retain both positive and negative-path evidence. |
| GitHub branch/release mismatch | Confirm all intended commits are actually merged into `main` before claiming a release. |
| Optional CI missing the deadline | Prioritize #39 and #42. Record #41 as pending rather than replacing end-to-end verification with an untested workflow. |

## 5. Explicitly deferred: future dispute resolution

The final version **does** support creation and read-only visibility of DISPUTED reservations, including inspection notes. It **does not** resolve disputes administratively, collect formal evidence/testimonies, calculate or transfer compensation, assign infractions, suspend accounts, or handle appeals.

A future iteration may explore a dispute case model, evidence from both parties, impartial decisions, independent reservation/tool state handling, possible compensation assessments, infractions/suspension and appeals. These are **ideas requiring business review and implementation changes**, not accepted or deployed rules for this release. The administrator remains unable to create, edit or delete equipment or borrow equipment.

No `dispute-resolution-v1.md` policy document is included in the final-delivery scope. Keep proposals outside the submitted repository until their rules have been agreed and implemented.
