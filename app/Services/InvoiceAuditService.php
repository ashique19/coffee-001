<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceAuditLog;
use Illuminate\Support\Carbon;

class InvoiceAuditService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(Invoice $invoice, string $event, array $metadata = []): InvoiceAuditLog
    {
        $redacted = $this->redactSecrets($metadata);

        return InvoiceAuditLog::query()->create([
            'invoice_id' => $invoice->id,
            'event' => $event,
            'metadata' => $redacted,
            'occurred_at' => Carbon::now('UTC'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function redactSecrets(array $metadata): array
    {
        $keys = ['private_key', 'csid', 'certificate', 'encrypted_private_key'];

        foreach ($keys as $key) {
            if (array_key_exists($key, $metadata)) {
                $metadata[$key] = '[redacted]';
            }
        }

        return $metadata;
    }
}
