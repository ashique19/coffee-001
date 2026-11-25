<?php

namespace App\Services\Invoicing\Signing;

use App\Contracts\Invoicing\InvoiceSigningGateway;
use App\Exceptions\InvoiceSigningException;
use App\Models\Invoice;
use App\Services\Invoicing\ZHeart\ZHeartApiException;
use App\Services\Invoicing\ZHeart\ZHeartClient;
use App\Services\Invoicing\ZHeart\ZHeartInvoicePayloadBuilder;
use App\Support\Invoicing\ZHeartApiUserMessage;
use App\Support\Invoicing\ZHeartSubmissionOutcome;
use App\Support\Invoicing\ZHeartZatcaPayloadNormalizer;

final class ZHeartInvoiceSigningGateway implements InvoiceSigningGateway
{
    public function __construct(
        private readonly ZHeartClient $client,
        private readonly ZHeartInvoicePayloadBuilder $payloadBuilder,
    ) {}

    public function signIssuedInvoice(InvoiceSigningRequest $request, Invoice $invoice): array
    {
        try {
            $payload = $this->payloadBuilder->build($request);
        } catch (\InvalidArgumentException $e) {
            throw new InvoiceSigningException($e->getMessage());
        }

        $integrationOn = $request->business->invoicingIntegrationEnabled();
        $envSubmit = (bool) config('zheart.auto_submit_signed');
        $autoSubmitSigned = $envSubmit && $integrationOn;
        $submissionSkipReason = null;

        if (! $autoSubmitSigned && app()->environment('production')) {
            throw new InvoiceSigningException(__('invoices.errors.sign_only_blocked_in_production'));
        }

        try {
            if ($autoSubmitSigned) {
                $result = $this->submitPayload($request, $payload);
            } else {
                $result = $this->client->sign($payload);
                $submissionSkipReason = ! $integrationOn && $envSubmit
                    ? 'integration_disabled'
                    : (! $envSubmit ? 'submit_disabled' : null);
            }
        } catch (ZHeartApiException $e) {
            if (in_array($e->outcome(), [ZHeartSubmissionOutcome::RETRY, ZHeartSubmissionOutcome::REJECTED], true)) {
                $mapped = $this->mapOutcomeException($e, $request->invoiceUuid, $autoSubmitSigned);
                $mapped['submission_skip_reason'] = $submissionSkipReason;

                return $mapped;
            }

            throw new InvoiceSigningException(ZHeartApiUserMessage::forException($e), 0, $e);
        }

        $data = $result['data'];
        $xml = $this->decodeSignedXml((string) ($data['signed_xml'] ?? ''));
        if ($xml === '' && ZHeartSubmissionOutcome::normalize($data['outcome'] ?? null) !== ZHeartSubmissionOutcome::RETRY) {
            throw InvoiceSigningException::signingFailed(context: 'Z-Heart response missing signed XML.');
        }

        $mapped = $this->mapZatcaSubmissionResponse($result, $request->invoiceUuid, $autoSubmitSigned);
        $mapped['submission_skip_reason'] = $submissionSkipReason;

        return $mapped;
    }

    public function signLocally(InvoiceSigningRequest $request): array
    {
        if (app()->environment('production')) {
            throw new InvoiceSigningException(__('invoices.errors.sign_only_blocked_in_production'));
        }

        try {
            $payload = $this->payloadBuilder->build($request);
        } catch (\InvalidArgumentException $e) {
            throw new InvoiceSigningException($e->getMessage());
        }

        try {
            $result = $this->client->sign($payload);
        } catch (ZHeartApiException $e) {
            throw new InvoiceSigningException(ZHeartApiUserMessage::forException($e), 0, $e);
        }

        return $this->mapZatcaSubmissionResponse($result, $request->invoiceUuid, false);
    }

