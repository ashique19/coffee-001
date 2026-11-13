<?php

namespace App\Enums;

enum ZatcaOnboardingStatus: string
{
    case PendingKeys = 'pending_keys';
    case CsrReady = 'csr_ready';
    case ComplianceCsid = 'compliance_csid';
    case ComplianceChecksPassed = 'compliance_checks_passed';
    case ProductionCsid = 'production_csid';
}
