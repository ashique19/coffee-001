# Fancy Coffee proposal — requirements traceability

**Source:** Client proposal PDF (Tasken Lab → **Fancy Coffee**, dated 2026-10-24, Bangla + English).  
**Purpose:** Map every stated requirement to implementation status in this repo, phased delivery order, and ZATCA/risk notes.  
**Legend:** **Done** = shipped in this PR branch · **Foundation ready** = schema/services/hooks exist, UI/workflows not built · **Planned** = not started · **Unclear** = needs client or ZATCA clarification.

This document does **not** claim ZATCA certification.

**Delivery rule:** **one PR per phase**, merged into `main` in order (0 → 7).

**Git workflow (per phase):**
1. `git checkout main && git pull` (include the merged previous phase).
2. Create a **new branch from `main` only** — e.g. `cursor/phase-3-zatca-integration-6d04` — not from another phase branch.
3. Implement, tests green, open PR targeting `main`.
4. After merge, repeat for the next phase on updated `main`.

Do **not** stack phase branches (phase 4 is not based on phase 3’s branch).

**Hard constraints (all phases):** no calls to **ZATCA production**; no production **deploy** automation in-repo; no **paid** third-party services unless the client supplies credentials outside this codebase (use interfaces + config flags only).

**UI (all phases, standing):** **Mobile-first** — design for small screens first, scale up with responsive breakpoints; **≥44px** touch targets; no hover-only interactions; **RTL** validated on mobile widths for `ar` (and future `ur`). Applied in code from Phase 4 onward; café and foundation layouts retrofitted in Phase 4 PR.

**UX backlog (done on `main`):** `docs/ux-and-feature-backlog.md` — audit + feature-gap table; quick POS fixes shipped in the UX follow-up PR. Larger items remain proposed only.

---

## Delivery roadmap (one PR per phase)

