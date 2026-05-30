/**
 * SmartPOS - Fast E2E Test
 * Navigates via direct URL + click to avoid accordion visibility issues
 */

import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots');
const REPORT_PATH = path.join(__dirname, '..', '..', 'TEST_REPORT.md');
const ADMIN = { email: 'admin@pos.com', password: 'admin123' };

if (!fs.existsSync(SCREENSHOT_DIR)) fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

const errors = [], passes = [];
let ssN = 0;

function ssName(n) { ssN++; return path.join(SCREENSHOT_DIR, `${String(ssN).padStart(3, '0')}_${n}.png`); }
async function shot(page, name) { const f = ssName(name); await page.screenshot({ path: f }); return f; }
function err(menu, act, msg, s) { errors.push({ menu, act, msg, shot: s, time: new Date().toISOString() }); }
function pass(menu, act) { passes.push({ menu, act, time: new Date().toISOString() }); }

// ── Helpers ────────────────────────────────────────────────
async function login(page) {
  await page.goto('/login', { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForTimeout(500);
  await page.fill('input[name="email"]', ADMIN.email);
  await page.fill('input[name="password"]', ADMIN.password);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard**', { timeout: 30000 });
  await page.waitForTimeout(500);
}

async function goto(page, path, label) {
  const t0 = Date.now();
  await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForTimeout(500);
  console.log(`  ${label}: ${Date.now() - t0}ms`);
}

async function testPage(page, path, label) {
  try {
    await goto(page, path, label);
    pass('Page', label);
    return true;
  } catch (e) {
    const s = await shot(page, `err_${label.replace(/[^a-z]/gi,'_')}`);
    err('Page', label, e.message, s);
    return false;
  }
}

// ── Test 1: Login ─────────────────────────────────────────
test('01 Login', async ({ page }) => {
  try {
    await login(page);
    await expect(page).toHaveURL(/dashboard/);
    await shot(page, '01_dashboard');
    pass('Login', 'Admin login');
  } catch (e) {
    const s = await shot(page, '01_login_err');
    err('Login', 'Login', e.message, s);
  }
});

// ── Test 2: All Pages ─────────────────────────────────────
test('02 All Pages', async ({ page }) => {
  await login(page);

  const pages = [
    ['/master/categories', 'Kategori'],
    ['/master/categories/create', 'Kategori Create'],
    ['/master/products', 'Produk'],
    ['/master/products/create', 'Produk Create'],
    ['/master/suppliers', 'Supplier'],
    ['/master/suppliers/create', 'Supplier Create'],
    ['/master/customers', 'Customer'],
    ['/master/customers/create', 'Customer Create'],
    ['/master/accounts', 'Akun Biaya'],
    ['/master/accounts/create', 'Akun Biaya Create'],
    ['/master/payment_methods', 'Metode Pembayaran'],
    ['/master/payment_methods/create', 'Metode Pembayaran Create'],
    ['/master/taxes', 'Pajak'],
    ['/master/taxes/create', 'Pajak Create'],
    ['/transaksi/purchases', 'Pembelian'],
    ['/transaksi/purchase_returns', 'Return Pembelian'],
    ['/pos/kasir', 'POS Kasir'],
    ['/transaksi/sale_returns', 'Retur Penjualan'],
    ['/pos/riwayat', 'Riwayat Penjualan'],
    ['/keuangan/payables', 'Bayar Hutang'],
    ['/keuangan/receivables', 'Terima Piutang'],
    ['/keuangan/cash_accounts', 'Akun Kas/Bank'],
    ['/keuangan/cash_transactions', 'Transaksi Kas'],
    ['/laporan/arus_kas', 'Alur Kas/Bank'],
    ['/akuntansi/journals', 'Jurnal Umum'],
    ['/akuntansi/ledger', 'Buku Besar'],
    ['/akuntansi/balance_sheet', 'Neraca'],
    ['/akuntansi/income_statement', 'Laba Rugi'],
    ['/stok/mutations', 'Kartu Stok'],
    ['/stok/opname', 'Stock Opname'],
    ['/laporan', 'Laporan'],
    ['/settings/company', 'Pengaturan'],
    ['/users', 'Manajemen User'],
    ['/roles', 'Role & Permission'],
  ];

  for (const [p, label] of pages) {
    await testPage(page, p, label);
  }
});

// ── Test 3: CRUD Categories ────────────────────────────────
test('03 CRUD Categories', async ({ page }) => {
  await login(page);
  const nm = `CAT_${Date.now()}`;
  const cd = `C${String(Date.now()).slice(-5)}`;

  try {
    // Create
    await goto(page, '/master/categories/create', 'Categories Create');
    await page.fill('input[name="code"]', cd);
    await page.fill('input[name="name"]', nm);
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);

    await expect(page.locator('#categories-table')).toContainText(nm, { timeout: 8000 });
    pass('CRUD Kategori', 'Tambah');

    // Edit
    const ed = page.locator('#categories-table tbody tr .btn-warning').first();
    if (await ed.isVisible({ timeout: 2000 }).catch(() => false)) {
      await ed.click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(400);
      await page.fill('input[name="name"]', nm + '_E');
      await page.locator('button:has-text("Perbarui")').click();
      await page.waitForLoadState('domcontentloaded');
      await page.waitForTimeout(500);
      await expect(page.locator('#categories-table')).toContainText(nm + '_E', { timeout: 5000 });
      pass('CRUD Kategori', 'Edit');
    }

    // Delete
    const del = page.locator('#categories-table tbody tr .btn-danger').first();
    if (await del.isVisible({ timeout: 2000 }).catch(() => false)) {
      page.once('dialog', d => d.accept());
      await del.click();
      await page.waitForTimeout(500);
      pass('CRUD Kategori', 'Hapus');
    }
  } catch (e) {
    const s = await shot(page, 'crud_categories_err');
    err('CRUD Kategori', 'CRUD', e.message, s);
  }
});

// ── Test 4: CRUD Taxes ─────────────────────────────────────
test('04 CRUD Taxes', async ({ page }) => {
  await login(page);
  const nm = `TAX_${Date.now()}`;
  const cd = `T${String(Date.now()).slice(-4)}`;

  try {
    await goto(page, '/master/taxes/create', 'Taxes Create');
    await page.fill('input[name="code"]', cd);
    await page.fill('input[name="name"]', nm);
    await page.fill('input[name="rate"]', '5');
    await page.selectOption('select[name="type"]', 'ppn');
    await page.selectOption('select[name="applies_to"]', 'sale');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);
    await expect(page.locator('#taxes-table')).toContainText(nm, { timeout: 8000 });
    pass('CRUD Pajak', 'Tambah');

    page.once('dialog', d => d.accept());
    const del = page.locator('#taxes-table tbody tr .btn-danger').first();
    if (await del.isVisible({ timeout: 2000 }).catch(() => false)) {
      await del.click();
      await page.waitForTimeout(500);
      pass('CRUD Pajak', 'Hapus');
    }
  } catch (e) {
    const s = await shot(page, 'crud_taxes_err');
    err('CRUD Pajak', 'CRUD', e.message, s);
  }
});

