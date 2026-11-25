<?php

namespace App\Support\Invoicing;

use App\Models\BusinessSetting;
use App\Models\Invoice;

/**
 * Seller section for invoice display. Z-Heart owns the seller identity: invoices store
 * the seller block Z-Heart returned at signing. Local legal settings are only a display
 * fallback for older invoices issued before that block existed.
 */
final class InvoiceSellerBlock
{
    /**
     * @return array{source: string, name_en: string, name_ar: string, vat_number: string, cr_number: string, id_scheme: string, id: string, national_address: array<string, string>, address_en: string, address_ar: string}
     */
    public static function forInvoice(Invoice $invoice, ?BusinessSetting $business = null): array
    {
        $stored = is_array($invoice->zheart_seller) ? $invoice->zheart_seller : null;

        if ($stored !== null && filled($stored['vat_number'] ?? null)) {
            return self::fromZHeart($stored);
        }

        return self::fromLocalSettings($business ?? BusinessSetting::singleton());
    }

    /**
     * @param  array<string, mixed>  $seller
     */
    public static function fromZHeart(array $seller): array
    {
        $address = is_array($seller['address'] ?? null) ? $seller['address'] : [];
        $scheme = self::str($seller['id_scheme'] ?? '');
        $id = self::str($seller['id'] ?? '');
        $nameAr = self::str($seller['name_ar'] ?? '');
        $nameEn = self::str($seller['name_en'] ?? '') ?: self::str($seller['name'] ?? '');

        return [
            'source' => 'z-heart',
            'name_en' => $nameEn,
            'name_ar' => $nameAr !== '' ? $nameAr : $nameEn,
            'vat_number' => self::str($seller['vat_number'] ?? ''),
            'cr_number' => self::str($seller['cr_number'] ?? '') ?: ($scheme === 'CRN' ? $id : ''),
            'id_scheme' => $scheme,
            'id' => $id,
            'national_address' => array_filter([
                'building' => self::str($address['building'] ?? ''),
                'street' => self::str($address['street'] ?? ''),
                'district' => self::str($address['district'] ?? ''),
                'city' => self::str($address['city'] ?? ''),
                'postal_code' => self::str($address['postal_code'] ?? $address['postal'] ?? ''),
                'additional' => self::str($address['additional'] ?? ''),
            ], fn (string $v): bool => $v !== ''),
            'address_en' => self::str($seller['address_formatted'] ?? ''),
            'address_ar' => '',
        ];
    }

    public static function fromLocalSettings(BusinessSetting $business): array
    {
        $legal = (array) data_get($business->settings, 'legal_entity', []);

        return [
            'source' => 'local',
            'name_en' => self::str($legal['name_en'] ?? '') ?: self::str($business->name),
            'name_ar' => self::str($legal['name_ar'] ?? ''),
            'vat_number' => self::str($legal['vat_number'] ?? ''),
            'cr_number' => self::str($legal['cr_number'] ?? ''),
            'id_scheme' => filled($legal['cr_number'] ?? null) ? 'CRN' : '',
            'id' => self::str($legal['cr_number'] ?? ''),
            'national_address' => is_array($legal['national_address'] ?? null) ? $legal['national_address'] : [],
            'address_en' => self::str($legal['address'] ?? ''),
            'address_ar' => self::str($legal['address_ar'] ?? ''),
        ];
    }

    /**
     * Latest seller block Z-Heart returned on any invoice, for read-only display in settings.
     *
     * @return array<string, mixed>|null
     */
    public static function latestFromZHeart(): ?array
    {
        $invoice = Invoice::query()
            ->whereNotNull('zheart_seller')
            ->latest('id')
            ->first(['id', 'zheart_seller']);

        return $invoice !== null && is_array($invoice->zheart_seller)
            ? self::fromZHeart($invoice->zheart_seller)
            : null;
    }

    private static function str(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}