| Phase | PR focus | Status | Depends on |
|------|-----------|--------|------------|
| **0** | Application foundation | **Done** (PR #1) | — |
| **1** | Multi-tenant data model + ZATCA core + tests | **Done** (PR #1) | 0 |
| **2** | Fancy Coffee fit: branches/POS hooks, café theme, platform vs tenant auth, traceability | **Done** (PR #1) | 1 |
| **3** | ZATCA integration (simulation/sandbox only) | **Done** (PR from `cursor/phase-3-zatca-integration-6d04`) | 2 merged |
| **4** | POS MVP (mobile-first POS UI) | **In progress** (`cursor/phase-4-pos-mvp-6d04`) | 3 merged |
| **5** | Loyalty & referrals | **Planned** | branch from `main` after 4 merges |
| **6** | Operations (inventory, staff/shifts, reports, dashboard) | **Planned** | branch from `main` after 5 merges |
| **7** | Platform super-admin, extra locales, security & PDPL checklist | **Planned** | branch from `main` after 6 merges |

### Phase 0 — Done (foundation)

- Laravel 13 + Livewire 3 + Pest, README, `.env.example`
- Single-DB multi-tenancy (`tenants`, `BelongsToTenant`, `tenant:provision`)
- Locale shell **en/ar** (RTL structure)
- Isolated `app/Zatca/`: UBL builder, ICV/PIH chain, QR TLV (tags 1–5), hash, secp256k1/CSR, XAdES **stub**, `NullZatcaApiClient`
- Issued invoice immutability + `invoice_audit_logs`

### Phase 1 — Done (domain + compliance primitives)

- `branches`, `egs_devices.branch_id`, invoices `branch_id` + POS `source`
- `pos_orders`, `customers`, `loyalty_transactions` (schema only)
- `PosInvoiceService`: simplified invoice from paid POS; refunds → credit notes; loyalty discount on ex-VAT lines
- Proposal mapping started in this document

### Phase 2 — Done (client storefront + auth split)

- **`docs/requirements-traceability.md`** (this file) tied to Fancy Coffee PDF
- Café theme preset (`cafe` / `fancy-coffee`): public `/{tenant}/`, `/menu`
- `platform_users` + `tenant_users`; guards `platform` / `tenant`; Spatie permissions (teams = `tenant_id`)
- `PlatformSupportAccessService` + `platform_access_audits`
- Tests: tenant isolation, hash chain, POS→invoice, auth realm isolation, café pages

### Phase 3 — Done (ZATCA integration PR)

**Goal:** Simulation/sandbox only — configurable via `ZATCA_*` / `config/zatca.php`; **never** production Fatoora URLs or live CSIDs in CI/tests.

| Item | Implementation |
|------|----------------|
| Device onboarding | `ZatcaOnboardingService` + `zatca:onboard` (OTP → compliance CSID → compliance invoice check → production CSID); secrets encrypted on `zatca_credentials` |
| HTTP client | `SandboxZatcaHttpClient` (simulation URL guard) + `FakeZatcaHttpClient` (`ZATCA_API_DRIVER=fake`) |
| Clearance vs reporting | `ZatcaSubmissionService` — simplified → reporting; standard/credit/debit → clearance |
| Outage resilience | `zatca_submissions` queue + `SubmitInvoiceToZatcaJob` + `zatca:retry-submissions` with backoff |
| 24h reporting deadline | `reporting_deadline_at` + `zatca:reporting-deadline-warnings` → `zatca_notifier_logs` |
| Credential renewal | `zatca:csid-renewal-reminders` (configurable day windows) |
| Auto-submit on issue | When `ZATCA_API_ENABLED` + `ZATCA_AUTO_QUEUE_SUBMISSION` |
| Tests | `composer test:zatca` (30+ cases); Pest fakes + optional `ZATCA_SANDBOX_INTEGRATION=1` live smoke; see `docs/zatca-test-matrix.md` |

**Still foundation / open items:** XAdES remains a stub; QR tags 6–9 not added; exact reporting SLA and API payload shapes must be verified against current ZATCA simulation Swagger before go-live.

**Traceability IDs primarily touched:** ZAT-3–ZAT-6, ZATCA backlog table.

### Phase 4 — Done (merged to `main`)

**Goal:** Cashier-usable MVP without heavy hardware vendors; **mobile-first** POS and retrofit of café/foundation shells.

| Item | Implementation |
|------|----------------|
| Catalog | `product_categories`, `products`, `product_modifiers`, `pos:seed-catalog` |
| Order screen | Livewire `Pos\Terminal` — categories, modifiers, cart, promo discount ex-VAT |
| Totals / VAT | `PosTotalsCalculator`, pay via `PosCheckoutService` → `PosInvoiceService` |
| Payments | `PosPaymentMethod` cash/card/other |
| Receipt | `tenant/pos/receipt` AR/EN, print-friendly, TLV note (QR image TBD) |
| History / EOD | Livewire `OrderHistory`, `EndOfDay` + `PosEndOfDayService` |
| Auth | `tenant` login at `/app/{tenant}/login`; `pos.access`; branch-scoped cashier |
| Mobile-first | Shared `touch-target` / `tap-action` utilities; sticky cart on small screens; café nav on mobile |

**Traceability IDs:** POS-1–POS-8, STF-2 (login UI), partial ZAT-5.

### Phase 5 — Done (merged to `main`)

- Earn points at **admin-configured rate**; ledger + balance
- **Redeem** as ex-VAT discount (wired to POS + ZATCA line net — verify UBL allowance in Phase 3 if needed)
- Expiry + minimum redemption rules
- Referral codes, rewards, anti-abuse (self-referral, duplicate)
- Automated allocation on qualifying orders

**Traceability IDs:** LOY-*, REF-*.

### Phase 6 — Done (merged to `main`)

- **Inventory:** ingredients, stock in/out, **recipes** deduct on POS sale, adjustments
- **Low-stock alerts** (branch-scoped); dashboard widgets
- **Staff & shifts** (extend `tenant_users`; shift records lightweight)
- **Reports:** daily sales, payment mix, branch + consolidated; export CSV first (PDF optional)
- **Admin dashboard:** proposal §2.10 core widgets

**Traceability IDs:** INV-*, ALT-*, RPT-*, DASH-*, BR-*.

### Phase 7 — Done (merged to `main`)

- **Super-admin** (platform guard): provision tenant, preset, legal entity, initial branch/EGS flags — no tenant login
- **Locales:** enable **bn**, **ur** (RTL), **hi** in `config/locales.php` + translations
- **Security review checklist** in repo (OWASP-style + auth realm checks)
- **Backups & retention** runbook (documented; client runs infra — no deploy scripts)
- **PDPL checklist** (KSA personal data) for customer/loyalty fields

**Traceability IDs:** POR-* (deferred portal pieces), AUTH-*, COM-*, new platform rows below.

| ID | Requirement | Status | Target phase |
|----|-------------|--------|--------------|
| PLAT-1 | Super-admin client onboarding UI | **Planned** | 7 |
| PLAT-2 | bn / ur / hi locales | **Planned** | 7 |
| PLAT-3 | Backup & retention runbook | **Planned** | 7 |
| PLAT-4 | PDPL checklist | **Planned** | 7 |
| PLAT-5 | Security review gate | **Planned** | 7 |

### Proposal §4 (Tasken Lab) vs our phases

| Proposal §4 | Our phase |
|-------------|-----------|
| Phase 1–2 Analysis / UI | 0–2 (foundation + café theme; full UI/UX for POS in 4+) |
| Phase 3 Backend / DB | 0–1 |
| Phase 4 POS, inventory, staff | 4 (POS), 6 (inventory/staff) |
| Phase 5 Loyalty | 5 |
| Phase 6 Reporting, multi-branch, ZATCA | 3 (ZATCA), 6 (reports/dashboard) |
| Phase 7–8 QA, deployment, training | 7 (checklists); training/deployment **out of repo** |

---

## 1. Project overview (§1)

| ID | Requirement (EN) | Status | Notes |
|----|------------------|--------|-------|
| OV-1 | Web-based coffee shop management (orders, sales, inventory, loyalty, referral, staff, multi-branch) | **Foundation ready** | Multi-tenant app; domain tables for branch, POS, customer, loyalty, staff |
| OV-2 | Centralized platform for multi-branch operations | **Foundation ready** | `branches` under `tenant_id`; branch on POS, EGS, invoices |
| OV-3 | ZATCA Phase 2 e-invoicing compliance path | **Foundation ready** | `app/Zatca/` module; live API stubbed; XAdES stub |
| OV-4 | Improve operations, data management, retention | **Planned** | Product outcome; not measurable in code yet |

---

## 2.1 Point of Sale (POS) (§2.1)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| POS-1 | Create / update / manage orders | **Done** (Phase 4) | `PosOrderService` open/add lines; statuses |
| POS-2 | Product selection on orders | **Done** (Phase 4) | Catalog models + POS terminal |
| POS-3 | Automatic billing (price × qty, totals) | **Done** (Phase 4) | `PosTotalsCalculator` |
| POS-4 | Discount & VAT calculation | **Done** (Phase 4) | Line + promo ex-VAT; UBL via issue on pay |
| POS-5 | Payment methods (cash, card, other) | **Done** (Phase 4) | Checkout + recorded on order |
| POS-6 | Order & payment history | **Done** (Phase 4) | Order history Livewire |
| POS-7 | Invoice & receipt print | **Done** (Phase 4) | Receipt view AR/EN; QR TLV text (image TBD) |
| POS-8 | Order cancellation & refund | **Done** (Phase 4) | Refund from history → credit note |

**ZATCA note:** Retail POS typically uses **simplified** tax invoices (B2C). Verify per branch/EGS onboarding.

---

## 2.2 Customer loyalty & reward points (§2.2)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| LOY-1 | Earn points on eligible purchases | **Foundation ready** | `loyalty_transactions` (`earn`), `customers.points_balance` |
| LOY-2 | Purchase-based automatic points | **Planned** | Phase 5 |
| LOY-3 | Customizable earning rate (admin) | **Planned** | Phase 5 tenant settings |
| LOY-4 | Redeem points for discount/rewards | **Foundation ready** | `redeem` txn; `loyalty_discount_ex_vat` on POS → invoice |
| LOY-5 | Points balance & history | **Foundation ready** | Ledger table + balance column |
| LOY-6 | Points expiration rules | **Planned** | Phase 5 scheduled job |
| LOY-7 | Minimum redemption / rules | **Planned** | Phase 5 |

**ZATCA note:** Loyalty redemption as **price reduction** must reduce **taxable amount (ex-VAT)** before VAT (implemented in line `discount_ex_vat`). **Unclear:** whether ZATCA expects explicit `AllowanceCharge` elements vs line net — TODO in UBL builder (verify with XML standard + SDK).

---

## 2.3 Customer referral & rewards (§2.3)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| REF-1 | Unique referral code per customer | **Foundation ready** | `customers.referral_code` unique per tenant |
| REF-2 | Refer new customers | **Planned** | Phase 5 (+ optional portal later) |
| REF-3 | Referrer points on referred purchase | **Foundation ready** | `referred_by_customer_id`, `loyalty_transactions.type=referral` |
| REF-4 | Referral & reward history | **Foundation ready** | Ledger + metadata |
| REF-5 | Referral tracking (signups, conversions) | **Planned** | Phase 5–6 |
| REF-6 | Customizable referral rules | **Planned** | Phase 5 |
| REF-7 | Anti-abuse (duplicate, self-referral) | **Planned** | Phase 5 |
| REF-8 | Automated points allocation | **Planned** | Phase 5 |

---

## 2.4 Inventory management (§2.4)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| INV-1 | Products, raw materials, ingredients CRUD | **Planned** | Phase 6 (catalog SKUs overlap Phase 4 — shared `products` table in 4, ingredients in 6) |
| INV-2 | Stock tracking | **Planned** | Phase 6 |
| INV-3 | Stock in / usage / wastage | **Planned** | Phase 6 |
| INV-4 | Auto deduct from POS via recipes | **Planned** | Phase 6 after Phase 4 POS |
| INV-5 | Recipe-based BOM | **Planned** | Phase 6 |
| INV-6 | Stock adjustment & history | **Planned** | Phase 6 |
| INV-7 | Inventory reports | **Planned** | Phase 6 |

**Foundation note:** `branch_id` on operational tables supports branch-wise stock when added.

---

## 2.5 Low stock alerts (§2.5)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| ALT-1 | Minimum stock levels | **Planned** | Phase 6 |
| ALT-2 | Automatic alerts | **Planned** | Phase 6 |
| ALT-3 | Dashboard notifications | **Planned** | Phase 6 |
| ALT-4 | Low stock list | **Planned** | Phase 6 |
| ALT-5 | Branch-wise monitoring | **Foundation ready** | Branch model; alerts Phase 6 |
| ALT-6 | Refill planning | **Planned** | Phase 6 |

---

## 2.6 ZATCA e-invoicing (§2.6)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| ZAT-1 | Electronic invoice per completed sale | **Foundation ready** | `PosInvoiceService` → simplified invoice |
| ZAT-2 | VAT calculation | **Done** (partial) | 15% on net ex-VAT lines; verify rates/exemptions |
| ZAT-3 | QR code (TLV) | **Done** (partial) | Tags 1–5; 6–9 TODO |
| ZAT-4 | Invoice number, date, time, business info | **Done** (partial) | Stored on `invoices`; UBL builder |
| ZAT-5 | Print, download, history | **Planned** | Phase 4 print; Phase 3 download/history for submitted XML |
| ZAT-6 | Phase 2 Fatoora integration & reporting | **Planned** | **Phase 3** simulation client only; production forbidden |
| ZAT-7 | Submission retry queue + 24h deadline warning | **Planned** | **Phase 3** |
| ZAT-8 | CSID / credential renewal reminders | **Planned** | **Phase 3** |

**Proposal note (§2.6):** Final ZATCA scope/timeline/cost after compliance review — **Unclear** until client confirms Fatoora credentials and reporting vs clearance mix.

---

## 2.7 Staff management & RBAC (§2.7)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| STF-1 | Staff accounts CRUD | **Foundation ready** | `tenant_users` table (separate from platform) |
| STF-2 | Secure login | **Foundation ready** | Guards ready; login UI **Phase 4** (tenant), **Phase 7** (platform admin) |
| STF-3 | Roles: Admin, Manager, Cashier, Accountant | **Foundation ready** | Spatie roles per tenant (`owner`, `manager`, `cashier`, `accountant`) |
| STF-4 | Permissions by role | **Foundation ready** | Spatie permissions (`pos.access`, `invoices.issue`, …) seeded per tenant team |
| STF-5 | Activate/deactivate staff | **Foundation ready** | `is_active` on `tenant_users` / `platform_users` |
| STF-6 | Activity tracking | **Planned** | Phase 4–6 audit extensions |
| STF-8 | Shifts | **Planned** | Phase 6 |
| STF-7 | Branch-wise staff | **Foundation ready** | `tenant_users.branch_id` nullable (tenant-wide roles) |

### Platform vs tenant authentication (cross-cutting)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| AUTH-1 | Platform users (super-admin, support, billing) with no tenant | **Done** | `platform_users`, guard `platform`, routes `/platform/*` |
| AUTH-2 | Tenant users scoped to one tenant (+ optional branch) | **Done** | `tenant_users`, guard `tenant`, routes `/app/{tenant}/*` |
| AUTH-3 | Separate guards; no accidental cross-realm sessions | **Done** | `EnsurePlatformRealm` / `EnsureTenantRealm` middleware (403 cross-guard) |
| AUTH-4 | Tenant roles isolated per tenant (Spatie teams) | **Done** | `permission.teams` + `tenant_id` team key; tests assert separate role rows/pivots |
| AUTH-5 | Platform support access to tenant data explicit + audited | **Done** | `PlatformSupportAccessService` + `platform_access_audits`; not via tenant login |
| AUTH-6 | Platform permissions use team id `0`; tenant permissions use `tenant_id` | **Done** | MySQL-safe pivots; migration + `docs/sql/2026-11-09-platform-admin-role.sql` |

---

## 2.8 Daily sales report (§2.8)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| RPT-1 | Daily sales summary | **Planned** | Phase 4 EOD; Phase 6 full reports |
| RPT-2 | Payment method breakdown | **Planned** | Phase 6 |
| RPT-3 | Discount & refund report | **Planned** | Phase 6 |
| RPT-4 | Product sales / best sellers | **Planned** | Phase 6 (needs Phase 4 catalog) |
| RPT-5 | Date range reports | **Planned** | Phase 6 |
| RPT-6 | Branch-wise reports | **Foundation ready** | `branch_id` on orders |
| RPT-7 | Export PDF / Excel | **Planned** | Phase 6 (CSV first, low cost) |

---

## 2.9 Multi-branch management (§2.9)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| BR-1 | Branch CRUD | **Foundation ready** | `branches` table |
| BR-2 | Branch-specific POS | **Foundation ready** | `pos_orders.branch_id` |
| BR-3 | Branch-wise sales records | **Foundation ready** | Same |
| BR-4 | Branch-wise inventory | **Planned** | Phase 6 |
| BR-5 | Branch staff assignment | **Foundation ready** | `tenant_users.branch_id` |
| BR-6 | Branch performance reports | **Planned** | Phase 6 |
| BR-7 | Consolidated reports | **Planned** | Phase 6 |
| BR-8 | Centralized admin dashboard | **Planned** | Phase 6 |

**ZATCA note:** Each branch may need its own **EGS unit** (`egs_devices.branch_id`) for ICV/PIH chain — **Foundation ready**.

---

## 2.10 Admin dashboard & analytics (§2.10)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| DASH-1 | Sales / revenue overview | **Planned** | Phase 6 |
| DASH-2 | Order statistics | **Planned** | Phase 6 |
| DASH-3 | Customer analytics | **Planned** | Phase 6 |
| DASH-4 | Loyalty points overview | **Planned** | Phase 6 |
| DASH-5 | Referral analytics | **Planned** | Phase 6 |
| DASH-6 | Best-selling products | **Planned** | Phase 6 |
| DASH-7 | Low stock alerts on dashboard | **Planned** | Phase 6 |
| DASH-8 | Branch performance comparison | **Planned** | Phase 6 |

---

## 3. Customer management & loyalty portal (§3)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| POR-1 | Customer registration & login | **Planned** | Post–phase 5 or thin slice in 5 (optional) |
| POR-2 | Points balance | **Foundation ready** | `customers` + ledger |
| POR-3 | Points history | **Foundation ready** | `loyalty_transactions` |
| POR-4 | Referral code & sharing (WhatsApp, etc.) | **Planned** | Phase 5+ (copy link; no paid WhatsApp API) |
| POR-5 | Available rewards | **Planned** | Phase 5 |
| POR-6 | Purchase history | **Planned** | Phase 5/6 |
| POR-7 | Profile management | **Foundation ready** | `customers.profile` JSON |
| POR-8 | Mobile responsive portal (no native app) | **Planned** | Reuse café/Livewire patterns; full portal optional |

---

## 4–6. Commercial / delivery terms (§4–6)

| ID | Topic | Status | Notes |
|----|-------|--------|-------|
| COM-1 | 3-month phased delivery | **Planned** | Maps to phases 0–7 (one PR each after 2) |
| COM-2 | ZATCA integration in dev cost, scope TBD | **Unclear** | Client to confirm Fatoora onboarding |
| COM-3 | Hardware / printer compatibility | **Planned** | Proposal: verify device compatibility |
| COM-4 | Hosting, SMS, payment gateway, POS hardware excluded | **N/A** | Infrastructure choices |

---

## Public storefront (café theme preset)

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| CAF-1 | Public marketing site for first tenant | **Done** | `cafe` layout; path `/{tenant}/`, `/menu` |
| CAF-2 | Theme colours / logo / font from tenant settings | **Done** | CSS variables from `settings.theme` |
| CAF-3 | en/ar + RTL | **Done** | Tajawal (self-hosted) + Instrument Sans |
| CAF-4 | Demo provision for Fancy Coffee | **Done** | `--preset=fancy-coffee` or `cafe` + branch |

---

## ZATCA conflicts & verification backlog

| Topic | Risk | Action |
|-------|------|--------|
| Loyalty/promotional discounts | VAT must not be calculated on pre-discount gross if discount is price reduction | Line net ex-VAT implemented; **verify** UBL `AllowanceCharge` vs line extension in SDK |
| Referral rewards as points | Points have no VAT until redeemed | Redemption path only affects invoice (OK) |
| Refunds | Must use **credit note** linked to original | **Done** via `CreditNote` + `referenced_invoice_id` |
| Mixed standard + simplified | B2B vs B2C invoice types | POS path uses **simplified** only; standard for wholesale TBD |
| Phase 2 reporting/clearance | Not configured | **Phase 3** simulation only |
| XAdES / QR crypto tags | Stub / partial | **Phase 3** |
| 24h simplified reporting window | Exact clock rules | **Unclear** — confirm in Phase 3 from current ZATCA docs |
| Retry queue idempotency | Duplicate submission risk | **Phase 3** must key on invoice UUID/hash |
| Invoice immutability vs POS void | Void before issue vs credit note after issue | **Unclear** operational policy with client |

---

## Foundation schema map (no redesign expected)

```
tenants
  ├── branches
  ├── customers (+ referral_code, referred_by)
  ├── egs_devices (branch_id) → ZATCA ICV/PIH chain
  ├── pos_orders (branch_id, customer_id, discounts, line_items) → invoice_id
  ├── loyalty_transactions
  ├── tenant_users (branch_id optional) + Spatie roles (guard tenant, team = tenant_id)
  ├── platform_users + Spatie roles (guard platform, team = null)
  ├── platform_access_audits (support / cross-tenant reads)
  └── invoices (branch_id, source morph → PosOrder, credit notes)
```

**POS → ZATCA flow:** `PosInvoiceService::issueSimplifiedInvoiceForOrder()`  
**Refund → ZATCA flow:** `PosInvoiceService::issueRefundCreditNote()`

---

## Provision commands

```bash
# Fancy Coffee (proposal client) — storefront + main branch
php artisan tenant:provision fancy-coffee "Fancy Coffee" --preset=fancy-coffee --locale=ar

# With EGS for ZATCA chain testing
php artisan tenant:provision fancy-coffee "Fancy Coffee" --preset=fancy-coffee --locale=ar --with-egs
```