// ── Test 5: CRUD Suppliers ─────────────────────────────────
test('05 CRUD Suppliers', async ({ page }) => {
  await login(page);
  const nm = `SUP_${Date.now()}`;
  const cd = `S${String(Date.now()).slice(-4)}`;

  try {
    await goto(page, '/master/suppliers/create', 'Suppliers Create');
    await page.fill('input[name="code"]', cd);
    await page.fill('input[name="name"]', nm);
    await page.fill('input[name="phone"]', '08123456789');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);
    await expect(page.locator('#suppliers-table')).toContainText(nm, { timeout: 8000 });
    pass('CRUD Supplier', 'Tambah');

    page.once('dialog', d => d.accept());
    const del = page.locator('#suppliers-table tbody tr .btn-danger').first();
    if (await del.isVisible({ timeout: 2000 }).catch(() => false)) {
      await del.click();
      await page.waitForTimeout(500);
      pass('CRUD Supplier', 'Hapus');
    }
  } catch (e) {
    const s = await shot(page, 'crud_supplier_err');
    err('CRUD Supplier', 'CRUD', e.message, s);
  }
});

// ── Test 6: CRUD Customers ─────────────────────────────────
test('06 CRUD Customers', async ({ page }) => {
  await login(page);
  const nm = `CUS_${Date.now()}`;
  const cd = `C${String(Date.now()).slice(-4)}`;

  try {
    await goto(page, '/master/customers/create', 'Customers Create');
    await page.fill('input[name="code"]', cd);
    await page.fill('input[name="name"]', nm);
    await page.selectOption('select[name="type"]', 'retail');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);
    await expect(page.locator('#customers-table')).toContainText(nm, { timeout: 8000 });
    pass('CRUD Customer', 'Tambah');

    page.once('dialog', d => d.accept());
    const del = page.locator('#customers-table tbody tr .btn-danger').first();
    if (await del.isVisible({ timeout: 2000 }).catch(() => false)) {
      await del.click();
      await page.waitForTimeout(500);
      pass('CRUD Customer', 'Hapus');
    }
  } catch (e) {
    const s = await shot(page, 'crud_customer_err');
    err('CRUD Customer', 'CRUD', e.message, s);
  }
});

