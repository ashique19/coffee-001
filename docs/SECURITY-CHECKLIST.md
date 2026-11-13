# Security review checklist (pre-production)

- [ ] Tenant vs platform guard isolation verified (`AuthRealmIsolationTest` green)
- [ ] No ZATCA production URLs in config defaults or CI
- [ ] CSRF on all web forms; Livewire uses framework tokens
- [ ] Spatie permissions reviewed per role (cashier least privilege)
- [ ] Encrypted casts on ZATCA key material (`zatca_credentials`)
- [ ] Rate limiting on tenant login (configure at reverse proxy)
- [ ] HTTPS only on public hosts; secure cookies in production `.env`
- [ ] Dependency audit: `composer audit`, `npm audit`
- [ ] No secrets in git history (verify `deploy/test-initial` deleted)
