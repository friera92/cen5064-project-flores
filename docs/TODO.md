# NeighbourLend — Pending Tasks

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