// ── Test 7: CRUD Products ──────────────────────────────────
test('07 CRUD Products', async ({ page }) => {
  await login(page);
  const nm = `PRD_${Date.now()}`;
  const cd = `P${String(Date.now()).slice(-6)}`;

  try {
    await goto(page, '/master/products/create', 'Products Create');
    await page.fill('input[name="code"]', cd);
    await page.fill('input[name="name"]', nm);
    await page.fill('input[name="unit"]', 'PCS');
    await page.fill('input[name="purchase_price"]', '50000');
    await page.fill('input[name="selling_price"]', '75000');
    await page.fill('input[name="stock"]', '100');
    await page.fill('input[name="min_stock"]', '10');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);
    await expect(page.locator('#products-table')).toContainText(nm, { timeout: 8000 });
    pass('CRUD Produk', 'Tambah');

    page.once('dialog', d => d.accept());
    const del = page.locator('#products-table tbody tr .btn-danger').first();
    if (await del.isVisible({ timeout: 2000 }).catch(() => false)) {
      await del.click();
      await page.waitForTimeout(500);
      pass('CRUD Produk', 'Hapus');
    }
  } catch (e) {
    const s = await shot(page, 'crud_product_err');
    err('CRUD Produk', 'CRUD', e.message, s);
  }
});

// ── Test 8: CRUD Payment Methods ───────────────────────────
test('08 CRUD Payment Methods', async ({ page }) => {
  await login(page);
  const nm = `PAY_${Date.now()}`;
  const cd = `P${String(Date.now()).slice(-4)}`;

  try {
    await goto(page, '/master/payment_methods/create', 'Payment Create');
    await page.fill('input[name="code"]', cd);
    await page.fill('input[name="name"]', nm);
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);
    await expect(page.locator('#payment-methods-table')).toContainText(nm, { timeout: 8000 });
    pass('CRUD Metode Pembayaran', 'Tambah');

    page.once('dialog', d => d.accept());
    const del = page.locator('#payment-methods-table tbody tr .btn-danger').first();
    if (await del.isVisible({ timeout: 2000 }).catch(() => false)) {
      await del.click();
      await page.waitForTimeout(500);
      pass('CRUD Metode Pembayaran', 'Hapus');
    }
  } catch (e) {
    const s = await shot(page, 'crud_payment_err');
    err('CRUD Metode Pembayaran', 'CRUD', e.message, s);
  }
});