    /**
     * Re-submit an already issued document to ZATCA using the same UUID and invoice content (idempotent).
     *
     * @return array<string, mixed>
     */
    public function submitInvoiceToZatca(InvoiceSigningRequest $request): array
    {
        if (! $request->business->invoicingIntegrationEnabled()) {
            throw new InvoiceSigningException(__('zheart_api.not_configured'));
        }

        try {
            $payload = $this->payloadBuilder->build($request);
        } catch (\InvalidArgumentException $e) {
            throw new InvoiceSigningException($e->getMessage());
        }

        try {
            $result = $this->submitPayload($request, $payload);
        } catch (ZHeartApiException $e) {
            if (in_array($e->outcome(), [ZHeartSubmissionOutcome::RETRY, ZHeartSubmissionOutcome::REJECTED], true)) {
                return $this->mapOutcomeException($e, $request->invoiceUuid, true);
            }

            throw new InvoiceSigningException(ZHeartApiUserMessage::forException($e), 0, $e);
        }

        return $this->mapZatcaSubmissionResponse($result, $request->invoiceUuid, true);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function submitPayload(InvoiceSigningRequest $request, array $payload): array
    {
        return $request->invoiceCategory === 'simplified'
            ? $this->client->report($payload)
            : $this->client->clear($payload);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function mapZatcaSubmissionResponse(array $result, string $invoiceUuid, bool $remoteSubmitted): array
    {
        $data = $result['data'];
        $normalized = ZHeartZatcaPayloadNormalizer::normalize($data);
        $remoteSubmission = $normalized['remote_submission'];

        if ($remoteSubmission === [] && is_array($data['zatca'] ?? null)) {
            $remoteSubmission = $data['zatca'];
        }

        $xml = isset($data['signed_xml']) ? $this->decodeSignedXml((string) $data['signed_xml']) : null;
        $cleared = isset($data['cleared_xml']) ? $this->decodeSignedXml((string) $data['cleared_xml']) : null;
        $outcome = ZHeartSubmissionOutcome::normalize($data['outcome'] ?? null);
        if ($outcome === null && $remoteSubmitted && ($result['status'] ?? 'ok') !== 'error') {
            $outcome = ZHeartSubmissionOutcome::ACCEPTED;
        }

        $invoiceNumber = isset($data['invoice_number']) && is_string($data['invoice_number']) && $data['invoice_number'] !== ''
            ? (string) $data['invoice_number']
            : null;

        return [
            'xml' => $xml,
            'hash' => isset($data['invoice_hash']) ? (string) $data['invoice_hash'] : null,
            'qr' => isset($data['qr']) ? (string) $data['qr'] : null,
            'seller' => is_array($data['seller'] ?? null) ? $data['seller'] : null,
            'icv' => isset($data['icv']) ? (int) $data['icv'] : null,
            'previous_invoice_hash' => isset($data['pih']) ? (string) $data['pih'] : null,
            'invoice_uuid' => (string) ($data['uuid'] ?? $invoiceUuid),
            'invoice_number' => $invoiceNumber,
            'outcome' => $outcome,
            'duplicate' => (bool) ($data['duplicate'] ?? false),
            'retryable' => ZHeartSubmissionOutcome::isRetryable($outcome, $data['retryable'] ?? null),
            'remote_submitted' => $remoteSubmitted,
            'remote_submission' => $remoteSubmission,
            'reporting_status' => $normalized['reporting_status'],
            'zatca_delivery' => $normalized['delivery'],
            'zatca_http_status' => $normalized['zatca_http_status'],
            'cleared_xml' => $cleared,
            'request_id' => $result['request_id'] ?? null,
            'message' => isset($data['message']) && is_string($data['message']) ? $data['message'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapOutcomeException(ZHeartApiException $e, string $invoiceUuid, bool $remoteSubmitted): array
    {
        $data = is_array(data_get($e->responseJson, 'data')) ? (array) data_get($e->responseJson, 'data') : [];
        $mapped = $this->mapZatcaSubmissionResponse([
            'data' => array_merge($data, [
                'outcome' => $e->outcome(),
                'retryable' => $e->isRetryable(),
                'message' => $e->getMessage(),
            ]),
            'request_id' => $e->requestId,
            'status' => 'error',
        ], $invoiceUuid, $remoteSubmitted);

        $mapped['outcome'] = $e->outcome();
        $mapped['retryable'] = $e->isRetryable();
        $mapped['message'] = $e->getMessage();

        return $mapped;
    }

    private function decodeSignedXml(string $encoded): string
    {
        if ($encoded === '') {
            return '';
        }

        if (str_starts_with($encoded, '<?xml') || str_starts_with($encoded, '<')) {
            return $encoded;
        }

        $decoded = base64_decode($encoded, true);

        return $decoded !== false ? $decoded : '';
    }
}
