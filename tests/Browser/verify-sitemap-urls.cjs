const fs = require('node:fs');
const { chromium } = require('playwright');

(async () => {
  const input = JSON.parse(fs.readFileSync(process.env.JOOMLA_SITEMAP_SAMPLES, 'utf8'));
  const base = new URL(process.env.JOOMLA_BASE_URL);
  const credentials = fs.readFileSync(process.env.JOOMLA_CREDENTIALS_FILE, 'utf8');
  const username = credentials.match(/Username: `([^`]+)`/i)?.[1];
  const password = credentials.match(/Password: `([^`]+)`/i)?.[1];
  if (!username || !password) throw new Error('Missing test credentials.');
  const browser = await chromium.launch({ headless: true });
  const observations = [];
  const failures = [];
  try {
    const context = await browser.newContext();
    const page = await context.newPage();
    await page.goto(base.href, { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="username"]').fill(username);
    await page.locator('input[name="password"]').fill(password);
    await Promise.all([page.waitForNavigation(), page.locator('button[type="submit"]').click()]);
    if (await page.locator('input[name="password"]').count()) throw new Error('Frontend login failed.');
    for (const [type, rows] of Object.entries(input.samples)) {
      for (const row of rows) {
        const url = new URL(row.url);
        if (url.origin !== base.origin) throw new Error('Sample origin differs from test site.');
        const response = await page.goto(url.href, { waitUntil: 'domcontentloaded', timeout: 45000 });
        const canonicals = page.locator('link[rel="canonical"]');
        const count = await canonicals.count();
        const canonical = count === 1 ? await canonicals.first().getAttribute('href') : null;
        const final = page.url();
        const main = await page.locator('main').innerText().catch(() => '');
        const equal = (a, b) => decodeURI(new URL(a).href) === decodeURI(new URL(b).href);
        const reasons = [];
        if (!response || response.status() !== 200) reasons.push('Non-200 response');
        if (!canonical || !equal(canonical, url.href)) reasons.push('Canonical differs from provider URL');
        if (!equal(final, url.href)) reasons.push('Final URL differs from provider URL');
        if (!main.trim() || /Fatal error|Warning:|COM_JOOMLEAGUE_[A-Z_]+/.test(main)) reasons.push('Empty or invalid content');
        if (await page.locator('input[name="password"]').count()) reasons.push('Unexpected login page');
        observations.push({ type, id: row.id, url: url.href, final, canonical, status: response?.status(), reasons });
        if (reasons.length) failures.push({ type, id: row.id, reasons });
      }
    }
  } finally {
    await browser.close();
  }
  if (process.env.JOOMLA_AUDIT_REPORT) {
    fs.writeFileSync(process.env.JOOMLA_AUDIT_REPORT, JSON.stringify({ observations, failures }, null, 2), { mode: 0o600 });
    fs.chmodSync(process.env.JOOMLA_AUDIT_REPORT, 0o600);
  }
  console.log(JSON.stringify({ checked: observations.length, failures }));
  if (failures.length) process.exitCode = 1;
})().catch((error) => { console.error(error.message); process.exitCode = 1; });
