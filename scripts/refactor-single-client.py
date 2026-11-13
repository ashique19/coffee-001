#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SKIP_DIRS = {"vendor", "node_modules", "storage", "public/build", ".git"}

REPLACEMENTS = [
    ("App\\Models\\Tenant", "App\\Models\\BusinessSetting"),
    ("App\\Tenancy\\TenantContext", "App\\Business\\BusinessContext"),
    ("TenantContext", "BusinessContext"),
    ("App\\Models\\TenantUser", "App\\Models\\StaffUser"),
    ("TenantUser", "StaffUser"),
    ("Zatca\\Data\\InvoiceLineData", "App\\Data\\InvoiceLineData"),
    ("config('zatca.zheart", "config('zheart"),
    ("config('zatca.enabled')", "config('zheart.submit_to_zatca')"),
    ("'tenant.dashboard'", "'admin.dashboard'"),
    ("'tenant.login'", "'admin.login'"),
    ("'tenant.logout'", "'admin.logout'"),
    ("'tenant.password", "'admin.password"),
    ("'tenant.pos.", "'admin.pos."),
    ("'tenant.invoices", "'admin.invoices"),
    ("'tenant.catalog'", "'admin.catalog'"),
    ("'tenant.inventory'", "'admin.inventory'"),
    ("'tenant.business'", "'admin.business'"),
    ("'tenant.team'", "'admin.team'"),
    ("'tenant.reports", "'admin.reports"),
    ("'tenant.storefront'", "'admin.storefront'"),
    ("'tenant.customers'", "'admin.customers'"),
    ("->routeIs('tenant.", "->routeIs('admin."),
    ("name('tenant.", "name('admin."),
]


def should_touch(path: Path) -> bool:
    if path.suffix not in {".php", ".blade.php", ".md", ".xml", ".js", ".ts"}:
        return False
    return not any(s in SKIP_DIRS for s in path.parts)


def main() -> None:
    for path in ROOT.rglob("*"):
        if not path.is_file() or not should_touch(path):
            continue
        text = path.read_text(encoding="utf-8", errors="ignore")
        orig = text
        for old, new in REPLACEMENTS:
            text = text.replace(old, new)
        if text != orig:
            path.write_text(text, encoding="utf-8")


if __name__ == "__main__":
    main()
