# NeighbourLend — Final phase files (November 21, 2026)

This package is **not** a GitHub commit. No files have been pushed to GitHub.

## Contents

| File | Purpose |
| --- | --- |
| `docs/TODO.md` | Replaces the outdated December schedule with the confirmed November 22 deadline, actual issue states and a small future-scope section. |
| `docs/FINAL-DELIVERY-VERIFICATION.md` | Provides final release commands, browser scenarios and honest PASS/FAIL/NOT RUN evidence fields. |
| `.github/workflows/docker-integration.yml` | Proposed Docker Compose CI job for #41; its success must be verified in GitHub Actions. |
| `neighbourlend/tests/Feature/AdminReservationParticipationTest.php` | Adds two HTTP regression cases for #39 to verify an admin cannot be a borrower or act on participant reservations. |

## Apply to your checkout

1. From the repository root, run `git status --short` and confirm you are on the intended branch. The administrative changes are on `feature/admin-disputes-inspection` as of this package. If there are local edits, preserve them before overwriting `docs/TODO.md`.
2. Download the ZIP and extract it **to the repository root**. The ZIP contains the actual relative paths above, plus this README.
3. The previously committed proposal **must be removed from the repository** to keep it out of the final submission:

   ```bash
   git rm docs/business-rules/dispute-resolution-v1.md
   ```

   If the file is not tracked on your current branch, skip that command. Git will not retain the now-empty `business-rules` directory.
4. Verify changes:

   ```bash
   git status --short
   git diff --check
   git diff -- docs/TODO.md
   ```

5. Run the isolated backend tests (existing database `neighbourlend_test` required):

   ```bash
   docker compose exec -T \
     -e APP_ENV=testing \
     -e DB_CONNECTION=pgsql \
     -e DB_DATABASE=neighbourlend_test \
     backend php artisan test
   ```

6. Review `docs/FINAL-DELIVERY-VERIFICATION.md` and record actual manual and CI results. Push a PR to `main` to trigger `.github/workflows/docker-integration.yml`. Do **not** mark #41 complete until that workflow passes.

## Read-before-merge reminders

- Issue statuses when this package was prepared: #35–#38 and #40 **closed**, #39/#41/#42 **open**. Update issue states only after the actual acceptance checks pass.
- The existing CI workflow `.github/workflows/ci.yml` is deliberately unchanged.
- The advanced dispute resolution system (formal testimony, compensation, infractions, account suspensions, appeals) is **out of scope** for this submission. Current DISPUTED/inspection-note visibility remains supported.
- As of the review, `main` was at `35d5a2e3f927a6aea3b459dbcddbfc1b7b9c9fb9`, while `feature/admin-disputes-inspection` included later commits. **Confirm the actual merge state again before delivery.**
- CI workflow syntax and PHP syntax were checked, but the container environment and GitHub Actions runner were not available in the file-generation environment. No claim of a green workflow is made.
