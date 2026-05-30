/**
 * Debug: Full navigation test
 */
import { test } from '@playwright/test';

const ADMIN = { email: 'admin@pos.com', password: 'admin123' };

async function login(page) {
  await page.goto('/login', { waitUntil: 'domcontentloaded', timeout: 15000 });
  await page.waitForTimeout(300);
  await page.fill('input[name="email"]', ADMIN.email);
  await page.fill('input[name="password"]', ADMIN.password);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard**', { timeout: 15000 });
  await page.waitForTimeout(500);
}

async function ensureMenuOpen(page, menu, link) {
  const targetLink = page.locator(`nav a:has-text("${link}")`).first();
  const isVis = await targetLink.isVisible().catch(() => false);
  console.log(`  Link "${link}" visible: ${isVis}`);
  if (isVis) return;

  const btn = page.locator(`nav button:has-text("${menu}")`).first();
  const btnVis = await btn.isVisible().catch(() => false);
  console.log(`  Button "${menu}" visible: ${btnVis}`);
  if (btnVis) {
    await btn.click();
    await page.waitForTimeout(400);
    const nowVis = await targetLink.isVisible().catch(() => false);
    console.log(`  After click, "${link}" visible: ${nowVis}`);
  }
}

async function clickNav(page, text) {
  const link = page.locator(`nav a:has-text("${text}")`).first();
  const t = Date.now();
  await link.click();
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(300);
  console.log(`  -> Navigated (${Date.now() - t}ms): ${page.url()}`);
}

test('Nav debug', async ({ page }) => {
  await login(page);
  console.log('URL:', page.url());

  const routes = [
    ['Data Master','Kategori'],
    ['Data Master','Produk'],
    ['Data Master','Supplier'],
    ['Pembelian','Pembelian'],
    ['Penjualan','POS Kasir'],
    ['Akuntansi','Jurnal Umum'],
    ['Pengguna','Manajemen User'],
    [null,'Laporan'],
    [null,'Pengaturan'],
  ];

  for (const [m, l] of routes) {
    console.log(`\n>>> ${m ? m + ' > ' : ''}${l}`);
    try {
      if (m) {
        await ensureMenuOpen(page, m, l);
        await clickNav(page, l);
      } else {
        await clickNav(page, l);
      }
    } catch (e) {
      console.log(`  ERROR: ${e.message.substring(0, 100)}`);
    }
  }

  console.log('\nDone!');
});
