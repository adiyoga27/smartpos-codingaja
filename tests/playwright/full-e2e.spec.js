/**
 * SmartPOS - Comprehensive Playwright E2E Test
 * Tests: Login, Sidebar Navigation, CRUD Operations
 */

import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL = 'http://localhost:8000';
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots');
const REPORT_PATH = path.join(__dirname, '..', '..', 'TEST_REPORT.md');

const ADMIN = { email: 'admin@pos.com', password: 'admin123' };

if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });
}

const errors = [];
const passes = [];
let ssCounter = 0;

function ssName(prefix) {
  ssCounter++;
  return path.join(SCREENSHOT_DIR, `${String(ssCounter).padStart(3, '0')}_${prefix}.png`);
}

async function ss(page, prefix) {
  const f = ssName(prefix);
  await page.screenshot({ path: f, fullPage: true });
  return f;
}

function fail(menu, action, msg, shot) {
  errors.push({ menu, action, msg, shot, time: new Date().toISOString() });
}

function ok(menu, action) {
  passes.push({ menu, action, time: new Date().toISOString() });
}

// ─── Helpers ──────────────────────────────────────────────────
async function login(page) {
  await page.goto('/login', { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', ADMIN.email);
  await page.fill('input[name="password"]', ADMIN.password);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard**', { timeout: 10000 });
}

async function toggleAccordion(page, text) {
  const btn = page.locator(`nav button:has-text("${text}")`).first();
  if (await btn.isVisible({ timeout: 2000 }).catch(() => false)) {
    await btn.click();
    await page.waitForTimeout(300);
  }
}

async function goSidebar(page, menu, link) {
  await toggleAccordion(page, menu);
  await page.waitForTimeout(200);
  await page.locator(`nav a:has-text("${link}")`).first().click();
  await page.waitForLoadState('networkidle');
}

async function goSidebarDirect(page, link) {
  await page.locator(`nav a:has-text("${link}")`).first().click();
  await page.waitForLoadState('networkidle');
}

async function clickCreate(page, text) {
  await page.locator(`a:has-text("${text}")`).first().click();
  await page.waitForLoadState('networkidle');
}

async function submitForm(page, fields, btnText = 'Simpan') {
  for (const [sel, val] of Object.entries(fields)) {
    await page.locator(sel).fill(String(val));
  }
  await page.locator(`button:has-text("${btnText}")`).click();
  await page.waitForLoadState('networkidle');
}

async function deleteFirstRow(page, tableSelector) {
  await page.waitForSelector(`${tableSelector} tbody tr`, { timeout: 5000 }).catch(() => {});
  const del = page.locator(`${tableSelector} tbody tr .btn-danger`).first();
  if (await del.isVisible({ timeout: 2000 }).catch(() => false)) {
    page.once('dialog', d => d.accept());
    await del.click();
    await page.waitForTimeout(600);
    await page.waitForLoadState('networkidle');
    return true;
  }
  return false;
}

// ─── THE ONE BIG TEST ─────────────────────────────────────────
test('SmartPOS Full E2E', async ({ page }) => {

  // ── 1. Login ──────────────────────────────────────────────
  try {
    await login(page);
    await expect(page).toHaveURL(/dashboard/);
    ok('Login', 'Admin login successful');
  } catch (e) {
    const shot = await ss(page, '01_login_err');
    fail('Login', 'Login', e.message, shot);
    throw e;
  }

  // ── 2. Take dashboard screenshot ──────────────────────────
  await ss(page, '02_dashboard');

  // ── 3. Open sidebar accordions ────────────────────────────
  const accordions = [
    'Data Master', 'Pembelian', 'Penjualan', 'Hutang & Piutang',
    'Alur Kas & Bank', 'Akuntansi', 'Stok Kontrol', 'Pengguna',
  ];
  for (const a of accordions) {
    try {
      await toggleAccordion(page, a);
      ok('Sidebar', `Accordion: ${a}`);
    } catch (e) {
      fail('Sidebar', `Accordion: ${a}`, e.message, await ss(page, `sidebar_${a.replace(/[^a-z]/gi, '_')}`));
    }
  }

  // ── 4. Navigate all sidebar pages ─────────────────────────
  const navItems = [
    { menu: 'Data Master', links: ['Kategori', 'Produk', 'Supplier', 'Customer', 'Akun Biaya', 'Metode Pembayaran', 'Pajak'] },
    { menu: 'Pembelian', links: ['Pembelian', 'Return Pembelian'] },
    { menu: 'Penjualan', links: ['POS Kasir', 'Retur Penjualan', 'Riwayat Penjualan'] },
    { menu: 'Hutang & Piutang', links: ['Bayar Hutang', 'Terima Piutang'] },
    { menu: 'Alur Kas & Bank', links: ['Akun Kas/Bank', 'Transaksi Kas', 'Alur Kas/Bank'] },
    { menu: 'Akuntansi', links: ['Jurnal Umum', 'Buku Besar', 'Neraca', 'Laba Rugi'] },
    { menu: 'Stok Kontrol', links: ['Kartu Stok', 'Stock Opname'] },
  ];

  for (const { menu, links } of navItems) {
    for (const link of links) {
      try {
        await goSidebar(page, menu, link);
        await ss(page, `nav_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`);
        ok(`Nav: ${menu}`, link);
      } catch (e) {
        fail(`Nav: ${menu}`, link, e.message, await ss(page, `nav_err_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`));
      }
    }
  }

  // Standalone links
  for (const link of ['Laporan', 'Pengaturan']) {
    try {
      await goSidebarDirect(page, link);
      await ss(page, `nav_${link.toLowerCase()}`);
      ok('Nav', link);
    } catch (e) {
      fail('Nav', link, e.message, await ss(page, `nav_err_${link.toLowerCase()}`));
    }
  }

  // Pengguna submenu
  for (const link of ['Manajemen User', 'Role & Permission']) {
    try {
      await goSidebar(page, 'Pengguna', link);
      await ss(page, `nav_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`);
      ok('Nav: Pengguna', link);
    } catch (e) {
      fail('Nav: Pengguna', link, e.message, await ss(page, `nav_err_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`));
    }
  }

  // ── 5. CRUD: Categories ───────────────────────────────────
  const catName = `KAT_E2E_${Date.now()}`;
  const catCode = `CT${Date.now() % 100000}`;
  try {
    await goSidebar(page, 'Data Master', 'Kategori');
    await clickCreate(page, 'Tambah Kategori');
    await submitForm(page, { 'input[name="code"]': catCode, 'input[name="name"]': catName });
    await expect(page.locator('#categories-table')).toContainText(catName, { timeout: 5000 });
    ok('CRUD Categories', 'Create');

    const editBtn = page.locator('#categories-table tbody tr .btn-warning').first();
    if (await editBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
      await editBtn.click();
      await page.waitForLoadState('networkidle');
      await submitForm(page, { 'input[name="name"]': catName + '_EDIT' }, 'Perbarui');
      await expect(page.locator('#categories-table')).toContainText(catName + '_EDIT', { timeout: 5000 });
      ok('CRUD Categories', 'Edit');
    }

    if (await deleteFirstRow(page, '#categories-table')) {
      ok('CRUD Categories', 'Delete');
    }
  } catch (e) {
    fail('CRUD Categories', 'CRUD', e.message, await ss(page, 'crud_categories_err'));
  }

  // ── 6. CRUD: Taxes ────────────────────────────────────────
  const taxName = `TAX_E2E_${Date.now()}`;
  const taxCode = `TX${Date.now() % 10000}`;
  try {
    await goSidebar(page, 'Data Master', 'Pajak');
    await clickCreate(page, 'Tambah Pajak');
    await page.fill('input[name="code"]', taxCode);
    await page.fill('input[name="name"]', taxName);
    await page.fill('input[name="rate"]', '5');
    await page.selectOption('select[name="type"]', 'ppn');
    await page.selectOption('select[name="applies_to"]', 'sale');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#taxes-table')).toContainText(taxName, { timeout: 5000 });
    ok('CRUD Taxes', 'Create');

    if (await deleteFirstRow(page, '#taxes-table')) ok('CRUD Taxes', 'Delete');
  } catch (e) {
    fail('CRUD Taxes', 'CRUD', e.message, await ss(page, 'crud_taxes_err'));
  }

  // ── 7. CRUD: Payment Methods ──────────────────────────────
  const payName = `PAY_E2E_${Date.now()}`;
  const payCode = `PM${Date.now() % 10000}`;
  try {
    await goSidebar(page, 'Data Master', 'Metode Pembayaran');
    await clickCreate(page, 'Tambah Metode');
    await submitForm(page, { 'input[name="code"]': payCode, 'input[name="name"]': payName });
    await expect(page.locator('#payment-methods-table')).toContainText(payName, { timeout: 5000 });
    ok('CRUD Payment Methods', 'Create');

    if (await deleteFirstRow(page, '#payment-methods-table')) ok('CRUD Payment Methods', 'Delete');
  } catch (e) {
    fail('CRUD Payment Methods', 'CRUD', e.message, await ss(page, 'crud_payment_methods_err'));
  }

  // ── 8. CRUD: Suppliers ────────────────────────────────────
  const supName = `SUP_E2E_${Date.now()}`;
  const supCode = `SP${Date.now() % 10000}`;
  try {
    await goSidebar(page, 'Data Master', 'Supplier');
    await clickCreate(page, 'Tambah Supplier');
    await submitForm(page, { 'input[name="code"]': supCode, 'input[name="name"]': supName, 'input[name="phone"]': '08123456789' });
    await expect(page.locator('#suppliers-table')).toContainText(supName, { timeout: 5000 });
    ok('CRUD Suppliers', 'Create');

    if (await deleteFirstRow(page, '#suppliers-table')) ok('CRUD Suppliers', 'Delete');
  } catch (e) {
    fail('CRUD Suppliers', 'CRUD', e.message, await ss(page, 'crud_suppliers_err'));
  }

  // ── 9. CRUD: Customers ────────────────────────────────────
  const cusName = `CUS_E2E_${Date.now()}`;
  const cusCode = `CU${Date.now() % 10000}`;
  try {
    await goSidebar(page, 'Data Master', 'Customer');
    await clickCreate(page, 'Tambah Customer');
    await page.fill('input[name="code"]', cusCode);
    await page.fill('input[name="name"]', cusName);
    await page.selectOption('select[name="type"]', 'retail');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#customers-table')).toContainText(cusName, { timeout: 5000 });
    ok('CRUD Customers', 'Create');

    if (await deleteFirstRow(page, '#customers-table')) ok('CRUD Customers', 'Delete');
  } catch (e) {
    fail('CRUD Customers', 'CRUD', e.message, await ss(page, 'crud_customers_err'));
  }

  // ── 10. CRUD: Products ────────────────────────────────────
  const prdName = `PRD_E2E_${Date.now()}`;
  const prdCode = `PD${Date.now() % 100000}`;
  try {
    await goSidebar(page, 'Data Master', 'Produk');
    await clickCreate(page, 'Tambah');
    await submitForm(page, {
      'input[name="code"]': prdCode,
      'input[name="name"]': prdName,
      'input[name="purchase_price"]': '50000',
      'input[name="selling_price"]': '75000',
      'input[name="stock"]': '100',
    });
    await expect(page.locator('#products-table')).toContainText(prdName, { timeout: 5000 });
    ok('CRUD Products', 'Create');

    if (await deleteFirstRow(page, '#products-table')) ok('CRUD Products', 'Delete');
  } catch (e) {
    fail('CRUD Products', 'CRUD', e.message, await ss(page, 'crud_products_err'));
  }

  // ── 11. CRUD: Accounts ────────────────────────────────────
  const accName = `AKN_E2E_${Date.now()}`;
  const accCode = `AC${Date.now() % 100000}`;
  try {
    await goSidebar(page, 'Data Master', 'Akun Biaya');
    await clickCreate(page, 'Tambah Akun Biaya');
    await page.fill('input[name="code"]', accCode);
    await page.fill('input[name="name"]', accName);
    await page.selectOption('select[name="type"]', 'expense');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('#accounts-table')).toContainText(accName, { timeout: 5000 });
    ok('CRUD Accounts', 'Create');

    if (await deleteFirstRow(page, '#accounts-table')) ok('CRUD Accounts', 'Delete');
  } catch (e) {
    fail('CRUD Accounts', 'CRUD', e.message, await ss(page, 'crud_accounts_err'));
  }

  // ── 12. Cash Accounts ─────────────────────────────────────
  try {
    await goSidebar(page, 'Alur Kas & Bank', 'Akun Kas/Bank');
    await ss(page, 'cash_accounts');
    ok('Page', 'Cash Accounts');
  } catch (e) {
    fail('Page', 'Cash Accounts', e.message, await ss(page, 'cash_accounts_err'));
  }

  // ── 13. Cash Transactions ─────────────────────────────────
  try {
    await goSidebar(page, 'Alur Kas & Bank', 'Transaksi Kas');
    await ss(page, 'cash_transactions');
    ok('Page', 'Cash Transactions');
  } catch (e) {
    fail('Page', 'Cash Transactions', e.message, await ss(page, 'cash_transactions_err'));
  }

  // ── 14. User Management ───────────────────────────────────
  try {
    await goSidebar(page, 'Pengguna', 'Manajemen User');
    await ss(page, 'users');
    ok('Page', 'User Management');
  } catch (e) {
    fail('Page', 'User Management', e.message, await ss(page, 'users_err'));
  }

  // ── 15. Roles & Permissions ───────────────────────────────
  try {
    await goSidebar(page, 'Pengguna', 'Role & Permission');
    await ss(page, 'roles');
    ok('Page', 'Roles');
  } catch (e) {
    fail('Page', 'Roles', e.message, await ss(page, 'roles_err'));
  }

  // ── 16. Journal ───────────────────────────────────────────
  try {
    await goSidebar(page, 'Akuntansi', 'Jurnal Umum');
    await ss(page, 'journals');
    ok('Page', 'Journals');
  } catch (e) {
    fail('Page', 'Journals', e.message, await ss(page, 'journals_err'));
  }

  // ── 17. Purchases & Purchase Returns ──────────────────────
  for (const link of ['Pembelian', 'Return Pembelian']) {
    try {
      await goSidebar(page, 'Pembelian', link);
      await ss(page, `tx_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`);
      ok('Page', link);
    } catch (e) {
      fail('Page', link, e.message, await ss(page, `tx_err_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`));
    }
  }

  // ── 18. Sales pages ───────────────────────────────────────
  for (const link of ['POS Kasir', 'Retur Penjualan', 'Riwayat Penjualan']) {
    try {
      await goSidebar(page, 'Penjualan', link);
      await ss(page, `tx_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`);
      ok('Page', link);
    } catch (e) {
      fail('Page', link, e.message, await ss(page, `tx_err_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`));
    }
  }

  // ── 19. Payables & Receivables ────────────────────────────
  for (const link of ['Bayar Hutang', 'Terima Piutang']) {
    try {
      await goSidebar(page, 'Hutang & Piutang', link);
      await ss(page, `tx_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`);
      ok('Page', link);
    } catch (e) {
      fail('Page', link, e.message, await ss(page, `tx_err_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`));
    }
  }

  // ── 20. Accounting reports ────────────────────────────────
  for (const link of ['Buku Besar', 'Neraca', 'Laba Rugi']) {
    try {
      await goSidebar(page, 'Akuntansi', link);
      await ss(page, `acct_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`);
      ok('Page', link);
    } catch (e) {
      fail('Page', link, e.message, await ss(page, `acct_err_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`));
    }
  }

  // ── 21. Stock pages ───────────────────────────────────────
  for (const link of ['Kartu Stok', 'Stock Opname']) {
    try {
      await goSidebar(page, 'Stok Kontrol', link);
      await ss(page, `stock_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`);
      ok('Page', link);
    } catch (e) {
      fail('Page', link, e.message, await ss(page, `stock_err_${link.replace(/[^a-z]/gi, '_').toLowerCase()}`));
    }
  }

  // ── 22. Settings ──────────────────────────────────────────
  try {
    await goSidebarDirect(page, 'Pengaturan');
    await ss(page, 'settings');
    ok('Page', 'Settings');
  } catch (e) {
    fail('Page', 'Settings', e.message, await ss(page, 'settings_err'));
  }

  // ── 23. Reports ───────────────────────────────────────────
  try {
    await goSidebarDirect(page, 'Laporan');
    await ss(page, 'reports');
    ok('Page', 'Reports');
  } catch (e) {
    fail('Page', 'Reports', e.message, await ss(page, 'reports_err'));
  }

  // ── FINAL: Generate TEST_REPORT.md ────────────────────────
  const total = passes.length + errors.length;
  const pct = total > 0 ? Math.round((passes.length / total) * 100) : 0;

  let md = `# SmartPOS - Test Report

**Generated**: ${new Date().toLocaleString('id-ID')}
**Base URL**: ${BASE_URL}
**Admin**: ${ADMIN.email}

---

## Summary

| Metric | Count |
|--------|-------|
| Total Checks | ${total} |
| Passed | ${passes.length} |
| Failed | ${errors.length} |
| Pass Rate | ${pct}% |
| Screenshots | ${ssCounter} |

---

## Passed

| # | Menu | Action | Time |
|---|------|--------|------|
`;

  passes.forEach((p, i) => {
    md += `| ${i + 1} | ${p.menu} | ${p.action} | ${p.time} |\n`;
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
      const ssf = path.basename(e.shot || '');
      md += `| ${i + 1} | ${e.menu} | ${e.action} | ${short} | ${ssf} |\n`;
    });
  }

  md += `
---

## Error Details

`;

  if (errors.length > 0) {
    errors.forEach((e, i) => {
      md += `### ${i + 1}. ${e.menu} - ${e.action}\n`;
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

  if (errors.length > 0) {
    throw new Error(`Test completed with ${errors.length} failures. See ${REPORT_PATH}`);
  }
});