// ── Test 9: CRUD Accounts ──────────────────────────────────
test('09 CRUD Accounts', async ({ page }) => {
  await login(page);
  const nm = `ACC_${Date.now()}`;
  const cd = `A${String(Date.now()).slice(-6)}`;

  try {
    await goto(page, '/master/accounts/create', 'Accounts Create');
    await page.fill('input[name="code"]', cd);
    await page.fill('input[name="name"]', nm);
    await page.selectOption('select[name="type"]', 'expense');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('domcontentloaded');
    await page.waitForTimeout(500);
    await expect(page.locator('#accounts-table')).toContainText(nm, { timeout: 8000 });
    pass('CRUD Akun Biaya', 'Tambah');

    page.once('dialog', d => d.accept());
    const del = page.locator('#accounts-table tbody tr .btn-danger').first();
    if (await del.isVisible({ timeout: 2000 }).catch(() => false)) {
      await del.click();
      await page.waitForTimeout(500);
      pass('CRUD Akun Biaya', 'Hapus');
    }
  } catch (e) {
    const s = await shot(page, 'crud_account_err');
    err('CRUD Akun Biaya', 'CRUD', e.message, s);
  }
});

// ── Test 10: Generate Report ───────────────────────────────
test('10 Generate Report', async () => {
  const total = passes.length + errors.length;
  const pct = total > 0 ? Math.round((passes.length / total) * 100) : 0;

  let md = `# SmartPOS - Test Report

**Generated**: ${new Date().toLocaleString('id-ID')}
**Base URL**: http://localhost:8000
**Admin**: ${ADMIN.email}

---

## Summary

| Metric | Count |
|--------|-------|
| Total Checks | ${total} |
| Passed | ${passes.length} |
| Failed | ${errors.length} |
| Pass Rate | ${pct}% |
| Screenshots | ${ssN} |

---

## Passed

| # | Menu | Action | Time |
|---|------|--------|------|
`;

  passes.forEach((p, i) => {
    md += `| ${i + 1} | ${p.menu} | ${p.act} | ${p.time} |\n`;
  });

  md += `
---

## Failed

`;

  if (errors.length === 0) {
    md += `No errors found.\n\n`;
  } else {
    md += `| # | Menu | Action | Error | Screenshot |
|---|------|--------|-------|------------|
`;
    errors.forEach((e, i) => {
      const short = e.msg && e.msg.length > 80 ? e.msg.substring(0, 77) + '...' : (e.msg || '');
      md += `| ${i + 1} | ${e.menu} | ${e.act} | ${short} | ${path.basename(e.shot || '')} |\n`;
    });
  }

  md += `
---

## Error Details

`;

  if (errors.length > 0) {
    errors.forEach((e, i) => {
      md += `### ${i + 1}. ${e.menu} - ${e.act}\n`;
      md += `- **Error**: ${e.msg}\n`;
      md += `- **Screenshot**: ${path.basename(e.shot || '')}\n`;
      md += `- **Time**: ${e.time}\n\n`;
    });
  } else {
    md += `No errors.\n\n`;
  }

  md += `---

*Generated by Playwright E2E. Screenshots: \`tests/playwright/screenshots/\`*
`;

  fs.writeFileSync(REPORT_PATH, md, 'utf-8');
  console.log(`\n=== REPORT ===`);
  console.log(`Passed: ${passes.length}, Failed: ${errors.length}, Total: ${total}`);
  console.log(`Report: ${REPORT_PATH}`);
  console.log(`Screenshots: ${SCREENSHOT_DIR}`);

  if (errors.length > 0) {
    throw new Error(`${errors.length} failures found. See ${REPORT_PATH}`);
  }
});
