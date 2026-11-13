<?php

namespace App\Services\Tenant;

use App\Enums\InvoiceDocumentType;
use App\Enums\InvoiceStatus;
use App\Enums\ZatcaSubmissionStatus;
use App\Models\Invoice;
use App\Models\BusinessSetting;
use App\Models\ZatcaSubmission;
use App\Services\Pos\PosAnalyticsService;
use Illuminate\Support\Carbon;

class TenantDashboardService
{
    public function __construct(
        private PosAnalyticsService $analytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Tenant $tenant): array
    {
        $tz = config('app.timezone');
        $todayEnd = Carbon::now($tz)->endOfDay();
        $todayStart = Carbon::now($tz)->startOfDay();
        $weekStart = Carbon::now($tz)->subDays(6)->startOfDay();

        $today = $this->analytics->report($tenant, null, $todayStart, $todayEnd, 'day');
        $recent = $this->analytics->report($tenant, null, $weekStart, $todayEnd, 'day');

        return [
            'today' => $today['summary'],
            'recent_sales_series' => $recent['sales_series'],
            'invoices_by_type' => $this->invoiceCountsByType($tenant),
            'zatca_status' => $this->zatcaStatusCounts($tenant),
        ];
    }

    /**
     * @return list<array{type: string, label_key: string, count: int}>
     */
    private function invoiceCountsByType(Tenant $tenant): array
    {
        $rows = Invoice::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', InvoiceStatus::Issued)
            ->selectRaw('document_type, COUNT(*) as aggregate')
            ->groupBy('document_type')
            ->pluck('aggregate', 'document_type');

        $types = [
            InvoiceDocumentType::Simplified,
            InvoiceDocumentType::Standard,
            InvoiceDocumentType::CreditNote,
            InvoiceDocumentType::DebitNote,
        ];

        $out = [];
        foreach ($types as $type) {
            $key = $type->value;
            $out[] = [
                'type' => $key,
                'label_key' => 'dashboard.invoice_type.'.$key,
                'count' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return array{pending: int, reported: int, failed: int, total: int}
     */
    private function zatcaStatusCounts(Tenant $tenant): array
    {
        $raw = ZatcaSubmission::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $pending = (int) ($raw[ZatcaSubmissionStatus::Pending->value] ?? 0)
            + (int) ($raw[ZatcaSubmissionStatus::Submitted->value] ?? 0);
        $reported = (int) ($raw[ZatcaSubmissionStatus::Accepted->value] ?? 0);
        $failed = (int) ($raw[ZatcaSubmissionStatus::Failed->value] ?? 0)
            + (int) ($raw[ZatcaSubmissionStatus::Rejected->value] ?? 0);

        return [
            'pending' => $pending,
            'reported' => $reported,
            'failed' => $failed,
            'total' => $pending + $reported + $failed,
        ];
    }
}
