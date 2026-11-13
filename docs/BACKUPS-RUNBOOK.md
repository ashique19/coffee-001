# Backups & retention (client-operated)

This repo does not automate backups. Recommended for MySQL single-DB multi-tenant:

1. **Daily** logical dump: `mysqldump --single-transaction --routines=0 --triggers=0 --set-gtid-purged=OFF dbname`
2. **Retain** 30 daily + 12 monthly; encrypt at rest
3. **Test restore** quarterly on staging
4. **Invoice immutability:** issued rows must not be restored from partial backups without chain validation
