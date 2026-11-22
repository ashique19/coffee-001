<?php

namespace App\Support\Invoicing;

use App\Enums\ProductItemType;
use App\Models\ProductUnit;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class DocumentCreateUiSupport
{
    public const DEFAULT_VAT_ZATCA_CATEGORY = 'S';

    public static function newLineId(): string
    {
        return (string) Str::uuid();
    }

    public static function defaultDocumentVatRate(): float
    {
        return 15.0;
    }

    public static function normalizeLineVatRate(mixed $lineValue, mixed $productVat = null, ?float $fallback = null): string
    {
        $rate = is_numeric($lineValue) ? (float) $lineValue : null;
        if ($rate === null && is_numeric($productVat)) {
            $rate = (float) $productVat;
        }
        if ($rate === null) {
            $rate = $fallback ?? self::defaultDocumentVatRate();
        }

        return (string) $rate;
    }

    public static function lineIsPresent(array $row): bool
    {
        if (filled($row['product_id'] ?? null)) {
            return true;
        }

        return filter_var($row['is_custom'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isCustomLine(array $row): bool
    {
        return self::lineIsPresent($row) && ! filled($row['product_id'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyFormLine(): array
    {
        return [
            'line_id' => self::newLineId(),
            'name' => '',
            'name_ar' => '',
            'name_bn' => '',
            'quantity' => '1',
            'unit_price' => '',
            'vat_rate' => self::normalizeLineVatRate(null, self::defaultDocumentVatRate()),
            'discount_percent' => '0',
            'product_id' => null,
            'product_unit_id' => null,
            'unit_label' => '',
            'unit_code' => '',
            'item_type' => ProductItemType::Item->value,
            'vat_zatca_category' => self::DEFAULT_VAT_ZATCA_CATEGORY,
            'is_custom' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    public static function decorateCustomLine(array $line): array
    {
        $line = array_merge(self::emptyFormLine(), $line);
        $line['is_custom'] = true;
        $line['product_id'] = null;
        $line['vat_rate'] = self::normalizeLineVatRate($line['vat_rate'] ?? '', self::defaultDocumentVatRate());
        if (trim((string) ($line['vat_zatca_category'] ?? '')) === '') {
            $line['vat_zatca_category'] = self::DEFAULT_VAT_ZATCA_CATEGORY;
        }
        if (trim((string) ($line['item_type'] ?? '')) === '') {
            $line['item_type'] = ProductItemType::Item->value;
        }
        if (! filled($line['product_unit_id'] ?? null)) {
            $unit = ProductUnit::query()->where('is_active', true)->where('code', 'PCS')->first()
                ?? ProductUnit::query()->where('is_active', true)->orderBy('code')->first();
            if ($unit !== null) {
                $line['product_unit_id'] = $unit->id;
                $line['unit_label'] = $unit->displayName();
                $line['unit_code'] = filled($unit->ubl_unit_code) ? (string) $unit->ubl_unit_code : 'PCE';
            }
        }

        return $line;
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public static function applySharedCustomLineNames(array $lines): array
    {
        foreach ($lines as $index => $row) {
            if (! is_array($row) || ! self::isCustomLine($row)) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            $lines[$index]['name'] = $name;
            $lines[$index]['name_ar'] = $name;
            $lines[$index]['name_bn'] = $name;
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function payloadLineFromFormRow(array $row): array
    {
        $item = [
            'name' => trim((string) ($row['name'] ?? '')),
            'quantity' => round((float) ($row['quantity'] ?? 0), 3),
            'unit_price_ex_vat' => (float) ($row['unit_price'] ?? 0),
            'vat_rate' => (float) ($row['vat_rate'] ?? 15),
        ];

        foreach (['name_ar', 'name_bn', 'unit_label', 'unit_code', 'item_type', 'vat_zatca_category'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                $item[$key] = $value;
            }
        }

        $productId = $row['product_id'] ?? null;
        if ($productId !== null && (int) $productId > 0) {
            $item['product_id'] = (int) $productId;
        } else {
            $item['is_custom'] = true;
            $name = $item['name'];
            $item['name_ar'] = $name;
            $item['name_bn'] = $name;
        }

        if (filled($row['product_unit_id'] ?? null)) {
            $item['product_unit_id'] = (int) $row['product_unit_id'];
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    public static function lineValidationRules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.is_custom' => ['sometimes', 'boolean'],
            'lines.*.name' => ['required', 'string', 'max:200'],
            'lines.*.name_ar' => ['nullable', 'string', 'max:200'],
            'lines.*.name_bn' => ['nullable', 'string', 'max:200'],
            'lines.*.item_type' => ['nullable', 'string', Rule::in(array_column(ProductItemType::cases(), 'value'))],
            'lines.*.product_unit_id' => ['nullable', 'integer', 'exists:product_units,id'],
            'lines.*.unit_code' => ['nullable', 'string', 'max:16'],
            'lines.*.vat_zatca_category' => ['nullable', 'string', 'max:8'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, string>
     */
    public static function lineValidationMessages(array $lines): array
    {
        $messages = [];
        $rules = ['required', 'required_without', 'numeric', 'min', 'max', 'in', 'integer', 'exists'];
        $fields = [
            'name',
            'name_ar',
            'name_bn',
            'item_type',
            'product_unit_id',
            'unit_code',
            'vat_zatca_category',
            'quantity',
            'unit_price',
            'vat_rate',
        ];

        foreach (array_keys($lines) as $index) {
            $row = is_array($lines[$index] ?? null) ? $lines[$index] : [];
            if (! self::isCustomLine($row) && filled($row['product_id'] ?? null)) {
                continue;
            }
            if (! self::lineIsPresent($row) && ! self::isCustomLine($row)) {
                continue;
            }

            $lineNo = (int) $index + 1;
            foreach ($fields as $field) {
                $text = __('invoices.custom_line_errors.'.$field, ['line' => $lineNo]);
                foreach ($rules as $rule) {
                    $messages["lines.$index.$field.$rule"] = $text;
                }
            }
        }

        return $messages;
    }
}
