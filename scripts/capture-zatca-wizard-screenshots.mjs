import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';

const base = process.env.CAFE_BASE_URL || 'http://127.0.0.1:8000';
const out = '/opt/cursor/artifacts';
await mkdir(out, { recursive: true });

const steps = [1, 2, 3, 4, 5, 6];
const viewports = [
  ['375', 375, 812],
  ['1280', 1280, 900],
];

const browser = await chromium.launch();
const loginUrl = `${base}/app/fancy-coffee/login`;

for (const locale of ['en', 'ar']) {
  for (const step of steps) {
    for (const [tag, width, height] of viewports) {
      const ctx = await browser.newContext({ viewport: { width, height }, locale });
      const page = await ctx.newPage();
      await page.goto(loginUrl, { waitUntil: 'networkidle' });
      const token = await page.locator('input[name="_token"]').inputValue();
      await page.request.post(loginUrl, {
        form: { _token: token, email: 'owner@fancy-coffee.test', password: 'TestPassword!2026' },
      });
      const localeQ = locale === 'ar' ? '&locale=ar' : '';
      await page.goto(`${base}/app/fancy-coffee/zatca?step=${step}${localeQ}`, { waitUntil: 'networkidle' });
      await page.waitForTimeout(600);
      const name = `zatca-wizard-step${step}-${locale}-${tag}px`;
      await page.screenshot({ path: `${out}/${name}.png`, fullPage: true });
      console.log(name);
      await ctx.close();
    }
  }
}

await browser.close();
