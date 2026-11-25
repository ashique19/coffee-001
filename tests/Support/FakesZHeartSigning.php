<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Http;

trait FakesZHeartSigning
{
    /**
     * Seller block as returned by Z-Heart (built from its client profile). Placeholder values.
     *
     * @return array<string, mixed>
     */
    public static function zheartSellerBlock(): array
    {
        return [
            'name' => 'ZH Legal Placeholder Co',
            'name_ar' => 'شركة زد هارت التجريبية',
            'name_en' => 'ZH Legal Placeholder Co',
            'vat_number' => '399999999900003',
            'id_scheme' => 'CRN',
            'id' => '1010010000',
            'cr_number' => '1010010000',
            'address' => [
                'building' => '4321',
                'street' => 'ZH Test Street',
                'additional' => '8765',
                'district' => 'ZH District',
                'city' => 'Jeddah',
                'postal_code' => '23456',
                'country' => 'SA',
            ],
            'address_formatted' => '4321, ZH Test Street, ZH District, Jeddah, 23456, Addl. 8765',
            'branch' => 'Main branch',
            'source' => 'z-heart',
        ];
    }

    protected function fakeZHeartSigning(): void
    {
        config([
            'zheart.base_url' => 'https://z-heart.test',
            'zheart.api_key' => 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789',
            'zheart.retry_times' => 0,
            'zheart.auto_submit_signed' => false,
        ]);

        Http::fake([
            'https://z-heart.test/api/v1/invoices/sign' => Http::response([
                'status' => 'ok',
                'data' => [
                    'signed_xml' => base64_encode('<Invoice/>'),
                    'invoice_hash' => 'aGFzaA==',
                    'qr' => 'cXI=',
                    'uuid' => '550e8400-e29b-41d4-a716-446655440099',
                    'icv' => 1,
                    'pih' => '',
                    'seller' => self::zheartSellerBlock(),
                ],
                'request_id' => 'req-test',
            ], 200),
        ]);
    }
}
