#!/usr/bin/env python3
"""PR #1 single-client hardening: deletes, renames, and text transforms."""

from __future__ import annotations

import re
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SKIP_DIRS = {"vendor", "node_modules", "storage", "public/build", ".git"}


def delete_if_exists(rel: str) -> None:
    p = ROOT / rel
    if p.is_file():
        p.unlink()
    elif p.is_dir():
        shutil.rmtree(p)


DELETE_PATHS = [
    "app/Enums/ZatcaOnboardingStatus.php",
    "app/Enums/ZatcaSubmissionStatus.php",
    "app/Enums/ZatcaSubmissionType.php",
    "app/Exceptions/ZatcaApiUserException.php",
    "app/Rules/ZatcaVatNumber.php",
    "app/Support/Themes/PlumberThemePreset.php",
    "app/Console/Commands/ClientBootstrapCommand.php",
    "app/Console/Commands/BackupDecryptCommand.php",
    "app/Console/Commands/BackupRunCommand.php",
    "app/Providers/BackupServiceProvider.php",
    "app/Services/Backup",
    "app/Auth/PermissionTenantTeamResolver.php",
    "app/Models/Tenant.php",
    "app/Models/TenantUser.php",
    "config/backup.php",
    "docs/BACKUPS-RUNBOOK.md",
    "resources/css/zatca-wizard.css",
    "resources/css/plumber-premium.css",
    "resources/css/plumber-storefront.css",
    "resources/css/foundation-landing.css",
    "resources/js/plumber-storefront.js",
    "resources/views/livewire/tenant/zatca-admin.blade.php",
    "resources/views/livewire/invoices/zatca-reconciliation-hub.blade.php",
    "resources/views/components/auth/platform-shell.blade.php",
    "resources/views/components/platform-layout.blade.php",
    "resources/views/layouts/platform.blade.php",
    "resources/views/layouts/plumber.blade.php",
    "resources/views/livewire/tenant-home.blade.php",
    "resources/views/components/plumber",
    "public/images/plumber",
    "public/images/templates/plumber-storefront.webp",
    "scripts/capture-zatca-wizard-screenshots.mjs",
    "scripts/generate-demo-plumber-sql.php",
    "scripts/generate-plumber-job-catalog-sql.php",
    "tests/Support/BackupTestKeys.php",
    "tests/Support/InteractsWithTenancy.php",
    "tests/Support/InteractsWithBusiness.php.bak",
]

for lang in ("en", "ar", "ur", "bn", "hi"):
    for name in ("zatca_admin.php", "zatca_api.php", "zatca_reconcile.php", "plumber.php", "platform.php"):
        DELETE_PATHS.append(f"lang/{lang}/{name}")

