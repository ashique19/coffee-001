#!/usr/bin/env python3
"""Port adapted feature/unit tests from /agent/repos/zatca/tests for single-client cafe."""

from __future__ import annotations

import re
import shutil
from pathlib import Path

SRC = Path('/agent/repos/zatca/tests')
DST = Path('/workspace/tests')

SKIP = {
    'Zatca', 'Plumber', 'Platform', 'Foundation', 'ClientBootstrap',
    'CombinedRelease', 'DemoPlumber', 'EgsDevice', 'TenantIsolation',
    'TenantAuth', 'TenantLivewire', 'TenantLogin', 'TenantDefault',
    'TenantBusiness', 'TenantDashboard', 'Backup', 'SandboxZatca',
    'UblInvoice', 'Xades', 'QrTlv', 'InvoiceHash', 'HardenedInvoice',
    'ZatcaVat', 'ZatcaCertificate', 'ZatcaCompliance', 'ZatcaPhase',
    'ZatcaLegal', 'ZatcaX509', 'ZatcaApi', 'ZatcaAdmin', 'ZatcaRepeat',
    'ZatcaSimulation', 'ZatcaSandbox', 'ZatcaStandard', 'ZatcaSubmission',
    'ReconcileIssue', 'TenantZatca', 'TenantAppBuild', 'GeoMaps',
    'GoogleMaps', 'GuardedCertificate', 'Ecdsa', 'HybridBackup',
}

REPLACEMENTS = [
    ('App\\Tenancy\\TenantContext', 'App\\Business\\BusinessContext'),
    ('TenantContext', 'BusinessContext'),
    ('App\\Models\\Tenant', 'App\\Models\\BusinessSetting'),
    ('createTenant', 'seedBusiness'),
    ('actingAsTenant', 'actingAsBusiness'),
    ('use Tests\\Support\\InteractsWithTenancy', 'use Tests\\Support\\InteractsWithBusiness'),
    ('InteractsWithTenancy', 'InteractsWithBusiness'),
    ('$this->createEgsDevice($tenant)', '$branch ??= \\App\\Models\\Branch::query()->first()'),
    ('$this->createEgsDevice($business)', '$branch ??= \\App\\Models\\Branch::query()->first()'),
    ('$device = $this->createEgsDevice($tenant);', '$branch = \\App\\Models\\Branch::query()->first();'),
    ('$device = $this->createEgsDevice($business);', '$branch = \\App\\Models\\Branch::query()->first();'),
    ('->withoutGlobalScopes()', ''),
    ("AuthRoleProvisioner::seedPlatform()", "AuthRoleProvisioner::seedStaffRoles()"),
    ('\\App\\Support\\PermissionTeams::forPlatform();', ''),
    ('PermissionTeams::forPlatform();', ''),
    ('$product->tenant', '$tenant'),
    ('issueDraft($device,', 'issueDraft($branch,'),
    ('issueDraft($device ,', 'issueDraft($branch ,'),
]

ROUTE_PATTERNS = [
    (r"'/app/[^']+/admin/", "'/admin/"),
    (r"'/app/[^']+/login'", "'/login'"),
    (r'route\([\'"]tenant\.', "route('admin."),
    (r'\$tenant->slug', "'main'"),
]


def should_port(name: str) -> bool:
    return not any(s in name for s in SKIP)


def transform(text: str) -> str:
    for old, new in REPLACEMENTS:
        text = text.replace(old, new)
    for pat, repl in ROUTE_PATTERNS:
        text = re.sub(pat, repl, text)
    return text


def main() -> None:
    for folder in ('Feature', 'Unit'):
        src_dir = SRC / folder
        if not src_dir.is_dir():
            continue
        dst_dir = DST / folder
        dst_dir.mkdir(parents=True, exist_ok=True)
        for path in sorted(src_dir.glob('*.php')):
            if not should_port(path.name):
                continue
            if path.name in {'PosCartQtyRegressionTest.php', 'ApplicationBootTest.php', 'CafeStorefrontTest.php',
                             'EnabledLocalesTest.php', 'IngredientInventoryTest.php', 'InstallSqlTest.php',
                             'AdminAuthenticatedRoutesTest.php', 'ZHeartClientTest.php'}:
                continue
            target = dst_dir / path.name
            content = transform(path.read_text(encoding='utf-8'))
            target.write_text(content, encoding='utf-8')
            print('ported', target.relative_to(DST))


if __name__ == '__main__':
    main()
