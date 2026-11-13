<?php

namespace App\Support\Invoicing;

use App\Services\Invoicing\ZHeart\ZHeartApiException;

final class ZHeartApiUserMessage
{
    public static function fromResponse(int $status, ?array $json, ?string $requestId): string
    {
        $id = $requestId ?? '—';
        $apiMessage = is_string(data_get($json, 'message')) ? trim((string) data_get($json, 'message')) : '';
        $errorCode = is_string(data_get($json, 'data.error')) ? trim((string) data_get($json, 'data.error')) : '';
        $detail = $apiMessage !== '' ? $apiMessage : $errorCode;

        if ($status === 401) {
            return __('zheart_api.auth_failed', ['id' => $id, 'detail' => $detail]);
        }

        if ($status === 403) {
            return match ($errorCode) {
                'ip_not_allowed' => __('zheart_api.ip_not_allowed', ['id' => $id, 'detail' => $detail]),
                'client_suspended' => __('zheart_api.client_suspended', ['id' => $id, 'detail' => $detail]),
                'client_not_active' => __('zheart_api.client_not_active', ['id' => $id, 'detail' => $detail]),
                default => __('zheart_api.forbidden', ['id' => $id, 'detail' => $detail]),
            };
        }

        if (in_array($status, [402, 423], true) || $errorCode === 'client_suspended') {
            return __('zheart_api.client_suspended', ['id' => $id, 'detail' => $detail]);
        }

        if ($status === 422) {
            $lower = strtolower($apiMessage.' '.$errorCode);
            if (str_contains($lower, 'production csid') || $errorCode === 'production_csid_required') {
                return __('zheart_api.production_csid_required', ['id' => $id, 'detail' => $detail]);
            }

            return __('zheart_api.validation_failed', ['id' => $id, 'detail' => $detail]);
        }

        return __('zheart_api.server_error', ['id' => $id, 'detail' => $detail]);
    }

    public static function forException(ZHeartApiException $e): string
    {
        return $e->getMessage();
    }
}
