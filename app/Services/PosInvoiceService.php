<?php

namespace App\Services;

use App\Enums\InvoiceDocumentType;
use App\Enums\PosOrderStatus;
use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\BusinessSetting;
use App\Support\EgsDeviceResolver;
use App\Support\Invoicing\StandardBuyerPayload;
use Illuminate\Support\Str;

/**
 * Bridges POS settlement to ZATCA simplified (B2C) tax invoices and credit-note refunds.
 *
 * Loyalty / promotional discounts must reduce the VAT taxable base (ex-VAT) before VAT is calculated.
 * TODO: Verify AllowanceCharge / document-level discount encoding in UBL per ZATCA XML standard.
 */
class PosInvoiceService
{
    public function __construct(
        private readonly InvoiceIssueService $invoiceIssueService,
        private readonly EgsDeviceResolver $egsDeviceResolver,
    ) {}

    public function issueInvoiceForOrder(PosOrder $order): Invoice
    {
        $tenant = $order->tenant ?? Tenant::query()->findOrFail($order->tenant_id);

        return $tenant->defaultInvoiceDocumentType() === InvoiceDocumentType::Standard
            ? $this->issueStandardInvoiceForOrder($order)
            : $this->issueSimplifiedInvoiceForOrder($order);
    }

    public function issueSimplifiedInvoiceForOrder(PosOrder $order): Invoice
    {
        if ($order->status !== PosOrderStatus::Paid) {
            throw new \InvalidArgumentException('Only paid orders can be invoiced.');
        }

        if ($order->invoice_id !== null) {
            return $order->invoice()->firstOrFail();
        }

        $device = $this->egsDeviceResolver->resolveForPosOrder($order);

        $lineItems = $this->mapLineItems($order);

        $documentNumber = 'POS-'.$order->order_number;

        $draft = $this->invoiceIssueService->issueDraft(
            device: $device,
            documentNumber: $documentNumber,
            type: InvoiceDocumentType::Simplified,
            lineItems: $lineItems,
        );

        $draft->forceFill([
            'branch_id' => $order->branch_id,
            'source_type' => PosOrder::class,
            'source_id' => $order->id,
            'payload' => array_merge($draft->payload ?? [], [
                'pos_order_id' => $order->id,
                'discount_ex_vat' => (float) $order->discount_ex_vat,
                'loyalty_discount_ex_vat' => (float) $order->loyalty_discount_ex_vat,
                'loyalty_points_redeemed' => $order->loyalty_points_redeemed,
                'payment_method' => $order->payment_method,
            ]),
        ])->save();

        $issued = $this->invoiceIssueService->issue($draft);

        $order->forceFill(['invoice_id' => $issued->id])->save();

        return $issued;
    }

    public function issueStandardInvoiceForOrder(PosOrder $order): Invoice
    {
        if ($order->status !== PosOrderStatus::Paid) {
            throw new \InvalidArgumentException('Only paid orders can be invoiced.');
        }

        if ($order->invoice_id !== null) {
            return $order->invoice()->firstOrFail();
        }

        $device = $this->egsDeviceResolver->resolveForPosOrder($order);
        $lineItems = $this->mapLineItems($order);
        $documentNumber = 'POS-'.$order->order_number;
        $order->loadMissing('customer');
        $buyer = StandardBuyerPayload::fromCustomer($order->customer);

        $draft = $this->invoiceIssueService->issueDraft(
            device: $device,
            documentNumber: $documentNumber,
            type: InvoiceDocumentType::Standard,
            lineItems: $lineItems,
        );

        $draft->forceFill([
            'branch_id' => $order->branch_id,
            'source_type' => PosOrder::class,
            'source_id' => $order->id,
            'payload' => array_merge($draft->payload ?? [], [
                'pos_order_id' => $order->id,
                'discount_ex_vat' => (float) $order->discount_ex_vat,
                'loyalty_discount_ex_vat' => (float) $order->loyalty_discount_ex_vat,
                'loyalty_points_redeemed' => $order->loyalty_points_redeemed,
                'payment_method' => $order->payment_method,
                ...$buyer,
            ]),
        ])->save();

        $issued = $this->invoiceIssueService->issue($draft);

        $order->forceFill(['invoice_id' => $issued->id])->save();

        return $issued;
    }

    public function issueRefundCreditNote(PosOrder $order, ?string $refundReason = null): Invoice
    {
        if ($order->invoice_id === null) {
            throw new \InvalidArgumentException('Order has no issued invoice to credit.');
        }

        if ($order->refund_credit_note_id !== null) {
            return $order->refundCreditNote()->firstOrFail();
        }

        $original = $order->invoice()->firstOrFail();
        $device = $this->egsDeviceResolver->resolveForInvoice($original);
        $lineItems = $this->mapLineItems($order);

        $credit = $this->invoiceIssueService->issueDraft(
            device: $device,
            documentNumber: 'CN-'.$order->order_number.'-'.Str::upper(Str::random(4)),
            type: InvoiceDocumentType::CreditNote,
            lineItems: $lineItems,
            referencedInvoice: $original,
        );

        $credit->forceFill([
            'branch_id' => $order->branch_id,
            'source_type' => PosOrder::class,
            'source_id' => $order->id,
            'payload' => array_merge($credit->payload ?? [], array_filter([
                'refund_reason' => $refundReason !== null ? trim($refundReason) : null,
            ])),
        ])->save();

        $issued = $this->invoiceIssueService->issue($credit);

        $order->forceFill([
            'status' => PosOrderStatus::Refunded,
            'refund_credit_note_id' => $issued->id,
        ])->save();

        return $issued;
    }

    /**
     * @return list<array{name: string, quantity: float, unit_price_ex_vat: float, vat_rate: float, discount_ex_vat?: float}>
     */
    private function mapLineItems(PosOrder $order): array
    {
        $items = [];
        foreach ($order->line_items as $line) {
            $items[] = [
                'name' => (string) ($line['name'] ?? 'Item'),
                'quantity' => (float) ($line['quantity'] ?? 1),
                'unit_price_ex_vat' => (float) ($line['unit_price_ex_vat'] ?? 0),
                'vat_rate' => (float) ($line['vat_rate'] ?? 15),
                'discount_ex_vat' => (float) ($line['discount_ex_vat'] ?? 0),
                'item_type' => (string) ($line['item_type'] ?? 'item'),
            ];
        }

        $orderDiscount = (float) $order->promo_discount_ex_vat + (float) $order->loyalty_discount_ex_vat;
        if ($items !== [] && $orderDiscount > 0) {
            $items[0]['discount_ex_vat'] += $orderDiscount;
        }

        if ($items === [] && (float) $order->subtotal_ex_vat > 0) {
            $loyaltyDiscount = (float) $order->loyalty_discount_ex_vat + (float) $order->discount_ex_vat;
            $items[] = [
                'name' => 'POS sale',
                'quantity' => 1,
                'unit_price_ex_vat' => (float) $order->subtotal_ex_vat,
                'vat_rate' => 15,
                'discount_ex_vat' => $loyaltyDiscount,
            ];
        }

        return $items;
    }
}
