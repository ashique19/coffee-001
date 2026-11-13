# Operations: backups & restore

## Database

- **Production:** nightly encrypted upload via `backup:run` — see [BACKUP.md](BACKUP.md).
- Take daily logical backups of the platform MySQL schema (all tenants).
- Retain at least **14 days** on-site and **30 days** off-site (object storage).
- Test a restore quarterly on a staging pod.

## Application files

- `storage/app` (uploaded logos, exports) must be included in filesystem snapshots or rsync jobs.
- `public/build` is committed in this repo; no separate artifact bucket required for front-end assets.

## ZATCA material

- Back up `egs_devices`, `zatca_credentials`, and issued `invoices` together — restore order matters for hash chain integrity.
- Never edit issued invoice rows; use credit notes after restore if corrections are needed.

## Runbook

1. Stop queue workers and cron (`loyalty:expire-points`, `zatca:retry-submissions`).
2. Restore MySQL from dump; verify `migrations` table matches codebase.
3. Restore `storage/app` if used.
4. Warm `/healthz` (expect `"database": true`) before re-enabling workers.
