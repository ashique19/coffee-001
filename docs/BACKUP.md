# Encrypted off-site database backups (Dropbox)

Production runs on **shared cPanel** (PHP 8.3, jailed shell, no root). Nightly backups use `php artisan backup:run`, scheduled by the owner in cPanel.

The application server stores **only the RSA public key**. It cannot decrypt uploaded backups. Restore is performed on the owner’s workstation with the **offline private key**.

## Threat model

| On server | Off server (owner) |
|-----------|-------------------|
| `BACKUP_PUBLIC_KEY_PATH` (PEM) | RSA **private** key PEM |
| `DROPBOX_*` refresh token | Copy of `.env` / `APP_KEY` in a password manager |
| Plaintext DB credentials in `.env` | Decrypted `.sql.gz` during restore |

Treat Dropbox backups like tape archives: they include tenant data, ZATCA credentials, and issued invoices. **ZATCA invoice hash chains** may need retention longer than this job keeps (see [Retention & compliance](#retention--compliance)).

## 1. Generate RSA 4096 keypair (owner machine)

```bash
openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:4096 -out zatca-backup-private.pem
openssl rsa -in zatca-backup-private.pem -pubout -out zatca-backup-public.pem
chmod 600 zatca-backup-private.pem
```

- Upload **only** `zatca-backup-public.pem` to the server **outside the web root** (e.g. `/home/USER/keys/zatca-backup-public.pem`).
- Store `zatca-backup-private.pem` offline (encrypted USB / password manager export). Never commit it.

## 2. Dropbox app (app folder)

1. Create a Dropbox app with **App folder** access.
2. Complete OAuth once to obtain a **refresh token** with scope for `files.content.write` and `files.metadata.read`.
3. Set environment variables on the server (see below). Do not log these values.

Remote path pattern:

`/zatca/YYYY/MM/zatca-YYYYmmdd-HHMMSS.sql.gz.enc`

## 3. Environment variables

```dotenv
# MySQL (standard Laravel DB_* vars) — used via temporary mysqldump defaults file only.

BACKUP_PUBLIC_KEY_PATH=/home/USER/keys/zatca-backup-public.pem
DROPBOX_APP_KEY=
DROPBOX_APP_SECRET=
DROPBOX_REFRESH_TOKEN=

# Optional overrides
# BACKUP_MYSQLDUMP_PATH=/usr/bin/mysqldump
# BACKUP_LOCAL_KEEP=3
```

No SQL migrations are required for this feature.

## 4. cPanel cron (owner)

```cron
0 3 * * * cd ~/zatca.taskeenlab.duckdns.org && php artisan backup:run >> ~/zatca-backup.log 2>&1
```

Stdout lines look like:

`backup ok remote=/zatca/2026/11/zatca-20261112-030015.sql.gz.enc bytes=… sha256=… sql_bytes=… duration_ms=… local=…`

Non-zero exit = failure (check `~/zatca-backup.log` and Laravel logs). Secrets are never printed.

## 5. Encrypted file format (`ZATCABAK1`)

| Section | Size | Description |
|---------|------|-------------|
| Magic | 9 | ASCII `ZATCABAK1` |
| Version | 1 | `0x01` |
| `sealed_key_len` | 4 | Big-endian uint32 |
| `sealed_key` | N | 32-byte AES key wrapped with **RSA-OAEP** (public key on server) |
| IV | 12 | AES-GCM nonce |
| Tag | 16 | AES-GCM auth tag |
| Ciphertext | rest | AES-256-GCM of **gzip**’d mysqldump |

Pipeline: `mysqldump` → `.sql` → **gzip** → encrypt → `.sql.gz.enc`.

`mysqldump` flags: `--single-transaction --routines --no-tablespaces`, credentials via a temp `--defaults-extra-file` (`chmod 0600`, deleted immediately after).

## 6. Restore (owner workstation)

1. Download `.sql.gz.enc` from Dropbox.
2. Decrypt:

```bash
php artisan backup:decrypt /path/to/zatca-20261112-030015.sql.gz.enc --private-key=/secure/zatca-backup-private.pem
# writes zatca-20261112-030015.sql.gz
gzip -dc zatca-20261112-030015.sql.gz | mysql -u ... -p database_name
```

3. Restore `storage/app` if you rely on uploaded assets (separate filesystem backup).
4. Verify `/healthz` and ZATCA hash chain before reopening POS.

## 7. Retention

| Location | Policy |
|----------|--------|
| `storage/backups/` (mode `0700`) | Latest **3** encrypted files |
| Dropbox `/zatca/` | Latest **14** daily backups + **earliest backup per month** for **12** months; older files deleted remotely |

## 8. Monthly restore test

Once per month:

1. Pull the newest remote backup.
2. Decrypt with the offline private key on a non-production machine.
3. Import into a throwaway schema and run smoke tests (login, sample invoice list).
4. Record date and SHA-256 in your ops log.

## Retention & compliance

This job’s remote retention (**≈14 days + 12 monthly anchors**) is for **operational recovery**. **ZATCA issued invoices** may require longer archival under your policies — export periodic encrypted copies or extend Dropbox lifecycle separately. Do not edit issued invoice rows after restore; use credit notes for corrections.

## Deploy checklist

1. Merge PR and `git pull` on cPanel.
2. Place public PEM; set `BACKUP_PUBLIC_KEY_PATH` and Dropbox env vars in `.env`.
3. `php artisan config:cache` (optional).
4. Run **once manually**: `php artisan backup:run` and confirm Dropbox path + log line.
5. Add the cron entry above.

See also `docs/ops-backups.md` for general ops context.