REPLACEMENTS: list[tuple[str, str]] = [
    ("App\\Rules\\ZatcaVatNumber", "App\\Rules\\SaudiVatNumber"),
    ("new ZatcaVatNumber", "new SaudiVatNumber"),
    ("App\\Support\\ReceiptComplianceLabel", "App\\Support\\ReceiptSigningStatusLabel"),
    ("ReceiptComplianceLabel", "ReceiptSigningStatusLabel"),
    ("App\\Enums\\ZatcaSubmissionStatus", "App\\Enums\\SigningSubmissionStatus"),
    ("ZatcaSubmissionStatus", "SigningSubmissionStatus"),
    ("App\\Services\\Tenant\\TenantBusinessSettingsService", "App\\Services\\Business\\BusinessSettingsService"),
    ("TenantBusinessSettingsService", "BusinessSettingsService"),
    ("App\\Services\\Tenant\\TenantDashboardService", "App\\Services\\Business\\BusinessDashboardService"),
    ("TenantDashboardService", "BusinessDashboardService"),
    ("App\\Services\\Tenant\\TenantBusinessLogoService", "App\\Services\\Business\\BusinessLogoService"),
    ("TenantBusinessLogoService", "BusinessLogoService"),
    ("App\\Services\\Tenant\\TenantSettingsAuditService", "App\\Services\\Business\\BusinessSettingsAuditService"),
    ("TenantSettingsAuditService", "BusinessSettingsAuditService"),
    ("App\\Http\\Middleware\\RedirectIfTenantAuthenticated", "App\\Http\\Middleware\\RedirectIfBusinessGuest"),
    ("RedirectIfTenantAuthenticated", "RedirectIfBusinessGuest"),
    ("App\\Auth\\TenantUserProvider", "App\\Auth\\StaffUserProvider"),
    ("TenantUserProvider", "StaffUserProvider"),
    ("App\\Services\\Auth\\TenantPasswordReset", "App\\Services\\Auth\\StaffPasswordReset"),
    ("TenantPasswordReset", "StaffPasswordReset"),
    ("App\\Notifications\\TenantResetPasswordNotification", "App\\Notifications\\StaffResetPasswordNotification"),
    ("TenantResetPasswordNotification", "StaffResetPasswordNotification"),
    ("'tenant.guest'", "'business.guest'"),
    ("EgsDeviceResolver", "BranchSigningContext"),
    ("App\\Support\\EgsDeviceResolver", "App\\Support\\Invoicing\\BranchSigningContext"),
    ("config('zatca.", "config('zheart."),
    ("__ ('zatca_admin.", "__('business.fields."),
    ("__('zatca_admin.", "__('business.fields."),
    ("ubl_xml", "signed_xml"),
    ("zatcaFilter", "signingFilter"),
    ("zatca_status", "signing_queue"),
    ("zatcaMax", "signingMax"),
    ("zatcaFilter", "signingFilter"),
    ("use App\\Providers\\BackupServiceProvider;", ""),
    ("BackupServiceProvider::class,", ""),
    ("use App\\Support\\Themes\\PlumberThemePreset;", ""),
]

LANG_REPLACEMENTS = [
    ("ZATCA", "signing service"),
    ("zatca", "signing"),
    ("Zatca", "Signing"),
    ("EGS device", "branch"),
    ("EGS ", ""),
    ("reconcile", "queue"),
    ("Reconcile", "Queue"),
    ("plumber", "storefront"),
    ("Plumber", "Storefront"),
    ("platform", "admin"),
    ("Platform", "Admin"),
]

TEXT_SUFFIXES = {".php", ".blade.php", ".md", ".js", ".css", ".json"}


def should_touch(path: Path) -> bool:
    if path.suffix == ".blade.php" or any(path.name.endswith(s) for s in TEXT_SUFFIXES):
        pass
    elif path.suffix not in TEXT_SUFFIXES:
        return False
    return not any(s in path.parts for s in SKIP_DIRS)


def apply_replacements(text: str, pairs: list[tuple[str, str]]) -> str:
    for old, new in pairs:
        text = text.replace(old, new)
    return text


def move_tenant_services() -> None:
    src = ROOT / "app/Services/Tenant"
    dst = ROOT / "app/Services/Business"
    if not src.exists():
        return
    dst.mkdir(parents=True, exist_ok=True)
    for f in src.glob("*.php"):
        content = f.read_text(encoding="utf-8")
        content = content.replace("namespace App\\Services\\Tenant;", "namespace App\\Services\\Business;")
        content = apply_replacements(content, REPLACEMENTS)
        name = f.name.replace("Tenant", "Business", 1)
        (dst / name).write_text(content, encoding="utf-8")
    shutil.rmtree(src)


def main() -> None:
    for rel in DELETE_PATHS:
        delete_if_exists(rel)

    move_tenant_services()

    for path in ROOT.rglob("*"):
        if not path.is_file() or not should_touch(path):
            continue
        if "harden-pr1.py" in str(path):
            continue
        text = path.read_text(encoding="utf-8", errors="ignore")
        orig = text
        text = apply_replacements(text, REPLACEMENTS)
        if "/lang/" in str(path):
            for old, new in LANG_REPLACEMENTS:
                text = text.replace(old, new)
        if text != orig:
            path.write_text(text, encoding="utf-8")


if __name__ == "__main__":
    main()
