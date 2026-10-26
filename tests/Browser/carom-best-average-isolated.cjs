const fs = require('node:fs');
const cp = require('node:child_process');
const crypto = require('node:crypto');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require('/home/ondrej/.npm-global/lib/node_modules/playwright');
const root = path.resolve(__dirname, '../..');
const out = fs.mkdtempSync('/mnt/disk-a/dev/tmp/carom-best-average-');
fs.chmodSync(out, 0o700);
const network = 'jl-carom-best-' + Date.now();
const containers = [];
const records = [];
const secret = crypto.randomBytes(24).toString('hex');
const adminPassword = crypto.randomBytes(24).toString('hex');
const archive = '/mnt/disk-a/docker/local-fcraksice/Joomla_6.2.0-Stable-Full_Package.zip';
const suite = root + '/tests/artifacts/support-topic90-20261025/dist/pkg_joomleague-6.2.0-rc2.zip';
const run = (...args) => cp.execFileSync('docker', args, { encoding: 'utf8', timeout: 240000, maxBuffer: 4 * 1024 * 1024, stdio: ['ignore', 'pipe', 'pipe'] });
const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
let browser;
let diagnosticPage;
(async () => {
  assert(fs.existsSync(suite));
  cp.execFileSync('unzip', ['-tq', archive]);
  fs.mkdirSync(out + '/temp', { mode: 0o700 });
  process.env.TMPDIR = out + '/temp';
  fs.writeFileSync(out + '/db.env', `MARIADB_ROOT_PASSWORD=${secret}\nMARIADB_DATABASE=encounter\nMARIADB_USER=joomla\nMARIADB_PASSWORD=${secret}\nPOSTGRES_PASSWORD=${secret}\nPOSTGRES_DB=encounter\n`, { mode: 0o600 });
  fs.writeFileSync(out + '/app.env', `JL_TEST_PG_PASSWORD=${secret}\n`, { mode: 0o600 });
  run('network', 'create', '--internal', network);
  browser = await chromium.launch({ headless: true });
  for (const driver of ['mysqli', 'pgsql']) {
    const db = network + '-' + driver + '-db';
    const app = network + '-' + driver + '-app';
    const webroot = out + '/' + driver;
    fs.mkdirSync(webroot, { mode: 0o755 });
    cp.execFileSync('unzip', ['-q', archive, '-d', webroot]);
    const setup = { driver, host: db, user: driver === 'pgsql' ? 'postgres' : 'joomla', password: secret, adminPassword };
    fs.writeFileSync(out + '/setup-' + driver + '.json', JSON.stringify(setup), { mode: 0o600 });
    const dbArgs = ['run', '-d', '--name', db, '--network', network, '--memory', '1g', '--cpus', '1', '--pids-limit', '256', '--env-file', out + '/db.env'];
    if (driver === 'pgsql') dbArgs.push('--network-alias', 'jl-encounter-pg-test', '--tmpfs', '/var/lib/postgresql:rw,size=512m', 'postgres:18-alpine');
    else dbArgs.push('--tmpfs', '/var/lib/mysql:rw,size=512m', 'mariadb:11.4');
    containers.push(db); run(...dbArgs);
    let ready = false;
    for (let i = 0; i < 90; i++) {
      try { driver === 'pgsql' ? run('exec', db, 'pg_isready', '-U', 'postgres', '-d', 'encounter') : run('exec', db, 'healthcheck.sh', '--connect', '--innodb_initialized'); ready = true; break; } catch {}
      await pause(1000);
    }
    assert(ready, 'Isolated database not ready');
    containers.push(app);
    run('run', '-d', '--name', app, '--network', network, '--memory', '1g', '--cpus', '2', '--pids-limit', '256', '--env-file', out + '/app.env', '-v', webroot + ':/var/www/html', '-v', out + ':/lab:ro', '-v', root + ':/source:ro', '--entrypoint', 'docker-php-entrypoint', 'klucon/php8512:local-fcraksice-app', 'apache2-foreground');
    fs.writeFileSync(out + '/' + driver + '-install.log', run('exec', app, 'php', '/source/tests/Integration/carom-best-average-install.php', '/lab/setup-' + driver + '.json'), { mode: 0o600 });
    assert(fs.existsSync(webroot + '/configuration.php'), 'CMS installation failed');
    run('exec', app, 'php', '/source/tests/Integration/carom-best-average-configure.php');
    if (fs.existsSync(webroot + '/installation')) fs.renameSync(webroot + '/installation', out + '/installation-' + driver);
    run('cp', suite, app + ':/tmp/carom-suite.zip');
    fs.writeFileSync(out + '/' + driver + '-package.log', run('exec', app, 'php', '/source/tests/Integration/carom-best-average-package.php', '/tmp/carom-suite.zip'), { mode: 0o600 });
    run('exec', app, 'chown', '-R', 'www-data:www-data', '/var/www/html');
    const flags = driver === 'pgsql' ? ['--pgsql'] : [];
    const tennis = run('exec', app, 'php', '/source/tests/Integration/team-encounter.php', ...flags);
    fs.writeFileSync(out + '/' + driver + '-tennis.log', tennis, { mode: 0o600 });
    assert(tennis.includes('encounter OK:'), 'Tennis regression checks did not run');
    const penalties = run('exec', app, 'php', '/source/tests/Integration/team-encounter.php', '--cycles', ...flags);
    fs.writeFileSync(out + '/' + driver + '-penalties.log', penalties, { mode: 0o600 });
    assert(penalties.includes('Portuguese penalties OK:'), 'Penalty regression checks did not run');
    const integration = run('exec', app, 'php', '/source/tests/Integration/team-encounter.php', '--carom', '--keep', '--browser-complete', ...flags);
    fs.writeFileSync(out + '/' + driver + '-integration.log', integration, { mode: 0o600 });
    assert(integration.includes('Best individual average OK:'), 'New persistence checks did not run');
    const fixture = JSON.parse(integration.trim().split('\n').at(-1));
    const ip = JSON.parse(run('inspect', app, '--format', '{{json .NetworkSettings.Networks}}'))[network].IPAddress;
    const base = 'http://' + ip;
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
    const page = await context.newPage();
    diagnosticPage = page;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(base + '/administrator/', { waitUntil: 'networkidle' });
    await page.locator('#mod-login-username').fill('admin');
    await page.locator('#mod-login-password').fill(adminPassword);
    await page.locator('button[type=submit]').click();
    await page.waitForURL('**/administrator/index.php');
    await page.waitForLoadState('networkidle');
    const rulesResponse = await page.goto(base + '/administrator/index.php?option=com_joomleague&view=projectrules&project_id=' + fixture.project, { waitUntil: 'networkidle' });
    fs.writeFileSync(out + '/' + driver + '-rules-text.txt', await page.locator('body').innerText(), { mode: 0o600 });
    fs.writeFileSync(out + '/' + driver + '-rules-url.json', JSON.stringify({ url: page.url(), response: rulesResponse.url() }), { mode: 0o600 });
    assert.equal(rulesResponse.status(), 200, 'Rules view failed');
    const row = page.locator('tr').filter({ hasText: 'First tie-break criterion' });
    assert.equal(await row.count(), 1);
    const select = row.locator('select');
    assert.equal(await select.inputValue(), 'best_average');
    assert((await select.innerText()).includes('Highest personal average (single game)'));
    const save = async value => {
      await select.selectOption(value);
      await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.evaluate(() => {
        const form = document.querySelector('#adminForm');
        form.querySelector('[name=task]').value = 'projectrules.apply';
        HTMLFormElement.prototype.submit.call(form);
      })]);
      assert.equal(await select.inputValue(), value);
      assert.equal(await page.locator('.alert-danger').count(), 0);
    };
    await save('average'); await save('best_average');
    await page.bringToFront();
    await row.evaluate(element => element.scrollIntoView({ block: 'center' }));
    fs.writeFileSync(out + '/' + driver + '-rules-dimensions.json', JSON.stringify(await page.evaluate(() => ({ width: innerWidth, height: innerHeight, scrollHeight: document.documentElement.scrollHeight }))), { mode: 0o600 });
    await page.screenshot({ path: out + '/' + driver + '-rules.png', timeout: 60000, animations: 'disabled' });
    for (const [label, width, height] of [['desktop', 1440, 1000], ['mobile', 390, 844]]) {
      const publicContext = await browser.newContext({ viewport: { width, height } });
      const publicPage = await publicContext.newPage();
      publicPage.on('pageerror', error => errors.push(error.message));
      const response = await publicPage.goto(base + '/index.php?option=com_joomleague&view=standings&project_id=' + fixture.project, { waitUntil: 'networkidle' });
      assert.equal(response.status(), 200);
      const content = await publicPage.locator('main').innerText();
      assert(content.includes('Highest personal average'));
      assert(content.includes('0.930232558'), 'Published best average was not rendered');
      assert(!content.includes('COM_JOOMLEAGUE_'));
      assert(await publicPage.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
      await publicPage.screenshot({ path: out + '/' + driver + '-' + label + '.png', fullPage: true, timeout: 60000, animations: 'disabled' });
      await publicPage.locator('.com-joomleague-standings .table-responsive').evaluate(element => { element.scrollLeft = element.scrollWidth; });
      await publicPage.screenshot({ path: out + '/' + driver + '-' + label + '-best-average.png', fullPage: true, timeout: 60000, animations: 'disabled' });
      await publicContext.close();
    }
    const rejected = await context.request.post(base + '/administrator/index.php', { form: { option: 'com_joomleague', task: 'projectrules.apply', project_id: String(fixture.project) } });
    assert([200, 403].includes(rejected.status()) && /invalid token|security token|JINVALID_TOKEN/i.test(await rejected.text()), 'Missing CSRF token was not rejected');
    await page.reload({ waitUntil: 'networkidle' });
    assert.equal(await select.inputValue(), 'best_average', 'Rejected request changed the saved criterion');
    assert.deepEqual(errors, []);
    await context.close();
    records.push({ driver, passed: true, fixture, csrfStatus: rejected.status(), checks: ['native-install', 'tennis-regression', 'penalty-cycles-regression', 'persisted-best-average', 'snapshot-order', 'pinned-profile-checksum', 'rule-save-reload', 'missing-csrf-rejected', 'desktop-mobile', 'no-js-errors'] });
    console.log(JSON.stringify(records.at(-1)));
  }
  fs.writeFileSync(out + '/results.json', JSON.stringify({ passed: true, suiteSha256: crypto.createHash('sha256').update(fs.readFileSync(suite)).digest('hex'), records }, null, 2), { mode: 0o600 });
})().catch(async error => {
  fs.writeFileSync(out + '/failure.txt', error.stack, { mode: 0o600 });
  if (diagnosticPage && !diagnosticPage.isClosed()) {
    await diagnosticPage.screenshot({ path: out + '/failure.png', fullPage: true }).catch(() => {});
  }
  if (error.stdout) fs.writeFileSync(out + '/failure-stdout.txt', error.stdout, { mode: 0o600 });
  if (error.stderr) fs.writeFileSync(out + '/failure-stderr.txt', error.stderr, { mode: 0o600 });
  console.error(error.message); process.exitCode = 1;
}).finally(async () => {
  if (browser) await browser.close();
  for (const container of containers.reverse()) {
    try { const log = cp.spawnSync('docker', ['logs', container], { encoding: 'utf8' }); fs.writeFileSync(out + '/' + container + '.log', log.stdout + log.stderr, { mode: 0o600 }); } catch {}
    try { run('stop', '-t', '5', container); } catch {}
    try { run('rm', container); } catch {}
  }
  try { run('network', 'rm', network); } catch {}
  console.log('Evidence: ' + out);
});
