# UX audit & feature-gap backlog

**Date:** 2026-11-07 · **Scope:** Phases 0–7 shipped on `main` · **Proposal:** Fancy Coffee / Tasken Lab PDF

## Executive summary

Mobile-first patterns are in place (POS sticky cart, café mobile nav, touch targets). Gaps remain in loading/error/empty states, receipt QR rendering, platform onboarding UI, and several proposal modules deferred to backlog.

## UX audit by surface

| Surface | Mobile-first | RTL (ar/ur) | Touch ≥44px | Empty / loading / error | Quick fix in this PR |
|---------|--------------|-------------|-------------|-------------------------|----------------------|
| Café home/menu | Good | Good | Good | Partial | — |
| Tenant POS terminal | Good | Good | Good | Improved | Yes (`wire:loading`, empty catalog) |
| POS history/EOD | Good | OK | Good | Partial | — |
| Tenant login | Good | Good | Good | OK | — |
| Receipt print | OK | Good | N/A | QR TLV text only | — |
| Platform dashboard | Basic | LTR only | Good | Empty state OK | — |
| Foundation `/` | Basic | OK | Improved | — | — |

## Quick wins implemented (PR `cursor/ux-audit-quick-fixes-6d04`)

| Item | Impact | Effort | Cost |
|------|--------|--------|------|
| POS pay button `wire:loading` + disabled state | High | Low | $0 |
| Empty product grid message | Medium | Low | $0 |
| `aria-live` region for checkout errors | Medium | Low | $0 |

## Proposed backlog (not in quick-fix PR)

| ID | Idea | Impact | Effort | Cost notes |
|----|------|--------|--------|------------|
| UX-1 | Render ZATCA QR as scannable image on receipt | High | Medium | OSS lib only |
| UX-2 | POS customer lookup + redeem points inline | High | Medium | $0 |
| UX-3 | Platform wizard for tenant provision (replace CLI-only) | High | Large | $0 |
| UX-4 | Offline-tolerant POS queue (PWA) | Medium | Large | $0 |
| UX-5 | WhatsApp share for referral (no paid API) | Medium | Medium | Client-owned WA Business |
| FEAT-1 | Payment gateway (Hyperpay/Moyasar) | High | Large | Gateway fees |
| FEAT-2 | ESC/POS printer bridge | Medium | Large | Hardware |
| FEAT-3 | Full inventory UI + recipe editor | High | Large | $0 |
| FEAT-4 | Customer portal (POR-*) | High | XLarge | $0 |
| FEAT-5 | ZATCA production onboarding UI | High | Large | ZATCA fees external |

## Competitor-informed gaps (KSA café / SMB invoicing)

- **Foodics / Toast:** native hardware + offline — we stay browser-first (lower cost).
- **Wafeq / Qoyod:** accountant-focused; our edge is ZATCA + POS + loyalty in one tenant DB.
- **Marn / Sysev:** deep inventory — our Phase 6 schema is starter only; UI backlog FEAT-3.

## Follow-up process

1. Client prioritizes backlog IDs.
2. One phase PR per feature cluster.
3. Re-run this audit after major UI releases.
