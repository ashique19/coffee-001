import { type Page } from 'playwright/test';
import { livewireFillBlur, withLivewireUpdate } from './livewire.js';

const PHONE_MAX = 768;
const TABLET_MAX = 1024;

function cartRoot(page: Page) {
    return page.locator('aside.pos-cart-glass');
}

function viewportWidth(page: Page): number {
    return page.viewportSize()?.width ?? 1280;
}

export async function openPosCartIfMobile(page: Page): Promise<void> {
    const width = viewportWidth(page);
    if (width >= TABLET_MAX) {
        return;
    }

    const root = cartRoot(page);
    const marker = width < PHONE_MAX ? '[data-pos-cart-sheet-footer]' : '#pos-payment-method';
    if (await root.locator(marker).isVisible({ timeout: 500 }).catch(() => false)) {
        return;
    }

    const opener = width < PHONE_MAX ? 'pos-mobile-cart-open' : 'pos-tablet-cart-open';
    await page.locator(`[data-testid="${opener}"]`).click();
    await root.locator(marker).waitFor({ state: 'visible', timeout: 15_000 });
}

export async function fillPromoDiscount(page: Page, amount: string): Promise<void> {
    await openPosCartIfMobile(page);

    const root = cartRoot(page);
    const toggle = root.locator('[data-pos-promo-toggle]');
    if (await toggle.isVisible({ timeout: 500 }).catch(() => false)) {
        await toggle.click();
    }

    const promo = root.locator('#pos-promo-discount');
    await promo.scrollIntoViewIfNeeded();
    await livewireFillBlur(page, promo, amount);
}

export async function payCurrentOrder(page: Page, method: string): Promise<void> {
    await openPosCartIfMobile(page);

    const root = cartRoot(page);
    if (viewportWidth(page) < PHONE_MAX) {
        const chip = root.locator(`label:has([data-pos-pay-chip="${method}"])`);
        await chip.scrollIntoViewIfNeeded();
        await withLivewireUpdate(page, () => chip.click());
    } else {
        const paymentSelect = root.locator('#pos-payment-method');
        await paymentSelect.scrollIntoViewIfNeeded();
        await withLivewireUpdate(page, () => paymentSelect.selectOption(method));
    }

    await root.getByRole('button', { name: /pay.*print|دفع/i }).click({ force: true });

    // Since the print-preview modal, paying stays on the terminal; open the full receipt page for assertions.
    await page.locator('[data-pos-receipt-modal]').waitFor({ state: 'visible', timeout: 60_000 });
    const orderId = await page.evaluate(
        () => (window as any).Livewire.all().find((c: any) => c.name === 'pos.terminal').$wire.receiptOrderId,
    );
    await page.goto(`/admin/pos/orders/${orderId}/receipt`);
}
